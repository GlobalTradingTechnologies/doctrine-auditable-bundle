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
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

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
#[Entity]
#[Table(name: 'doctrine_auditable_group')]
#[Index(columns: ['created_ts'], name: 'ix_doctrine_auditable_group_created_ts')]
#[Index(columns: ['entity_class', 'entity_id'], name: 'ix_doctrine_auditable_group_entity_class_entity_id')]
#[Index(columns: ['username'], name: 'ix_doctrine_auditable_group_user_name')]
class Group extends GroupSuperClass
{
    /**
     * Group entries
     *
     * @var Collection<int, Entry>
     *
     * @ORM\OneToMany(targetEntity="Entry", mappedBy="group")
     */
    #[OneToMany(mappedBy: 'group', targetEntity: Entry::class)]
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
     * @return iterable<Entry>
     */
    public function getEntries(): iterable
    {
        return $this->entries;
    }
}
