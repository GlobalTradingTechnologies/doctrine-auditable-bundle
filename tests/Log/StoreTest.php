<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Log;

use Doctrine\Bundle\DoctrineBundle as Doctrine;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Gtt\Bundle\DoctrineAuditableBundle\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use function spl_object_hash;

/**
 * Tests {@see Store}
 */
final class StoreTest extends TestCase
{
    /**
     * @return Doctrine\Registry|MockObject
     */
    private function getDoctrineRegistryMock(): Doctrine\Registry
    {
        return $this->getMockBuilder(Doctrine\Registry::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return EntityManagerInterface|MockObject
     */
    private function getEntityManagerMock(): EntityManagerInterface
    {
        return $this->getMockBuilder(EntityManagerInterface::class)->getMock();
    }

    /**
     * @return OnFlushEventArgs|MockObject
     */
    private function getOnFlushEventArgsMock(): OnFlushEventArgs
    {
        return $this->getMockBuilder(OnFlushEventArgs::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * white/negative: tests {@see Store::describe} method with no related entity manager for entity
     */
    public function testDescribeWithNoRelatedEntityManager(): void
    {
        $doctrineRegistry = $this->getDoctrineRegistryMock();
        $doctrineRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Stub\Entity::class)
            ->willReturn(null);

        $entity = new Stub\Entity();

        $store = new Store($doctrineRegistry);

        $this->expectException(Exception\NoEntityManagerFoundException::class);
        $store->describe($entity, 'whatever');
    }

    /**
     * white/positive: tests {@see Store::describe} method
     */
    public function testDescribeAndPop(): void
    {
        $entityManager = $this->getEntityManagerMock();

        $doctrineRegistry = $this->getDoctrineRegistryMock();
        $doctrineRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Stub\Entity::class)
            ->willReturn($entityManager);

        $comment = 'Test entity changes description';
        $entity = new Stub\Entity();

        $store = new Store($doctrineRegistry);
        $store->describe($entity, $comment);

        $logStack = $store->pop($entityManager);

        $entityHash = spl_object_hash($entity);

        self::assertArrayHasKey($entityHash, $logStack);
        self::assertEquals($logStack[$entityHash], $comment);
    }

    /**
     * white/positive: tests {@see Store::onFlush} method
     */
    public function testOnFlush(): void
    {
        $entityManager = $this->getEntityManagerMock();

        $doctrineRegistry = $this->getDoctrineRegistryMock();
        $doctrineRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Stub\Entity::class)
            ->willReturn($entityManager);

        $onFlushEventArgs = $this->getOnFlushEventArgsMock();
        $onFlushEventArgs->expects(self::once())->method('getEntityManager')->willReturn($entityManager);

        $store = new Store($doctrineRegistry);
        $store->describe(new Stub\Entity(), 'whatever');
        $store->onFlush($onFlushEventArgs);

        self::assertEmpty($store->pop($entityManager));
    }
}
