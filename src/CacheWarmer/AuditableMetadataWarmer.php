<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\CacheWarmer;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ObjectManager;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\AnnotationInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Generates cache with auditable attributes
 */
final class AuditableMetadataWarmer implements CacheWarmerInterface
{
    private Registry $registry;

    /**
     * Generic undecorated attributes reader
     */
    private AnnotationInterface $reader;

    /**
     * Cache directory relative to kernel cache dir
     */
    private string $cachePath;

    public function __construct(Registry $registry, AnnotationInterface $reader, string $cachePath)
    {
        $this->registry  = $registry;
        $this->reader    = $reader;
        $this->cachePath = $cachePath;
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir): array
    {
        foreach ($this->registry->getManagers() as $om) {
            $this->generateCacheForManager($om, $cacheDir);
        }

        return [];
    }

    private function generateCacheForManager(ObjectManager $om, string $cacheDir): void
    {
        $myCacheRoot = $this->createPath($cacheDir . DIRECTORY_SEPARATOR . $this->cachePath);

        foreach ($om->getMetadataFactory()->getAllMetadata() as $metadata) {
            $class = $metadata->getName();

            $config = $this->readAuditableSettings($metadata);
            if (empty($config['columns'])) {
                continue;
            }

            $this->writeClassConfiguration($class, $config, $myCacheRoot);
        }
    }

    /**
     * Build class configuration
     *
     * @param ClassMetadataInfo $meta Class metadata
     *
     * @return array
     */
    private function readAuditableSettings(ClassMetadataInfo $meta): array
    {
        // if class has no parent, just return its configuration
        // or read configuration for each parent class in inheritance chain,
        // validate, merge them and return otherwise
        if (empty($meta->parentClasses)) {
            return $this->reader->read($meta->name);
        }

        $configurationToMergeMap = [$meta->name => $this->reader->read($meta->name)];
        // 2. Retrieve all parent classes configuration
        foreach (array_reverse($meta->parentClasses) as $parentClass) {  // from root to direct parent
            $configurationToMergeMap[$parentClass] = $this->reader->read($parentClass);
        }

        // 3. Build final class configuration: perform configuration inheritance
        $configurationToMergeList = array_values($configurationToMergeMap);
        return array_merge_recursive(...$configurationToMergeList);
    }

    private function createPath(string $path): string
    {
        if (!\is_dir($path) && !mkdir($path, 0755, true) && !\is_dir($path)) {
            throw new \RuntimeException(sprintf('Can not create metadata cache directory "%s" for auditable bundle', $path));
        }

        return $path;
    }

    private function writeClassConfiguration(string $class, array $config, string $baseDir): void
    {
        $relativeFullPath = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, '\\')) . '.php';

        $this->createPath($baseDir . DIRECTORY_SEPARATOR . dirname($relativeFullPath));

        $data    = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ';';
        $written = file_put_contents($baseDir . DIRECTORY_SEPARATOR . $relativeFullPath, $data);

        if ($written === false || strlen($data) !== $written) {
            throw new \RuntimeException("Failed to write metadata cache for class \"$class\".");
        }
    }
}
