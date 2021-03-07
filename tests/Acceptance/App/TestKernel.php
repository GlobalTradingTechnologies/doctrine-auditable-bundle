<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Acceptance\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Gtt\Bundle\DoctrineAuditableBundle\DoctrineAuditableBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class TestKernel
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineAuditableBundle();
        yield new DoctrineBundle();
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->extension('framework', ['test' => true]);

        $c->extension('doctrine', [
            'dbal' => [
                'connection' => [
                    'name'    => 'default',
                    'driver'  => 'pdo_sqlite',
                    'memory'  => true,
                    'logging' => false,
                ],
            ],
            'orm'  => [
                'mappings' => [
                    'App' => [
                        'is_bundle' => false,
                        'type'      => 'annotation',
                        'prefix'    => 'Gtt\\Bundle\\DoctrineAuditableBundle\\Acceptance\\App\\Entity',
                        'dir'       => __DIR__ . '/Entity',
                    ],
                    'DoctrineAuditableBundle' => null,
                ],
            ],
        ]);
    }
}
