<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\Application\ApplicationBuilder;
use ExtendsSoftware\ExaPHPExample\Application\ApplicationModule;

try {
    $projectRoot = dirname(__DIR__, 2);
    chdir($projectRoot);

    require_once $projectRoot . '/vendor/autoload.php';

    new ApplicationBuilder()
        ->addGlobalConfigDirectory(
            getenv('APP_CONFIG_DIRECTORY') ?: $projectRoot . '/config',
            '/\.(global|local)\.php$/',
        )
        ->setCacheLocation(getenv('APP_CACHE_DIRECTORY') ?: $projectRoot . '/data/cache')
        ->setCacheEnabled(filter_var(getenv('APP_CACHE_ENABLED'), FILTER_VALIDATE_BOOLEAN))
        ->addModule(
            new ApplicationModule(),
        )
        ->build()
        ->bootstrap();
} catch (Throwable $exception) {
    error_log((string)$exception);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/problem+json');

        // Match ExaPHP's Problem Details without relying on a working autoloader.
        echo json_encode([
            'type' => '/problems/application/internal-server-error',
            'title' => 'Internal Server Error',
            'detail' => 'An unknown error occurred.',
            'status' => 500,
            'instance' => $_SERVER['REQUEST_URI'] ?? null,
            'metadata' => new stdClass(),
        ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
