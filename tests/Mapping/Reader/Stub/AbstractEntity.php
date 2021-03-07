<?php

declare(strict_types=1);

/*
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\Stub;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class AbstractEntity
 */
abstract class AbstractEntity
{
    public int $id;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setIdentifier(['id']);
    }
}
