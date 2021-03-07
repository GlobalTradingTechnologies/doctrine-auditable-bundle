<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader;

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
     */
    public function read(string $class): array;
}
