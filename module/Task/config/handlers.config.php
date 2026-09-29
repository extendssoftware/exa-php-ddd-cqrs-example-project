<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask\CreateTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask\GetTaskHandler;

return [
    ServiceLocatorInterface::class => [
        ReflectionResolver::class => [
            CreateTaskHandler::class => CreateTaskHandler::class,
            GetTaskHandler::class => GetTaskHandler::class,
        ],
    ],
];
