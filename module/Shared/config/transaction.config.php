<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\ServiceLocator\Resolver\Reflection\ReflectionResolver;
use ExtendsSoftware\ExaPHP\ServiceLocator\ServiceLocatorInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Application\Transaction\TransactionManagerInterface;
use ExtendsSoftware\ExaPHPExample\Shared\Infrastructure\Transaction\PdoTransactionManager;

return [
    ServiceLocatorInterface::class => [
        ReflectionResolver::class => [
            TransactionManagerInterface::class => PdoTransactionManager::class,
        ],
    ],
];
