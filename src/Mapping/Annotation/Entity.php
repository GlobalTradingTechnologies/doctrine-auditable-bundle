<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Gtt\Bundle\DoctrineAuditableBundle\Logger\Store;

/**
 * Annotation for auditable entity
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @deprecated This property forces your application domain entity know about this bundle,
 *             (it's application layer violation). Please consider to avoid using this API
 *             in favor for {@see Store} API.
 *
 * @author Pavel.Levin
 */
final class Entity extends Annotation
{
    /**
     * Property with comment for changeSet
     *
     * @deprecated see class \@depracated description
     *
     * @var string
     */
    public $commentProperty;
}
