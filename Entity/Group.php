<?php
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAdapterBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ChangeSet group
 *
 * @ORM\Entity
 * @ORM\Table(
 *  name="doctrine_auditable_group",
 *  indexes={
 *      @ORM\Index(name="ix_doctrine_auditable_group_created_ts", columns={"created_ts"}),
 *      @ORM\Index(name="ix_doctrine_auditable_group_entity_class_entity_id", columns={"entity_class", "entity_id"}),
 *      @ORM\Index(name="ix_doctrine_auditable_group_user_name", columns={"username"})
 *  }
 * )
 *
 * @author Pavel.Levin
 */
class Group extends GroupSuperClass
{
    /**
     * Group entries
     *
     * @var Entry[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Entry", mappedBy="group")
     */
    private $entries;

    /**
     * Related entries
     *
     * @return ArrayCollection|Entry[]
     */
    public function getEntries()
    {
        return $this->entries;
    }
}
