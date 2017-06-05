<?php
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAdapterBundle\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Annotation for auditable entity
 *
 * @Annotation
 * @Target("CLASS")
 *
 * @author Pavel.Levin
 */
final class Entity extends Annotation
{
    /**
     * Property with comment for changeSet
     *
     * @var string
     */
    public $commentProperty;
}
