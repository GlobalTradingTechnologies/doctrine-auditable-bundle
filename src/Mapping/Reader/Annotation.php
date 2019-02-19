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
 * This is an annotation mapping driver for Auditable behavioral extension.
 * Used for extraction of extended metadata from Annotations specifically for Auditable extension.
 */
class Annotation implements AnnotationInterface
{
    /**
     * Annotation reader instance
     *
     * @var Reader
     */
    private $reader;

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
    public function __construct(Reader $reader, Registry $registry)
    {
        $this->reader   = $reader;
        $this->registry = $registry;
    }

    /**
     *{@inheritdoc}
     */
    public function read($class)
    {
        $entityManager = $this->registry->getManagerForClass($class);
        if (null === $entityManager) {
            throw new \LogicException("Class `$class` has no object manager");
        }
        $metadataFactory = $entityManager->getMetadataFactory();
        /** @var ClassMetadataInfo $meta */
        $meta            = $metadataFactory->getMetadataFor($class);
        $reflectionClass = $meta->getReflectionClass();

        // Analyze class annotation
        if (null === $classAnnotation = $this->reader->getClassAnnotation($reflectionClass, Entity::class)) {
            return [];
        }

        /** config properties: columns (string[]), commentProperty (string),  */
        $config = ['columns' => []];
        if (null !== $commentProperty = $classAnnotation->commentProperty) {
            if (!$reflectionClass->hasProperty($commentProperty)) {
                throw new InvalidMappingException("Comment property '$commentProperty' does not exist.");
            }
            $config['commentProperty'] = $commentProperty;
        }

        // Analyze property annotations
        foreach ($reflectionClass->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate()) {
                continue;
            }

            // Auditable property
            if ($this->reader->getPropertyAnnotation($property, Property::class)) {
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

        if (!$meta->isMappedSuperclass && $config) {
            if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Composite identifiers does not support, {$meta->name}");
            }
        }

        return $config;
    }
}
