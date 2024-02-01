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
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Gtt\Bundle\DoctrineAuditableBundle\Exception\InvalidMappingException;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\Stub\Php80CompositeIdentifierEntity;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\Stub\Php80Entity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

final class AttributeTest extends TestCase
{
    private Attribute $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $configuration = new Configuration();
        $configuration->setAutoGenerateProxyClasses(false);
        $configuration->setProxyDir(sys_get_temp_dir());
        $configuration->setProxyNamespace('Proxy\\' . __NAMESPACE__);
        $configuration->setMetadataDriverImpl(new StaticPHPDriver(__DIR__ . '/Stub'));

        $eventManager = new EventManager();
        $connection   = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['getEventManager'])
            ->disableOriginalConstructor()
            ->getMock();

        $connection->method('getEventManager')->willReturn($eventManager);

        $em = new EntityManager(
            DriverManager::getConnection(
                [
                    'driver'  => 'pdo_sqlite',
                    'name'    => 'default',
                    'memory'  => true,
                    'logging' => false,
                ]
            ),
            $configuration
        );

        $container = new Container();
        $container->set('default.em', $em);

        $this->reader = new Attribute(
            new Registry(
                $container,
                ['default.connection'],
                ['default.em'],
                'default.connection',
                'default.em'
            )
        );
    }

    public function testReadingPhp80Attributes(): void
    {
        $data = $this->reader->read(Php80Entity::class);

        self::assertSame(['columns' => ['auditableProperty']], $data);
    }

    public function testReadingPhp80CompositeEntity(): void
    {
        $this->expectException(InvalidMappingException::class);
        $this->reader->read(Php80CompositeIdentifierEntity::class);
    }
}
