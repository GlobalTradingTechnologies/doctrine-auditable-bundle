<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * ChangeSet group
 */
#[MappedSuperclass]
class GroupSuperClass
{
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    #[Column(type: Types::INTEGER)]
    protected int $id;

    /**
     * Date (time when change was registered in the system)
     */
    #[Column(name: 'created_ts', type: Types::DATETIMETZ_IMMUTABLE, columnDefinition: 'TIMESTAMP NULL DEFAULT NULL')]
    protected DateTimeImmutable $createdTs;

    #[Column(name: 'username', type: Types::STRING, nullable: true)]
    protected ?string $username;

    #[Column(name: 'entity_class', type: Types::STRING, length: 255)]
    protected string $entityClass;

    #[Column(name: 'entity_id', type: Types::STRING, length: 255)]
    protected string $entityId;

    #[Column(type: Types::TEXT, nullable: true)]
    protected ?string $comment;

    public function __construct(
        DateTimeImmutable $createdTs,
        ?string $username,
        string $entityClass,
        string $entityId,
        ?string $comment
    ) {
        $this->createdTs   = $createdTs;
        $this->username    = $username;
        $this->entityClass = $entityClass;
        $this->entityId    = $entityId;
        $this->comment     = $comment;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get created timestamp
     */
    public function getCreatedTs(): DateTimeImmutable
    {
        return $this->createdTs;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
