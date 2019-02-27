<?php
declare(strict_types=1);
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAuditableBundle\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Annotation for auditable entity
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Property extends Annotation
{
}
