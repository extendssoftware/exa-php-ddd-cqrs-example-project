<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Factory\FactoryResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Persistence\PdoFactory;

return [
    ServiceLocatorInterface::class => [
        FactoryResolver::class => [
            PDO::class => PdoFactory::class,
        ],
    ],
];
