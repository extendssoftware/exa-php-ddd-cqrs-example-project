<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHP\Application\ApplicationBuilder;
use ExtendsSoftware\ExaPHPExample\Application\ApplicationModule;

try {
    $projectRoot = dirname(__DIR__, 2);

    chdir($projectRoot);

    require_once $projectRoot . '/vendor/autoload.php';

    $configDirectory = getenv('APP_CONFIG_DIRECTORY') ?: $projectRoot . '/config';
    $configFilePattern = '/\.(global|local)\.php$/';
    $cacheDirectory = getenv('APP_CACHE_DIRECTORY') ?: $projectRoot . '/data/cache';
    $cacheEnabled = filter_var(getenv('APP_CACHE_ENABLED'), FILTER_VALIDATE_BOOLEAN);
    $modules = [
        new ApplicationModule(),
    ];

    new ApplicationBuilder()
        ->addGlobalConfigDirectory($configDirectory, $configFilePattern)
        ->setCacheLocation($cacheDirectory)
        ->setCacheEnabled($cacheEnabled)
        ->addModule(...$modules)
        ->build()
        ->bootstrap();
} catch (Throwable $exception) {
    error_log((string)$exception);

    if (!headers_sent()) {
        // Match ExaPHP's Problem Details without relying on a working autoloader.
        $body = json_encode([
            'type' => '/problems/application/internal-server-error',
            'title' => 'Internal Server Error',
            'detail' => 'An unknown error occurred.',
            'status' => 500,
            'instance' => $_SERVER['REQUEST_URI'] ?? null,
            'metadata' => new stdClass(),
        ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        http_response_code(500);

        header('Content-Type: application/problem+json');
        header('Content-Length: ' . strlen($body));

        echo $body;
    }
}
