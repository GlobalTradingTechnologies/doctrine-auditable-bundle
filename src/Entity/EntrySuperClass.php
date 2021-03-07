<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ChangeSet entry
 *
 * @ORM\MappedSuperclass
 */
class EntrySuperClass
{
    /**
     * ID
     *
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Entity column name
     *
     * @var string
     *
     * @ORM\Column(name="entity_column", type="string", length=255)
     */
    protected string $entityColumn;

    /**
     * Column represents association
     *
     * @var boolean
     *
     * @ORM\Column(name="is_association", type="boolean")
     */
    protected bool $association = false;

    /**
     * Value before
     *
     * @ORM\Column(name="value_before", type="string", length=255, nullable=true)
     */
    protected ?string $valueBefore;

    /**
     * Value after
     *
     * @ORM\Column(name="value_after", type="string", length=255, nullable=true)
     */
    protected ?string $valueAfter;

    /**
     * Related entity string representation before update (if possible)
     *
     * @ORM\Column(name="related_string_before", type="string", length=255, nullable=true)
     */
    protected ?string $relatedStringBefore;

    /**
     * Related entity string representation after update (if possible)
     *
     * @ORM\Column(name="related_string_after", type="string", length=255, nullable=true)
     */
    protected ?string $relatedStringAfter;

    /**
     * Entry constructor.
     *
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

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get entity column name
     *
     * @return string
     */
    public function getEntityColumn(): string
    {
        return $this->entityColumn;
    }

    /**
     * Get association
     *
     * @return bool
     */
    public function getAssociation(): bool
    {
        return $this->association;
    }

    /**
     * Get value before
     *
     * @return string|null
     */
    public function getValueBefore(): ?string
    {
        return $this->valueBefore;
    }

    /**
     * Get value after
     *
     * @return string|null
     */
    public function getValueAfter(): ?string
    {
        return $this->valueAfter;
    }

    /**
     * Get related entity string representation before change
     *
     * @return string
     */
    public function getRelatedStringBefore(): ?string
    {
        return $this->relatedStringBefore;
    }

    /**
     * Get related entity string representation after change
     *
     * @return string
     */
    public function getRelatedStringAfter(): ?string
    {
        return $this->relatedStringAfter;
    }
}
