<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\Stub;

use Doctrine\ORM\Mapping\ClassMetadata;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Attribute as Auditable;

#[Auditable\Entity]
class Php80CompositeIdentifierEntity
{
    public int $idOne;

    public int $idTwo;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setIdentifier(['idOne', 'idTwo']);
    }
}
