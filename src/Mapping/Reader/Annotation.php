<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gtt\Bundle\DoctrineAuditableBundle\Exception\InvalidMappingException;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Annotation\Entity;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Annotation\Property;
use LogicException;
use ReflectionClass;
use ReflectionProperty;

use function count;
use function is_array;

/**
 * This is an annotation mapping driver for Auditable behavioral extension.
 * Used for extraction of extended metadata from Annotations specifically for Auditable extension.
 */
final class Annotation implements AnnotationInterface
{
    /**
     * Annotation reader instance
     */
    private Reader $reader;

    /**
     * Entity managers registry
     */
    private Registry $registry;

    /**
     * Annotation constructor.
     *
     * @param Reader   $reader   Annotation reader
     * @param Registry $registry Entity managers registry
     */
    public function __construct(Reader $reader, Registry $registry)
    {
        $this->reader   = $reader;
        $this->registry = $registry;
    }

    /**
     *{@inheritdoc}
     */
    public function read(string $class): array
    {
        $entityManager = $this->registry->getManagerForClass($class);
        if (null === $entityManager) {
            throw new LogicException("Class `$class` has no object manager");
        }
        $metadataFactory = $entityManager->getMetadataFactory();
        $meta            = $metadataFactory->getMetadataFor($class);

        assert($meta instanceof ClassMetadataInfo);
        $reflectionClass = $meta->getReflectionClass();
        // Analyze class annotation
        if (!$this->hasClassAnnotation($reflectionClass, Entity::class)) {
            return [];
        }

        /** config properties: columns (string[]),  */
        $config = ['columns' => []];

        // Analyze property annotations
        foreach ($reflectionClass->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate()) {
                continue;
            }

            // Auditable property
            if ($this->hasPropertyAnnotation($property, Property::class)) {
                $field = $property->getName();

                if (isset($meta->embeddedClasses[$field])) {
                    throw new InvalidMappingException("Embedded classes does not support, {$meta->name}::$field");
                }
                if ($meta->isCollectionValuedAssociation($field)) {
                    throw new InvalidMappingException("Collections does not support, {$meta->name}::$field");
                }

                $config['columns'][] = $field;
            }
        }

        if (!$meta->isMappedSuperclass && count($meta->identifier) > 1) {
            throw new InvalidMappingException("Composite identifiers are not supported, found in class \"{$meta->name}\"");
        }

        return $config;
    }

    /**
     * @param class-string $annotation
     */
    private function hasClassAnnotation(ReflectionClass $class, string $annotation): bool
    {
        if ($this->hasPhp80Attribute($class, $annotation)) {
            return true;
        }

        return $this->reader->getClassAnnotation($class, $annotation) !== null;
    }

    /**
     * @param class-string $annotation
     */
    private function hasPropertyAnnotation(ReflectionProperty $property, string $annotation): bool
    {
        if ($this->hasPhp80Attribute($property, $annotation)) {
            return true;
        }

        return $this->reader->getPropertyAnnotation($property, $annotation) !== null;
    }

    /**
     * @param ReflectionClass|ReflectionProperty $reflection
     * @param class-string                       $annotation
     */
    private function hasPhp80Attribute($reflection, string $annotation): bool
    {
        if (PHP_VERSION_ID < 80000) {
            return false;
        }

        $attributes = $reflection->getAttributes($annotation);

        return !empty($attributes);
    }
}
