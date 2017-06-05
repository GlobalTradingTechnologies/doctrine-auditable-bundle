<?php
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAdapterBundle\Entity;

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
    protected $entityColumn;

    /**
     * Column represents association
     *
     * @var boolean
     *
     * @ORM\Column(name="is_association", type="boolean")
     */
    protected $association = false;

    /**
     * Value before
     *
     * @var mixed
     *
     * @ORM\Column(name="value_before", type="string", length=255)
     */
    protected $valueBefore;

    /**
     * Value after
     *
     * @var mixed
     *
     * @ORM\Column(name="value_after", type="string", length=255)
     */
    protected $valueAfter;

    /**
     * Related entity string representation before update (if possible)
     *
     * @var string
     *
     * @ORM\Column(name="related_string_before", type="string", length=255)
     */
    protected $relatedStringBefore;

    /**
     * Related entity string representation after update (if possible)
     *
     * @var string
     *
     * @ORM\Column(name="related_string_after", type="string", length=255)
     */
    protected $relatedStringAfter;

    /**
     * Entry constructor.
     *
     * @param string  $entityColumn        Entity column name
     * @param boolean $association         Column represents association
     * @param string  $valueBefore         Value before
     * @param string  $valueAfter          Value after
     * @param string  $relatedStringBefore Related entity string representation before update (if possible)
     * @param string  $relatedStringAfter  Related entity string representation after update (if possible)
     */
    public function __construct($entityColumn,
                                $association,
                                $valueBefore,
                                $valueAfter,
                                $relatedStringBefore = null,
                                $relatedStringAfter = null)
    {
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get entity column name
     *
     * @return string
     */
    public function getEntityColumn()
    {
        return $this->entityColumn;
    }

    /**
     * Get association
     *
     * @return bool
     */
    public function getAssociation()
    {
        return $this->association;
    }

    /**
     * Get value before
     *
     * @return string
     */
    public function getValueBefore()
    {
        return $this->valueBefore;
    }

    /**
     * Get value after
     *
     * @return string
     */
    public function getValueAfter()
    {
        return $this->valueAfter;
    }

    /**
     * Get related entity string representation before change
     *
     * @return string
     */
    public function getRelatedStringBefore()
    {
        return $this->relatedStringBefore;
    }

    /**
     * Get related entity string representation after change
     *
     * @return string
     */
    public function getRelatedStringAfter()
    {
        return $this->relatedStringAfter;
    }
}
