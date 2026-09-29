<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHPExample\Shared\Application\Outbox\OutboxInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\EventSerializerInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\EventSerializerRegistryFactory;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Outbox\PdoOutbox;
use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Factory\FactoryResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;

return [
    ServiceLocatorInterface::class => [
        FactoryResolver::class => [
            EventSerializerInterface::class => EventSerializerRegistryFactory::class,
        ],
        ReflectionResolver::class => [
            OutboxInterface::class => PdoOutbox::class,
        ],
    ],
];
