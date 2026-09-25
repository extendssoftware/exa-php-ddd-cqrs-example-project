<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Persistence;

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Factory\ServiceFactoryInterface;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHP\Utility\Container\ContainerException;
use PDO;
use PDOException;

final readonly class PdoFactory implements ServiceFactoryInterface
{
    /**
     * @throws ContainerException When the PDO DSN configuration is missing.
     * @throws PDOException When the driver is unavailable or the connection fails.
     */
    public function createService(
        string $class,
        ServiceLocatorInterface $serviceLocator,
        ?array $extra = null,
    ): PDO {
        $config = $serviceLocator->getContainer();

        return new PDO(
            $config->get(PDO::class . '.dsn'),
            $config->find(PDO::class . '.username'),
            $config->find(PDO::class . '.password'),
            $config->find(PDO::class . '.options', []),
        );
    }
}
