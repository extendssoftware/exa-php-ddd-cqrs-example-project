<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\Authorization\AuthorizerInterface;
use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\StaticFactory\StaticFactoryResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Authorization\PublicPermissionRealm;

return [
    AuthorizerInterface::class => [
        'realms' => [
            [
                'name' => PublicPermissionRealm::class,
                'options' => [
                    'permissions' => ['v1/index/get'],
                ],
            ],
        ],
    ],
    ServiceLocatorInterface::class => [
        StaticFactoryResolver::class => [
            PublicPermissionRealm::class => PublicPermissionRealm::class,
        ],
    ],
];
