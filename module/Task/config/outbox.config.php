<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Invokable\InvokableResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\EventSerializerRegistry;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCompleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskCreated;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskDeleted;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskRenamed;
use ExtendsSoftware\ExaPHPExample\Task\Domain\Task\Event\TaskReopened;
use ExtendsSoftware\ExaPHPExample\Task\Infrastructure\Outbox\TaskEventSerializer;
use ExtendsSoftware\ExaPHPExample\Task\TaskModule;

return [
    EventSerializerRegistry::class => [
        TaskModule::class => [
            TaskCreated::class => TaskEventSerializer::class,
            TaskRenamed::class => TaskEventSerializer::class,
            TaskCompleted::class => TaskEventSerializer::class,
            TaskReopened::class => TaskEventSerializer::class,
            TaskDeleted::class => TaskEventSerializer::class,
        ],
    ],
    ServiceLocatorInterface::class => [
        InvokableResolver::class => [
            TaskEventSerializer::class => TaskEventSerializer::class,
        ],
    ],
];
