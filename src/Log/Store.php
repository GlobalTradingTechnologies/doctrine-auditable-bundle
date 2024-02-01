<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Log;

use Doctrine\Bundle\DoctrineBundle as Doctrine;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Gtt\Bundle\DoctrineAuditableBundle\Exception;

use function array_key_exists;
use function get_class;
use function spl_object_hash;

/**
 * Collects entities changes' description to be saved as changelog description for managed entities by auditable
 *
 * This is cleaner alternative for retrieving changelog comment from entity (
 * because it's application layer violation)
 */
final class Store
{
    /**
     * Stack of entities' changes description
     *
     * @example [
     *             'entityManager_spl_object_hash' => [
     *               'entity_spl_object_hash' => 'changelog comment'
     *             ]
     *          ]
     */
    private array $store = [];

    public function __construct(
        private readonly Doctrine\Registry $doctrineRegistry
    ) {
    }

    /**
     * Describes entity changes
     *
     * @param object                 $entity        Entity
     * @param string                 $comment       Comment that describes given entity changes
     *
     * @throws Exception\NoEntityManagerFoundException When no related entity manager found for given entity
     */
    public function describe(object $entity, string $comment): void
    {
        if (empty($comment)) {
            return;
        }

        $entityClass   = get_class($entity);
        $entityManager = $this->doctrineRegistry->getManagerForClass($entityClass);

        if ($entityManager === null) {
            throw new Exception\NoEntityManagerFoundException(
                "No related EntityManager was found for given entity class `{$entityClass}`"
            );
        }

        $entityManagerHash = spl_object_hash($entityManager);
        $entityHash        = spl_object_hash($entity);

        if (!array_key_exists($entityManagerHash, $this->store)) {
            $this->store[$entityManagerHash] = [];
        }

        // there could be additional check `$entityManager->contains()`, but at this point it's unnecessary overhead

        $this->store[$entityManagerHash][$entityHash] = $comment;
    }

    /**
     * Returns accumulated entities' changes descriptions
     *
     * @param EntityManagerInterface|null $entityManager
     *
     * @return array [[ 'entity_spl_object_hash' => 'changelog comment' ]]
     *
     * @internal
     */
    public function pop(EntityManagerInterface $entityManager = null): array
    {
        $entityManagerHash = spl_object_hash($entityManager ?? $this->doctrineRegistry->getManager());
        if (!array_key_exists($entityManagerHash, $this->store)) {
            return [];
        }

        $store = $this->store[$entityManagerHash];
        unset($this->store[$entityManagerHash]);

        return $store;
    }

    /**
     * Performs stack clean up, because stack can not live after changes flush
     *
     * @internal
     */
    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager     = $event->getObjectManager();
        $entityManagerHash = spl_object_hash($entityManager);
        unset($this->store[$entityManagerHash]);  // clean up expired stack
    }
}
