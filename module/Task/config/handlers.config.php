<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CompleteTask\CompleteTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\CreateTask\CreateTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\DeleteTask\DeleteTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\RenameTask\RenameTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Application\Command\ReopenTask\ReopenTaskHandler;
use ExtendsSoftware\ExaPHPExample\Task\Application\Query\GetTask\GetTaskHandler;

return [
    ServiceLocatorInterface::class => [
        ReflectionResolver::class => [
            CreateTaskHandler::class => CreateTaskHandler::class,
            RenameTaskHandler::class => RenameTaskHandler::class,
            CompleteTaskHandler::class => CompleteTaskHandler::class,
            ReopenTaskHandler::class => ReopenTaskHandler::class,
            DeleteTaskHandler::class => DeleteTaskHandler::class,
            GetTaskHandler::class => GetTaskHandler::class,
        ],
    ],
];
