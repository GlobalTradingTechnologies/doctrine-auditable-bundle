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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @var Collection<Entry>
     *
     * @ORM\OneToMany(targetEntity="Entry", mappedBy="group")
     */
    private Collection $entries;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        DateTimeImmutable $createdTs,
        ?string $username,
        string $entityClass,
        string $entityId,
        ?string $comment
    ) {
        parent::__construct($createdTs, $username, $entityClass, $entityId, $comment);
        $this->entries = new ArrayCollection();
    }


    /**
     * Related entries
     *
     * @return ArrayCollection|Entry[]
     */
    public function getEntries(): iterable
    {
        return $this->entries;
    }
}
