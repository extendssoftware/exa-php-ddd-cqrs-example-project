<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\Router\RouterInterface;
use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Http\Controller\IndexController;

return [
    RouterInterface::class => [
        IndexController::class,
    ],
    ServiceLocatorInterface::class => [
        ReflectionResolver::class => [
            IndexController::class => IndexController::class,
        ],
    ],
];
