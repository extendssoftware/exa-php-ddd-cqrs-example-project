<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Tests\Infrastructure\Persistence;

use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocator;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorFactory;
use ExtendsSoftware\ExaPHP\Utility\Container\Container;
use ExtendsSoftware\ExaPHP\Utility\Container\ContainerException;
use ExtendsSoftware\ExaPHP\Utility\Merger\Merger;
use ExtendsSoftware\ExaPHPExample\Application\ApplicationModule;
use ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Persistence\PdoFactory;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
final class PdoFactoryTest extends TestCase
{
    #[Test]
    public function createsConnectionWithConfiguredOptions(): void
    {
        $serviceLocator = new ServiceLocator(new Container([
            PDO::class => [
                'dsn' => 'sqlite::memory:',
                'username' => null,
                'password' => null,
                'options' => [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
            ],
        ]));

        $pdo = new PdoFactory()->createService(PDO::class, $serviceLocator);

        self::assertSame(['value' => 42], $pdo->query('SELECT 42 AS value')->fetch());
    }

    #[Test]
    public function createsConnectionWithOnlyDsn(): void
    {
        $serviceLocator = new ServiceLocator(new Container([
            PDO::class => ['dsn' => 'sqlite::memory:'],
        ]));

        $pdo = new PdoFactory()->createService(PDO::class, $serviceLocator);

        self::assertSame('sqlite', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
    }

    #[Test]
    public function rejectsMissingDsn(): void
    {
        $serviceLocator = new ServiceLocator(new Container([]));

        $this->expectException(ContainerException::class);

        new PdoFactory()->createService(PDO::class, $serviceLocator);
    }

    #[Test]
    public function propagatesConnectionFailure(): void
    {
        $serviceLocator = new ServiceLocator(new Container([
            PDO::class => ['dsn' => 'nonexistent-driver:'],
        ]));

        $this->expectException(PDOException::class);

        new PdoFactory()->createService(PDO::class, $serviceLocator);
    }

    #[Test]
    public function resolvesSharedConnectionThroughModuleConfiguration(): void
    {
        $config = [];
        foreach (new ApplicationModule()->getConfig()->load() as $loaded) {
            $config = new Merger()->merge($config, $loaded);
        }
        $config[PDO::class] = ['dsn' => 'sqlite::memory:'];
        $serviceLocator = new ServiceLocatorFactory()->create($config);

        $pdo = $serviceLocator->getService(PDO::class);

        self::assertInstanceOf(PDO::class, $pdo);
        self::assertSame($pdo, $serviceLocator->getService(PDO::class));
        self::assertSame(42, $pdo->query('SELECT 42')->fetchColumn());
    }
}
