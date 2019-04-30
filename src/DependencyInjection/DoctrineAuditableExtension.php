<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Gtt\Bundle\DoctrineAuditableBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * Bundle extension
 */
class DoctrineAuditableExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $config);

        $locator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader  = new XmlFileLoader($container, $locator);

        $loader->load('services.xml');
        $container->setParameter('gtt.doctrine_auditable.subscriber_class', $config['subscriber_class']);
    }
}
