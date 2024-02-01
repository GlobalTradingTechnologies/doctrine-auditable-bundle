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
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gtt\Bundle\DoctrineAuditableBundle\Exception\InvalidMappingException;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Attribute\Entity;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Attribute\Property;
use LogicException;
use ReflectionClass;

/**
 * This is an attribute mapping driver for Auditable behavioral extension.
 * Used for extraction of extended metadata from Attributes specifically for Auditable extension.
 */
final readonly class Attribute implements AttributeInterface
{
    public function __construct(
        private Registry $registry
    ) {
    }

    /**
     * @inheritDoc
     */
    public function read(string $class): array
    {
        $entityManager = $this->registry->getManagerForClass($class);
        if ($entityManager === null) {
            throw new LogicException("Class `$class` has no object manager");
        }
        $metadataFactory = $entityManager->getMetadataFactory();
        $meta            = $metadataFactory->getMetadataFor($class);

        assert($meta instanceof ClassMetadataInfo);
        $reflectionClass = $meta->getReflectionClass();

        if ($reflectionClass === null) {
            return [];
        }

        if (!$reflectionClass->getAttributes(Entity::class)) {
            return [];
        }

        if (!$meta->isMappedSuperclass && count($meta->identifier) > 1) {
            throw new InvalidMappingException(
                "Composite identifiers are not supported, found in class \"{$meta->name}\""
            );
        }

        return $this->getPropertiesConfig($reflectionClass, $meta);
    }

    /**
     * Analyze property attributes
     */
    private function getPropertiesConfig(ReflectionClass $reflectionClass, ClassMetadataInfo $meta): array
    {
        /** config properties: columns (string[]),  */
        $config = ['columns' => []];

        foreach ($reflectionClass->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate()) {
                continue;
            }

            // Auditable property
            if ($property->getAttributes(Property::class)) {
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

        return $config;
    }
}
