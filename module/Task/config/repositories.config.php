<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\TaskRepositoryInterface;
use ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Repository\PdoTaskRepository;

return [
    ServiceLocatorInterface::class => [
        ReflectionResolver::class => [
            TaskRepositoryInterface::class => PdoTaskRepository::class,
        ],
    ],
];
