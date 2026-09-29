<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Clock\ClockInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Clock\SystemClock;

return [
    ServiceLocatorInterface::class => [
        ReflectionResolver::class => [
            ClockInterface::class => SystemClock::class,
        ],
    ],
];
