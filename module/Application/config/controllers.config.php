<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Http\Controller\IndexController;

return [
    ServiceLocatorInterface::class => [
        ReflectionResolver::class => [
            IndexController::class => IndexController::class,
        ],
    ],
];