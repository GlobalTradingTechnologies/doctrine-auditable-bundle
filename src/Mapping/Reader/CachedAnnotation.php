<?php
declare(strict_types=1);
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader;

use Doctrine\Bundle\DoctrineBundle\Registry;

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
     * @param Annotation $reader   Annotation reader
     * @param Registry   $registry Entity managers registry
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

        $entityManager = $this->registry->getManagerForClass($class);
        $factory       = $entityManager->getMetadataFactory();
        $cacheDriver   = $factory->getCacheDriver();
        if ($cacheDriver !== null) {
            $cacheId = $this->generateCacheId($class);

            $config = $cacheDriver->fetch($cacheId);
            if ($config === false) {
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
    protected function generateCacheId(string $class): string
    {
        return str_replace('\\', '_', __CLASS__ . ' - ' . $class);
    }
}
