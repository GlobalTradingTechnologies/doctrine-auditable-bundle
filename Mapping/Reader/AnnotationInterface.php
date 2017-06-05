<?php
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAdapterBundle\Mapping\Reader;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Befooz\Bundle\DoctrineAdapterBundle\Exception\InvalidMappingException;
use Befooz\Bundle\DoctrineAdapterBundle\Mapping\Annotation\Entity;
use Befooz\Bundle\DoctrineAdapterBundle\Mapping\Annotation\Property;

/**
 * This is auditable annotation mapping reader interface
 */
interface AnnotationInterface
{
    /**
     * Read auditable metadata
     *
     * @param string $class Entity class name
     *
     * @return array Config
     *
     * @throws InvalidMappingException
     */
    public function read($class);
}
