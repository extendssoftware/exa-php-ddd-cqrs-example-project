<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\Application\ApplicationBuilder;
use ExtendsSoftware\ExaPHPExample\Application\ApplicationModule;

require_once __DIR__ . '/../../vendor/autoload.php';

chdir(__DIR__ . '/../../');

new ApplicationBuilder()
    ->addGlobalConfigDirectory(getenv('APP_CONFIG_DIRECTORY') ?: getcwd() . '/config', '/\.(global|local)\.php$/')
    ->setCacheLocation(getenv('APP_CACHE_DIRECTORY') ?: getcwd() . '/data/cache')
    ->setCacheEnabled(filter_var(getenv('APP_CACHE_ENABLED'), FILTER_VALIDATE_BOOLEAN))
    ->addModule(new ApplicationModule())
    ->build()
    ->bootstrap();
