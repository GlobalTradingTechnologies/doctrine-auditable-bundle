<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * ChangeSet entry
 */
#[MappedSuperclass]
class EntrySuperClass
{
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    #[Column(type: Types::BIGINT, options: ['unsigned' => true])]
    protected int $id;

    #[Column(name: 'entity_column', type: Types::STRING, length: 255)]
    protected string $entityColumn;

    /**
     * Column represents association
     */
    #[Column(name: 'is_association', type: Types::BOOLEAN)]
    protected bool $association = false;

    #[Column(name: 'value_before', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $valueBefore;

    #[Column(name: 'value_after', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $valueAfter;

    /**
     * Related entity string representation before update (if possible)
     */
    #[Column(name: 'related_string_before', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $relatedStringBefore;

    /**
     * Related entity string representation after update (if possible)
     */
    #[Column(name: 'related_string_after', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $relatedStringAfter;

    /**
     * @param string      $entityColumn        Entity column name
     * @param boolean     $association         Column represents association
     * @param string|null $valueBefore         Value before
     * @param string|null $valueAfter          Value after
     * @param string|null $relatedStringBefore Related entity string representation before update (if possible)
     * @param string|null $relatedStringAfter  Related entity string representation after update (if possible)
     */
    public function __construct(
        string $entityColumn,
        bool $association,
        ?string $valueBefore,
        ?string $valueAfter,
        string $relatedStringBefore = null,
        string $relatedStringAfter = null
    ) {
        $this->entityColumn        = $entityColumn;
        $this->association         = $association;
        $this->valueBefore         = $valueBefore;
        $this->valueAfter          = $valueAfter;
        $this->relatedStringBefore = $relatedStringBefore;
        $this->relatedStringAfter  = $relatedStringAfter;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get entity column name
     */
    public function getEntityColumn(): string
    {
        return $this->entityColumn;
    }

    public function getAssociation(): bool
    {
        return $this->association;
    }

    public function getValueBefore(): ?string
    {
        return $this->valueBefore;
    }

    public function getValueAfter(): ?string
    {
        return $this->valueAfter;
    }

    /**
     * Get related entity string representation before change
     */
    public function getRelatedStringBefore(): ?string
    {
        return $this->relatedStringBefore;
    }

    /**
     * Get related entity string representation after change
     */
    public function getRelatedStringAfter(): ?string
    {
        return $this->relatedStringAfter;
    }
}
