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
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\Stub\Php74Entity;
use Gtt\Bundle\DoctrineAuditableBundle\Mapping\Reader\Stub\Php80Entity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class AnnotationTest
 */
final class AnnotationTest extends TestCase
{
    private Annotation $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $configuration = new Configuration();
        $configuration->setAutoGenerateProxyClasses(false);
        $configuration->setProxyDir(sys_get_temp_dir());
        $configuration->setProxyNamespace('Proxy\\' . __NAMESPACE__);
        $configuration->setMetadataDriverImpl(new StaticPHPDriver(__DIR__ . '/Stub'));

        $eventManager = new EventManager();
        $connection = $this->getMockBuilder(Connection::class)
                           ->onlyMethods(['getEventManager'])
                           ->disableOriginalConstructor()
                           ->disableProxyingToOriginalMethods()
                           ->getMockForAbstractClass();

        $connection->method('getEventManager')->willReturn($eventManager);

        $em = EntityManager::create($connection, $configuration);

        $container = new Container();
        $container->set('default.em', $em);

        $this->reader = new Annotation(new AnnotationReader(), new Registry(
            $container,
            ['default.connection'],
            ['default.em'],
            'default.connection',
            'default.em'
        ));
    }

    public function testReadingAnnotationsFromDocComment(): void
    {
        $data = $this->reader->read(Php74Entity::class);

        self::assertSame(['columns' => ['auditableProperty']], $data);
    }

    public function testReadingPhp80Attributes(): void
    {
        if (PHP_VERSION_ID < 80000) {
            self::markTestSkipped('This test should run under PHP-8.0');
        }

        $data = $this->reader->read(Php80Entity::class);

        self::assertSame(['columns' => ['auditableProperty']], $data);
    }
}
