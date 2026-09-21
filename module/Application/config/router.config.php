<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\Router\RouterInterface;
use ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Http\Controller\IndexController;

return [
    RouterInterface::class => [
        IndexController::class,
    ],
];
