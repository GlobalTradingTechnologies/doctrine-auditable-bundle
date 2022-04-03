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
use InvalidArgumentException;
use Psr\Log\NullLogger;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class TestKernel
 */
final class TestKernel extends Kernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    /**
     * @var array<string, callable>
     */
    private array $mocks = [];

    /**
     * @var callable(ContainerBuilder): void
     */
    private $compilerPass;

    public function mockMethod(string $name, callable $fn): void
    {
        $ref = new ReflectionMethod($this, $name);
        if (!$ref->isPublic()) {
            throw new InvalidArgumentException(sprintf('Method "%s" is not public and cannot be mocked.', $name));
        }

        if ($ref->getDeclaringClass()->getName() !== self::class) {
            throw new InvalidArgumentException(sprintf(
                'Method "%s" does not have an implementation in class "%s". Implement the method!',
                $name,
                self::class
            ));
        }

        $this->mocks[$name] = $fn;
    }

    /**
     * @param callable(ContainerBuilder): void|null $compilerPass
     */
    public function setCompilerPass(?callable $compilerPass)
    {
        $this->compilerPass = $compilerPass;
    }

    public function process(ContainerBuilder $container): void
    {
        if ($this->compilerPass !== null) {
            ($this->compilerPass)($container);
        }
    }

    /**
     * @inheritDoc
     */
    public function getCacheDir(): string
    {
        return $this->invokeMethod(__FUNCTION__);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineAuditableBundle();
        yield new DoctrineBundle();
        yield new SecurityBundle();
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->extension('framework', ['test' => true]);
        $c->extension('security', [
            'enable_authenticator_manager' => true,
            'providers' => [
                'in_memory' => [
                    'memory' => true
                ],
            ],
            'firewalls' => [
                'main' => null,
            ]
        ]);

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
                        'type'      => 'attribute',
                        'prefix'    => 'Gtt\\Bundle\\DoctrineAuditableBundle\\Acceptance\\App\\Entity',
                        'dir'       => __DIR__ . '/Entity',
                    ],
                    'DoctrineAuditableBundle' => null,
                ],
            ],
        ]);

        $c->services()
            ->set('logger', NullLogger::class);
    }

    private function invokeMethod(string $name, array $args = []): mixed
    {
        if (isset($this->mocks[$name])) {
            return ($this->mocks[$name])(...$args);
        }

        return parent::$name(...$args);
    }
}
