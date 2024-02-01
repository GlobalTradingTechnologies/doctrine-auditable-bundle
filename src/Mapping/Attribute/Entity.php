<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Mapping\Attribute;

use Attribute;

/**
 * Attribute for auditable entity
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Entity
{
}