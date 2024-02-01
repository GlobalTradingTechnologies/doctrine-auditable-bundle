<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Gtt\Bundle\DoctrineAuditableBundle\CacheWarmer\AuditableMetadataWarmer;
use Gtt\Bundle\DoctrineAuditableBundle\Event\AuditableListener;
use Gtt\Bundle\DoctrineAuditableBundle\Log\Store;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\Attribute;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('gtt.doctrine_auditable.subscriber_class', '') // replaced by container extension
        ->set('gtt.doctrine_auditable.metadata_cache_path', 'gtt-auditable-bundle' . DIRECTORY_SEPARATOR . 'doctrine')
    ;

    $container->services()
        ->defaults()
            ->autowire(false)
            ->autoconfigure(false)
            ->private()

        ->set(AuditableMetadataWarmer::class)
            ->arg('$registry', service('doctrine'))
            ->arg('$reader', service(Attribute::class))
            ->arg('$cachePath', '%gtt.doctrine_auditable.metadata_cache_path%')
            ->tag('kernel.cache_warmer')

        ->set(AuditableListener::class, '%gtt.doctrine_auditable.subscriber_class%')
            ->args([
                service(Store::class),
                service('security.token_storage')->nullOnInvalid(),
                '%kernel.cache_dir%/%gtt.doctrine_auditable.metadata_cache_path%',
            ])
            ->tag('doctrine.event_listener', [
                'event'    => 'onFlush',
                'method'   => 'onFlush',
                'priority' => 10,
            ])

        ->set(Attribute::class)
            ->arg('$registry', service('doctrine'))

        ->set(Store::class)
            ->share()
            ->args([service('doctrine')])
            ->tag('doctrine.event_listener', [
                'event'    => 'onFlush',
                'method'   => 'onFlush',
                'priority' => -1024, // priority must be lower than `AuditableListener` has
            ])
    ;
};
