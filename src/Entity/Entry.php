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
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

/**
 * ChangeSet entry
 *
 * @author Pavel.Levin
 */
#[Entity]
#[Table(name: 'doctrine_auditable_entry')]
#[Index(columns: ['entity_column', 'is_association'], name: 'ix_doctrine_auditable_entry_entity_column_is_association')]
#[Index(columns: ['value_before'], name: 'ix_doctrine_auditable_entry_value_before')]
#[Index(columns: ['value_after'], name: 'ix_doctrine_auditable_entry_value_after')]
class Entry extends EntrySuperClass
{
    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id')]
    protected Group $group;

    /**
     * @inheritDoc
     */
    public function __construct(
        Group $group,
        string $entityColumn,
        bool $association,
        ?string $valueBefore,
        ?string $valueAfter,
        string $relatedStringBefore = null,
        string $relatedStringAfter = null
    ) {
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

    public function getGroup(): Group
    {
        return $this->group;
    }
}
