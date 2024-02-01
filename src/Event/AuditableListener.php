<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Event;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_shift;
use function assert;
use function count;
use function get_class;
use function is_file;
use function is_string;
use function method_exists;
use function spl_object_hash;

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
     * Entity manager
     */
    protected EntityManagerInterface $entityManager;

    /**
     * Classes meta-configs
     */
    private array $configs = [];

    /**
     * Store of change log of entity
     */
    private array $logStore = [];

    /**
     * AuditableListener constructor.
     *
     * @param Store                      $store        Auditable store
     * @param TokenStorageInterface|null $tokenStorage Token storage
     * @param string                     $cacheDir     Cache directory containing warmed up auditable metadata
     */
    final public function __construct(
        protected Store $store,
        protected ?TokenStorageInterface $tokenStorage,
        private readonly string $cacheDir
    ) {
    }

    /**
     * Looks for auditable objects for further processing
     *
     * @param OnFlushEventArgs $eventArgs Event arguments
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $this->entityManager = $eventArgs->getObjectManager();
        $uow                 = $this->entityManager->getUnitOfWork();
        $this->logStore      = $this->store->pop($this->entityManager);

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
            $entityHash = spl_object_hash($entity);
            $comment    = $this->logStore[$entityHash] ?? null;

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
                    $valueBefore = $valueBefore?->format(self::DATETIME_WITH_TIMEZONE_FORMAT);
                    $valueAfter  = $valueAfter?->format(self::DATETIME_WITH_TIMEZONE_FORMAT);
                } elseif ($type instanceof Type) {
                    $platform = $this->entityManager->getConnection()->getDatabasePlatform();
                    assert($platform instanceof AbstractPlatform);
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
        assert(
            $group instanceof Group,
            'Method ' . __METHOD__ . ' should be override when ' . __CLASS__ . '::createGroup is override'
        );

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
     */
    protected function getUsername(): ?string
    {
        return $this->tokenStorage?->getToken()?->getUserIdentifier();
    }

    /**
     * Get the configuration for specific object class
     * if cache driver is present it scans it also
     *
     * @param ClassMetadataInfo $meta Class metadata
     *
     * @throws InvalidMappingException
     */
    private function getClassConfiguration(ClassMetadataInfo $meta): array
    {
        if (!isset($this->configs[$meta->name])) {
            $configEntry = $this->cacheDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, ltrim($meta->name, '\\')) . '.php';

            $this->configs[$meta->name] = is_file($configEntry)
                ? include $configEntry
                : [];
        }

        return $this->configs[$meta->name];
    }
}
