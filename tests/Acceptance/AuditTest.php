<?php

/**
 * This file is part of the Global Trading Technologies Ltd doctrine-auditable-bundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Gtt\Bundle\DoctrineAuditableBundle\Acceptance;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Gtt\Bundle\DoctrineAuditableBundle\Acceptance\App\Entity\Order;
use Gtt\Bundle\DoctrineAuditableBundle\Acceptance\App\TestKernel;
use Gtt\Bundle\DoctrineAuditableBundle\Entity\Entry;
use Gtt\Bundle\DoctrineAuditableBundle\Entity\Group;
use Gtt\Bundle\DoctrineAuditableBundle\Log\Store;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class AuditTest
 */
final class AuditTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->em = self::$container->get('doctrine.orm.default_entity_manager');

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->updateSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    public function testAuditingEntityOnPropertyChange(): void
    {
        $order = new Order('Tester', 25);
        $this->em->persist($order);
        $this->em->flush();

        $order->setTotalItems(43);
        $this->em->flush();

        $group = $this->em->getRepository(Group::class)
                          ->findOneBy(['entityClass' => Order::class, 'entityId' => $order->getId()]);
        self::assertNotNull($group);

        assert($group instanceof Group);
        self::assertEmpty($group->getComment());

        /** @var Entry[] $entries */
        $entries = $this->em->getRepository(Entry::class)->findBy(['group' => $group]);
        self::assertCount(1, $entries);
        self::assertSame('25', $entries[0]->getValueBefore());
        self::assertSame('43', $entries[0]->getValueAfter());
    }

    public function testSettingAuditComment(): void
    {
        $order = new Order('Tester', 25);
        $this->em->persist($order);
        $this->em->flush();

        $order->setTotalItems(43);
        $store = self::$container->get(Store::class);
        assert($store instanceof Store);
        $store->describe($order, 'Total items change test');
        $this->em->flush();

        $group = $this->em->getRepository(Group::class)
                          ->findOneBy(['entityClass' => Order::class, 'entityId' => $order->getId()]);
        self::assertNotNull($group);

        assert($group instanceof Group);
        self::assertSame('Total items change test', $group->getComment());
    }

    /**
     * @dataProvider orderDateTimeProvider
     */
    public function testDateTimeAuditing(
        Order $order,
        DateTimeInterface $oldDate,
        DateTimeInterface $newDate,
        callable $setter
    ): void {
        $setter($oldDate);

        $this->em->persist($order);
        $this->em->flush();

        $setter($newDate);
        $store = self::$container->get(Store::class);
        assert($store instanceof Store);
        $store->describe($order, 'Total items change test');
        $this->em->flush();

        $entries = $this->em
            ->createQueryBuilder()
            ->select('e')
            ->from(Entry::class, 'e')
            ->join('e.group', 'g')
            ->where('g.entityClass = :class AND g.entityId = :id')
            ->setParameter('class', Order::class)
            ->setParameter('id', $order->getId())
            ->getQuery()
            ->getResult();

        self::assertCount(1, $entries);
        self::assertSame($oldDate->format('c'), $entries[0]->getValueBefore());
        self::assertSame($newDate->format('c'), $entries[0]->getValueAfter());
    }

    public static function orderDateTimeProvider(): iterable
    {
        $order = new Order('test', 1);
        yield [$order, new DateTime('2020-04-08 12:14:17'), new DateTime('2021-04-08 12:14:17'), [$order, 'setPostedTs']];

        $order = new Order('test', 2);
        yield [$order, new DateTimeImmutable('2020-04-08 12:14:17'), new DateTimeImmutable('2021-04-08 12:14:17'), [$order, 'setExecutedTs']];
    }
}
