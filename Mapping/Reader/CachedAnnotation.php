<?php
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAdapterBundle\Mapping\Reader;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gtt\Bundle\DoctrineAdapterBundle\Exception\InvalidMappingException;
use Gtt\Bundle\DoctrineAdapterBundle\Mapping\Annotation\Entity;
use Gtt\Bundle\DoctrineAdapterBundle\Mapping\Annotation\Property;

/**
 * This is caching decorator for annotation reader
 */
class CachedAnnotation implements AnnotationInterface
{
    /**
     * Classes meta-configs
     *
     * @var array
     */
    protected $configs;

    /**
     * Annotation reader instance
     *
     * @var Annotation
     */
    protected $reader;

    /**
     * Entity managers registry
     *
     * @var Registry
     */
    protected $registry;

    /**
     * Annotation constructor.
     *
     * @param Reader   $reader   Annotation reader
     * @param Registry $registry Entity managers registry
     */
    public function __construct(Annotation $reader, Registry $registry)
    {
        $this->reader   = $reader;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function read($class)
    {
        if (isset($this->configs[$class])) {
            return $this->configs[$class];
        }

        $entityManager = $this->registry->getEntityManagerForClass($class);
        $factory       = $entityManager->getMetadataFactory();
        if (null !== $cacheDriver = $factory->getCacheDriver()) {
            $cacheId = $this->generateCacheId($class);

            if (false === $config = $cacheDriver->fetch($cacheId)) {
                $this->configs[$class] = $this->reader->read($class);

                return $this->configs[$class];
            }
        }

        // Re-fetch metadata on cache miss
        $this->configs[$class] = $this->reader->read($class);

        return $this->configs[$class];
    }

    /**
     * Generate the cache id
     *
     * @param string $class Entity class name
     *
     * @return string
     */
    protected function generateCacheId($class)
    {
        return str_replace('\\', '_', __CLASS__ . ' - ' . $class);
    }
}
