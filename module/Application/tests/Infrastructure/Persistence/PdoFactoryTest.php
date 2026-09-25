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

use function getenv;
use function putenv;

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
    public function connectsToDockerMysqlUsingDistributedConfiguration(): void
    {
        $config = require __DIR__ . '/../../../../../config/pdo.local.php.dist';
        $serviceLocator = new ServiceLocator(new Container($config));

        $pdo = new PdoFactory()->createService(PDO::class, $serviceLocator);

        self::assertSame('mysql', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        self::assertSame(getenv('MYSQL_DATABASE'), $pdo->query('SELECT DATABASE()')->fetchColumn());
        $statement = $pdo->prepare('SELECT :value AS value');
        $statement->execute(['value' => 'connected']);
        self::assertSame(['value' => 'connected'], $statement->fetch());
    }

    #[Test]
    public function distributedConfigurationReadsEnvironmentSettings(): void
    {
        $settings = [
            'MYSQL_DATABASE' => 'custom_database',
            'MYSQL_USER' => 'custom_user',
            'MYSQL_PASSWORD' => '0',
        ];
        $original = [];
        foreach ($settings as $name => $value) {
            $original[$name] = getenv($name);
            putenv($name . '=' . $value);
        }

        try {
            $config = require __DIR__ . '/../../../../../config/pdo.local.php.dist';

            self::assertSame(
                'mysql:host=mysql;port=3306;dbname=custom_database;charset=utf8mb4',
                $config[PDO::class]['dsn'],
            );
            self::assertSame('custom_user', $config[PDO::class]['username']);
            self::assertSame('0', $config[PDO::class]['password']);
        } finally {
            foreach ($original as $name => $value) {
                putenv($value === false ? $name : $name . '=' . $value);
            }
        }
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
