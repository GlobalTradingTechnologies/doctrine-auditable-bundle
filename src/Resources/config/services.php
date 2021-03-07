<?php
/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Gtt\Bundle\DoctrineAuditableBundle\Event\AuditableListener;
use Gtt\Bundle\DoctrineAuditableBundle\Log\Store;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\Annotation;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\CachedAnnotation;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('gtt.doctrine_auditable.subscriber_class', ''); // replaced by container extension

    $reference = function_exists('Symfony\\Component\\DependencyInjection\\Loader\\Configurator\\service')
        ? 'Symfony\\Component\\DependencyInjection\\Loader\\Configurator\\service'
        : 'Symfony\\Component\\DependencyInjection\\Loader\\Configurator\\ref';

    $container->services()
        ->defaults()
            ->autowire(false)
            ->autoconfigure(false)
            ->private()

        ->set(AuditableListener::class, '%gtt.doctrine_auditable.subscriber_class%')
            ->args([
                $reference(Store::class),
                $reference('security.token_storage')->nullOnInvalid(),
                $reference(CachedAnnotation::class),
            ])
            ->tag('doctrine.event_listener', [
                'event'    => 'onFlush',
                'method'   => 'onFlush',
                'priority' => 10,
            ])

        ->set(Annotation::class)
            ->args([service('annotation_reader'), $reference('doctrine')])

        ->set(CachedAnnotation::class)
            ->args([$reference(Annotation::class), $reference('doctrine')])

        ->set(Store::class)
            ->share()
            ->args([$reference('doctrine')])
            ->tag('doctrine.event_listener', [
                'event'    => 'onFlush',
                'method'   => 'onFlush',
                'priority' => -1024, // priority must be lower than `AuditableListener` has
            ])
    ;
};
