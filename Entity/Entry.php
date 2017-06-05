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
 * @ORM\Entity
 * @ORM\Table(
 *  name="doctrine_auditable_entry",
 *  indexes={
 *      @ORM\Index(name="ix_doctrine_auditable_entry_entity_column_is_association", columns={"entity_column", "is_association"}),
 *      @ORM\Index(name="ix_doctrine_auditable_entry_value_before", columns={"value_before"}),
 *      @ORM\Index(name="ix_doctrine_auditable_entry_value_after", columns={"value_after"})
 *  }
 * )
 *
 * @author Pavel.Levin
 */
class Entry extends EntrySuperClass
{
    /**
     * ChangeSet group
     *
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="Group")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     */
    protected $group;

    /**
     * {@inheritdoc}
     */
    public function __construct(Group $group,
                                $entityColumn,
                                $association,
                                $valueBefore,
                                $valueAfter,
                                $relatedStringBefore = null,
                                $relatedStringAfter = null)
    {
        parent::__construct(
            $entityColumn,
            $association,
            $valueBefore,
            $valueAfter,
            $relatedStringBefore,
            $relatedStringAfter
        );

        $this->group = $group;
    }

    /**
     * Get group
     *
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }
}
