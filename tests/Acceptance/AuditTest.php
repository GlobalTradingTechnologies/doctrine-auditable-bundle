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
use Gtt\Bundle\DoctrineAuditableBundle\CacheWarmer\AuditableMetadataWarmer;
use Gtt\Bundle\DoctrineAuditableBundle\Entity\Entry;
use Gtt\Bundle\DoctrineAuditableBundle\Entity\Group;
use Gtt\Bundle\DoctrineAuditableBundle\Log\Store;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Class AuditTest
 */
final class AuditTest extends KernelTestCase
{
    private const MOCK_USER_NAME = 'das author!';

    private EntityManagerInterface $em;

    private vfsStreamDirectory $projectRoot;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $kernel = parent::createKernel($options);
        if (isset($options['cache_dir'])) {
            \assert($kernel instanceof TestKernel);
            $kernel->mockMethod('getCacheDir', static fn (): string => $options['cache_dir']);
        }

        $kernel->setCompilerPass(static function (ContainerBuilder $container): void {
            $container->getDefinition('doctrine.orm.default_entity_manager')
                ->setLazy(true);
        });

        return $kernel;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = vfsStream::setup('app', 0755, [
            'config' => [
                'packages' => [],
            ],
            'var'    => [
                'cache' => [],
            ],
        ]);

        $cacheDir = $this->projectRoot->url() . '/var/cache';
        self::bootKernel(['cache_dir' => $cacheDir]);

        $this->em = self::getContainer()->get('doctrine.orm.default_entity_manager');

        $schemaTool = new SchemaTool($this->em);
        $schemaTool->updateSchema($this->em->getMetadataFactory()->getAllMetadata());

        self::getContainer()->get('security.token_storage')
            ->setToken(new PreAuthenticatedToken(new InMemoryUser(self::MOCK_USER_NAME, ''), 'main'));

        $warmer = self::getContainer()->get(AuditableMetadataWarmer::class);
        \assert($warmer instanceof AuditableMetadataWarmer);
        $warmer->warmUp($cacheDir);
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
        self::assertSame(self::MOCK_USER_NAME, $group->getUsername());

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
        $store = self::getContainer()->get(Store::class);
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
        $store = self::getContainer()->get(Store::class);
        assert($store instanceof Store);
        $store->describe($order, 'Total items change test');
        $this->em->flush();

        $entries = $this->getOrderChangesById($order->getId());

        self::assertCount(1, $entries);

        self::assertSame($oldDate->format('c'), $entries[0]->getValueBefore());
        self::assertSame($newDate->format('c'), $entries[0]->getValueAfter());
    }

    public function testNullableDateInValueBefore(): void
    {
        $order = new Order('test', 1);
        $order->setExecutedTs(null);

        $this->em->persist($order);
        $this->em->flush();

        $order->setExecutedTs(new \DateTimeImmutable('2020-01-02T03:00:00+00:00'));
        $this->em->flush();

        $entries = $this->getOrderChangesById($order->getId());

        self::assertNull($entries[0]->getValueBefore());
        self::assertSame('2020-01-02T03:00:00+00:00', $entries[0]->getValueAfter());
    }

    public function testNullableDateInValueAfter(): void
    {
        $order = new Order('test', 1);
        $order->setExecutedTs(new \DateTimeImmutable('2020-01-02T03:00:00+00:00'));

        $this->em->persist($order);
        $this->em->flush();

        $order->setExecutedTs(null);
        $this->em->flush();

        $entries = $this->getOrderChangesById($order->getId());

        self::assertSame('2020-01-02T03:00:00+00:00', $entries[0]->getValueBefore());
        self::assertNull($entries[0]->getValueAfter());
    }

    public static function orderDateTimeProvider(): iterable
    {
        $order = new Order('test', 1);
        yield [$order, new DateTime('2020-04-08 12:14:17'), new DateTime('2021-04-08 12:14:17'), [$order, 'setPostedTs']];

        $order = new Order('test', 2);
        yield [$order, new DateTimeImmutable('2020-04-08 12:14:17'), new DateTimeImmutable('2021-04-08 12:14:17'), [$order, 'setExecutedTs']];
    }

    private function getOrderChangesById(int $id): array
    {
        return $this->em
            ->createQueryBuilder()
            ->select('e')
            ->from(Entry::class, 'e')
            ->join('e.group', 'g')
            ->where('g.entityClass = :class AND g.entityId = :id')
            ->setParameter('class', Order::class)
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }
}
