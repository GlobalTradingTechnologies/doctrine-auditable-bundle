<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Gtt\Bundle\DoctrineAuditableBundle\DependencyInjection;

use Gtt\Bundle\DoctrineAuditableBundle\Event\AuditableListener;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function is_subclass_of;

/**
 * Defines bundle configuration structure
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('doctrine_auditable');
        $rootNode    = \method_exists($treeBuilder, 'getRootNode')
            ? $treeBuilder->getRootNode()
            : $treeBuilder->root('doctrine_auditable');

        $isWrongAbstraction = static function (string $subscriberClass): bool {
            return !is_subclass_of($subscriberClass, AuditableListener::class);
        };

        $rootNode
            ->children()
                ->scalarNode('subscriber_class')
                    ->info('Subscriber class used to handle event to create auditable data')
                    ->validate()
                        ->ifTrue($isWrongAbstraction)
                        ->thenInvalid('%s class must override "' . AuditableListener::class . '"')
                    ->end()
                    ->defaultValue(AuditableListener::class)
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
