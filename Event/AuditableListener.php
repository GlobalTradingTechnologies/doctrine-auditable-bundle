<?php
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAdapterBundle\Event;

use Doctrine\DBAL\Types\DateTimeType;
use Gtt\Bundle\DoctrineAdapterBundle\Mapping\Reader\AnnotationInterface;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gtt\Bundle\DoctrineAdapterBundle\Entity\Entry;
use Gtt\Bundle\DoctrineAdapterBundle\Entity\Group;
use Gtt\Bundle\DoctrineAdapterBundle\Entity\GroupSuperClass;
use Gtt\Bundle\DoctrineAdapterBundle\Mapping\Reader\Annotation;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Auditable listener
 */
class AuditableListener implements EventSubscriber
{
    /**
     * Datetime with timezone format (ISO 8601)
     */
    const DATETIME_WITH_TIMEZONE_FORMAT = 'c';

    /**
     * Token storage
     *
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * Annotation reader
     *
     * @var AnnotationInterface
     */
    protected $reader;

    /**
     * Entity manager
     *
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Classes meta-configs
     *
     * @var array
     */
    protected $configs;

    /**
     * AuditableListener constructor.
     *
     * @param TokenStorageInterface $tokenStorage Token storage
     * @param AnnotationInterface   $reader       Annotation reader
     */
    public function __construct(TokenStorageInterface $tokenStorage, AnnotationInterface $reader)
    {
        $this->reader       = $reader;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'onFlush'
        ];
    }

    /**
     * Looks for auditable objects for further processing
     *
     * @param OnFlushEventArgs $eventArgs Event arguments
     *
     * @return void
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->entityManager = $eventArgs->getEntityManager();
        $uow                 = $this->entityManager->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->createLogEntry($entity);
        }
    }

    /**
     * Creates log entry
     *
     * @param object $entity Doctrine entity
     *
     * @return void
     */
    protected function createLogEntry($entity)
    {
        $class = get_class($entity);
        $meta  = $this->entityManager->getClassMetadata($class);

        // Filter embedded documents
        if (isset($meta->isEmbeddedDocument) && $meta->isEmbeddedDocument) {
            return;
        }

        if ($config = $this->reader->read($class)) {
            $uow       = $this->entityManager->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($entity);

            $columnsChangeSet = $this->getColumnsChangeSet($meta, $changeSet);
            $toOneChangeSet   = $this->getToOneChangeSet($meta, $changeSet);

            $affectedAuditableColumns      = array_intersect(array_keys($columnsChangeSet), $config['columns']);
            $affectedAuditableAssociations = array_intersect(array_keys($toOneChangeSet), $config['columns']);

            if (count($affectedAuditableColumns) > 0 || count($affectedAuditableAssociations) > 0) {

                $comment = null;
                if (isset($config['commentProperty'])) {
                    $accessor = PropertyAccess::createPropertyAccessor();
                    $comment  = $accessor->getValue($entity, $config['commentProperty']);
                    $accessor->setValue($entity, $config['commentProperty'], null);
                }

                /** @var Group $group */
                $group = $this->createGroup(
                    new DateTime,
                    $this->getUsername(),
                    $meta->name,
                    $this->readEntityId($entity),
                    $comment
                );
                $this->entityManager->persist($group);

                // If you create and persist a new entity in onFlush, then calling EntityManager#persist() is not enough
                // You have to execute an additional call to $unitOfWork->computeChangeSet($classMetadata, $entity)
                // @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#onflush
                $groupClassMetadata = $this->entityManager->getClassMetadata(get_class($group));
                $uow->computeChangeSet($groupClassMetadata, $group);

                foreach ($affectedAuditableColumns as $column) {
                    $type = $meta->getTypeOfField($column);
                    if (is_string($type)) {
                        $type = Type::getType($type);
                    }

                    $valueBefore = $columnsChangeSet[$column][0];
                    $valueAfter  = $columnsChangeSet[$column][1];

                    if ($type instanceof DateTimeType) {
                        $valueBefore = is_null($valueBefore) ? null : $valueBefore->format(self::DATETIME_WITH_TIMEZONE_FORMAT);
                        $valueAfter  = is_null($valueAfter) ? null : $valueAfter->format(self::DATETIME_WITH_TIMEZONE_FORMAT);
                    } elseif ($type instanceof Type) {
                        $platform    = $this->entityManager->getConnection()->getDatabasePlatform();
                        $valueBefore = $type->convertToDatabaseValue($valueBefore, $platform);
                        $valueAfter  = $type->convertToDatabaseValue($valueAfter, $platform);
                    } else {
                        $valueBefore = is_null($valueBefore) ? null : (string) $valueBefore;
                        $valueAfter  = is_null($valueAfter) ? null : (string) $valueAfter;
                    }

                    /** @var Entry $entry */
                    $entry = $this->createEntry($group, $column, false, $valueBefore, $valueAfter);
                    $this->entityManager->persist($entry);

                    $entryClassMetadata = $this->entityManager->getClassMetadata(get_class($entry));
                    $uow->computeChangeSet($entryClassMetadata, $entry);
                }

                foreach ($affectedAuditableAssociations as $association) {
                    $before       = $toOneChangeSet[$association][0];
                    $after        = $toOneChangeSet[$association][1];
                    $valueBefore  = $this->readEntityId($before);
                    $valueAfter   = $this->readEntityId($after);
                    $stringBefore = $this->getEntityStringRepresentation($before);
                    $stringAfter  = $this->getEntityStringRepresentation($after);

                    /** @var Entry $entry */
                    $entry = $this->createEntry(
                        $group, $association, true, $valueBefore, $valueAfter, $stringBefore, $stringAfter
                    );
                    $this->entityManager->persist($entry);

                    $entryClassMetadata = $this->entityManager->getClassMetadata(get_class($entry));
                    $uow->computeChangeSet($entryClassMetadata, $entry);
                }
            }
        }
    }

    /**
     * Return changSet only for columns
     *
     * @param ClassMetadataInfo $meta      Class metadata
     * @param array             $changeSet ChangeSet
     *
     * @return array
     */
    protected function getColumnsChangeSet(ClassMetadataInfo $meta, array $changeSet)
    {
        $filtered = [];

        foreach ($changeSet as $name => $change) {
            if (array_key_exists($name, $meta->fieldMappings)) {
                $filtered[$name] = $change;
            }
        }

        return $filtered;
    }

    /**
     * Return changSet only for toOne associations from owning side
     *
     * @param ClassMetadataInfo $meta      Class metadata
     * @param array             $changeSet ChangeSet
     *
     * @return array
     */
    protected function getToOneChangeSet(ClassMetadataInfo $meta, array $changeSet)
    {
        $filtered = [];

        foreach ($changeSet as $name => $change) {
            if (array_key_exists($name, $meta->associationMappings)) {
                $assocMeta = $meta->associationMappings[$name];
                if ($assocMeta['isOwningSide'] && ($assocMeta['type'] & ClassMetadataInfo::TO_ONE)) {
                    $filtered[$name] = $change;
                }
            }
        }

        return $filtered;
    }

    /**
     * Try to get entity string representation (via __toString)
     *
     * @param object $entity Entity
     *
     * @return null|string
     */
    protected function getEntityStringRepresentation($entity)
    {
        if (method_exists($entity, '__toString')) {
            return (string) $entity;
        }

        return null;
    }

    /**
     * Read entity single ID value
     *
     * @param object|null $entity Entity
     *
     * @return mixed
     */
    protected function readEntityId($entity = null)
    {
        if ($entity === null) {
            return null;
        }

        $meta   = $this->entityManager->getClassMetadata(get_class($entity));
        $values = $meta->getIdentifierValues($entity);

        return array_shift($values);
    }

    /**
     * Create group instance
     *
     * @param DateTimeInterface $createdTs   Crated timestamp
     * @param string            $username    Username
     * @param string            $entityClass Entity class name
     * @param string            $entityId    Entity ID
     * @param string            $comment     ChangeSet comment
     *
     * @return Group
     */
    protected function createGroup(DateTimeInterface $createdTs, $username, $entityClass, $entityId, $comment)
    {
        return new Group($createdTs, $username, $entityClass, $entityId, $comment);
    }

    /**
     * Create entry instance
     *
     * @param GroupSuperClass $group               ChangeSet group
     * @param string          $entityColumn        Entity column name
     * @param boolean         $association         Column represents association
     * @param string          $valueBefore         Value before
     * @param string          $valueAfter          Value after
     * @param string          $relatedStringBefore Related entity string representation before update (if possible)
     * @param string          $relatedStringAfter  Related entity string representation after update (if possible)
     *
     * @return Entry
     */
    protected function createEntry($group,
                                   $entityColumn,
                                   $association,
                                   $valueBefore,
                                   $valueAfter,
                                   $relatedStringBefore = null,
                                   $relatedStringAfter = null)
    {
        return new Entry(
            $group,
            $entityColumn,
            $association,
            $valueBefore,
            $valueAfter,
            $relatedStringBefore,
            $relatedStringAfter
        );
    }

    /**
     * Get current username
     *
     * @return string|null
     */
    protected function getUsername()
    {
        if (null !== $token = $this->tokenStorage->getToken()) {
            return $token->getUsername();
        }

        return null;
    }

    /**
     * Get the configuration for specific object class
     * if cache driver is present it scans it also
     *
     * @param ClassMetadataInfo $meta Class metadata
     *
     * @return array
     */
    protected function getClassConfiguration($meta)
    {
        if (isset($this->configs[$meta->name])) {
            return $this->configs[$meta->name];
        }

        $factory = $this->entityManager->getMetadataFactory();
        if (null !== $cacheDriver = $factory->getCacheDriver()) {
            $cacheId = $this->generateCacheId($meta->name);

            if (false === $config = $cacheDriver->fetch($cacheId)) {
                $this->configs[$meta->name] = $this->reader->read($meta);

                return $this->configs[$meta->name];
            }
        }

        // Re-fetch metadata on cache miss
        $this->configs[$meta->name] = $this->reader->read($meta);

        return $this->configs[$meta->name];
    }

}
