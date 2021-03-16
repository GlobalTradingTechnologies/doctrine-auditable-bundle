<?php
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Event;

use DateTimeImmutable;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateTimeTzType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gtt\Bundle\DoctrineAuditableBundle\Entity\Entry;
use Gtt\Bundle\DoctrineAuditableBundle\Entity\EntrySuperClass;
use Gtt\Bundle\DoctrineAuditableBundle\Entity\Group;
use Gtt\Bundle\DoctrineAuditableBundle\Entity\GroupSuperClass;
use Gtt\Bundle\DoctrineAuditableBundle\Exception\InvalidMappingException;
use Gtt\Bundle\DoctrineAuditableBundle\Log\Store;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\AnnotationInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_merge_recursive;
use function array_reverse;
use function array_shift;
use function array_values;
use function count;
use function get_class;
use function is_string;
use function method_exists;
use function spl_object_hash;
use function str_replace;

/**
 * Auditable subscriber
 */
class AuditableListener
{
    /**
     * Datetime with timezone format (ISO 8601)
     */
    private const DATETIME_WITH_TIMEZONE_FORMAT = 'c';

    /**
     * Token storage
     */
    protected ?TokenStorageInterface $tokenStorage;

    /**
     * Annotation reader
     */
    protected AnnotationInterface $reader;

    /**
     * Entity manager
     */
    protected EntityManagerInterface $entityManager;

    /**
     * Classes meta-configs
     */
    private array $configs = [];

    protected Store $store;

    /**
     * Store of change log of entity
     *
     * @var array
     */
    private array $logStore = [];

    /**
     * AuditableListener constructor.
     *
     * @param Store                      $store        Auditable store
     * @param TokenStorageInterface|null $tokenStorage Token storage
     * @param AnnotationInterface        $reader       Annotation reader
     */
    public function __construct(Store $store, ?TokenStorageInterface $tokenStorage, AnnotationInterface $reader)
    {
        $this->store        = $store;
        $this->reader       = $reader;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Looks for auditable objects for further processing
     *
     * @param OnFlushEventArgs $eventArgs Event arguments
     *
     * @return void
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $this->entityManager = $eventArgs->getEntityManager();
        $uow                 = $this->entityManager->getUnitOfWork();

        if ($this->store !== null) {
            $this->logStore = $this->store->pop($this->entityManager);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->createLogEntry($entity);
        }

        $this->logStore = [];
    }

    /**
     * Creates log entry
     *
     * @param object $entity Doctrine entity
     */
    protected function createLogEntry(object $entity): void
    {
        $class = get_class($entity);
        $meta  = $this->entityManager->getClassMetadata($class);

        // Filter embedded documents
        if (isset($meta->isEmbeddedDocument) && $meta->isEmbeddedDocument) {
            return;
        }

        $config = $this->getClassConfiguration($meta);  // todo consider to move entity auditable configuration outside entity, to decrease coupling
        if (empty($config)) {
            return;
        }

        $uow       = $this->entityManager->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($entity);

        $columnsChangeSet = $this->getColumnsChangeSet($meta, $changeSet);
        $toOneChangeSet   = $this->getToOneChangeSet($meta, $changeSet);

        $affectedAuditableColumns      = array_intersect(array_keys($columnsChangeSet), $config['columns']);
        $affectedAuditableAssociations = array_intersect(array_keys($toOneChangeSet), $config['columns']);

        if (count($affectedAuditableColumns) > 0 || count($affectedAuditableAssociations) > 0) {
            $comment = null;
            if ($this->store !== null) {
                $entityHash  = spl_object_hash($entity);
                if (array_key_exists($entityHash, $this->logStore)) {
                    $comment_ = $this->logStore[$entityHash];
                    $comment  = $comment_ ?? $comment;  // let this strategy override comment retrieved by old strategy
                }
            }

            $group = $this->createGroup(
                new DateTimeImmutable(),
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

                [$valueBefore, $valueAfter] = $columnsChangeSet[$column];

                if ($type instanceof DateTimeType || $type instanceof DateTimeTzType) {
                    $valueBefore = $valueBefore === null ? null : $valueBefore->format(self::DATETIME_WITH_TIMEZONE_FORMAT);
                    $valueAfter  = $valueBefore === null ? null : $valueAfter->format(self::DATETIME_WITH_TIMEZONE_FORMAT);
                } elseif ($type instanceof Type) {
                    $platform    = $this->entityManager->getConnection()->getDatabasePlatform();
                    $valueBefore = $type->convertToDatabaseValue($valueBefore, $platform);
                    $valueAfter  = $type->convertToDatabaseValue($valueAfter, $platform);
                }

                $valueBefore = $valueBefore === null ? null : (string) $valueBefore;
                $valueAfter  = $valueAfter === null ? null : (string) $valueAfter;

                $entry = $this->createEntry($group, $column, false, $valueBefore, $valueAfter);
                $this->entityManager->persist($entry);

                $entryClassMetadata = $this->entityManager->getClassMetadata(get_class($entry));
                $uow->computeChangeSet($entryClassMetadata, $entry);
            }

            foreach ($affectedAuditableAssociations as $association) {
                [$before, $after] = $toOneChangeSet[$association];

                $valueBefore  = $this->readEntityId($before);
                $valueAfter   = $this->readEntityId($after);
                $stringBefore = $this->getEntityStringRepresentation($before);
                $stringAfter  = $this->getEntityStringRepresentation($after);

                $entry = $this->createEntry(
                    $group,
                    $association,
                    true,
                    $valueBefore,
                    $valueAfter,
                    $stringBefore,
                    $stringAfter
                );
                $this->entityManager->persist($entry);

                $entryClassMetadata = $this->entityManager->getClassMetadata(get_class($entry));
                $uow->computeChangeSet($entryClassMetadata, $entry);
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
    protected function getColumnsChangeSet(ClassMetadataInfo $meta, array $changeSet): array
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
    protected function getToOneChangeSet(ClassMetadataInfo $meta, array $changeSet): array
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
    protected function getEntityStringRepresentation(object $entity): ?string
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
     * @return string|null
     */
    protected function readEntityId(object $entity = null): ?string
    {
        if ($entity === null) {
            return null;
        }

        $meta   = $this->entityManager->getClassMetadata(get_class($entity));
        $values = $meta->getIdentifierValues($entity);

        return (string) array_shift($values);
    }

    /**
     * Create group instance
     *
     * @param DateTimeImmutable $createdTs   Crated timestamp
     * @param string|null       $username    Username
     * @param string            $entityClass Entity class name
     * @param string            $entityId    Entity ID
     * @param string|null       $comment     ChangeSet comment
     *
     * @return Group
     */
    protected function createGroup(
        DateTimeImmutable $createdTs,
        ?string $username,
        string $entityClass,
        string $entityId,
        ?string $comment
    ): GroupSuperClass {
        return new Group($createdTs, $username, $entityClass, $entityId, $comment);
    }

    /**
     * Create entry instance
     *
     * @param GroupSuperClass $group               ChangeSet group
     * @param string          $entityColumn        Entity column name
     * @param boolean         $association         Column represents association
     * @param string|null     $valueBefore         Value before
     * @param string|null     $valueAfter          Value after
     * @param string|null     $relatedStringBefore Related entity string representation before update (if possible)
     * @param string|null     $relatedStringAfter  Related entity string representation after update (if possible)
     *
     * @return Entry
     */
    protected function createEntry(
        GroupSuperClass $group,
        string $entityColumn,
        bool $association,
        ?string $valueBefore,
        ?string $valueAfter,
        string $relatedStringBefore = null,
        string $relatedStringAfter = null
    ): EntrySuperClass {
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
    protected function getUsername(): ?string
    {
        if ($this->tokenStorage === null) {
            return null;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        return $token->getUsername();
    }

    /**
     * Get the configuration for specific object class
     * if cache driver is present it scans it also
     *
     * @param ClassMetadataInfo $meta Class metadata
     *
     * @throws InvalidMappingException
     *
     * @return array
     */
    private function getClassConfiguration(ClassMetadataInfo $meta): array
    {
        if (isset($this->configs[$meta->name])) {
            return $this->configs[$meta->name];
        }

        $factory     = $this->entityManager->getMetadataFactory();
        $cacheDriver = $factory->getCacheDriver();
        if ($cacheDriver !== null) {
            $cacheId = $this->generateCacheId($meta->name);

            $config = $cacheDriver->fetch($cacheId);
            if ($config !== false) {
                $this->configs[$meta->name] = $this->getAuditableSettings($meta);

                return $this->configs[$meta->name];
            }
        }

        // Re-fetch metadata on cache miss
        $this->configs[$meta->name] = $this->getAuditableSettings($meta);

        return $this->configs[$meta->name];
    }

    /**
     * Build class configuration
     *
     * @param ClassMetadataInfo $meta Class metadata
     *
     * @throws InvalidMappingException
     *
     * @return array
     */
    private function getAuditableSettings(ClassMetadataInfo $meta): array
    {
        // if class has no parent, just return its configuration
        // or read configuration for each parent class in inheritance chain,
        // validate, merge them and return otherwise
        if (empty($meta->parentClasses)) {
            return $this->reader->read($meta->name);
        }

        $configurationToMergeMap = [$meta->name => $this->reader->read($meta->name)];
        // 2. Retrieve all parent classes configuration
        foreach (array_reverse($meta->parentClasses) as $parentClass) {  // from root to direct parent
            $configurationToMergeMap[$parentClass] = $this->reader->read($parentClass);
        }

        // 3. Build final class configuration: perform configuration inheritance
        $configurationToMergeList = array_values($configurationToMergeMap);
        return array_merge_recursive(...$configurationToMergeList);
    }


    /**
     * Generate the cache id
     *
     * @param string $class Entity class name
     *
     * @return string
     */
    private function generateCacheId(string $class): string
    {
        return str_replace('\\', '_', __CLASS__ . ' - ' . $class);
    }
}
