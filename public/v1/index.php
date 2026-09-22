<?php

declare(strict_types=1);

use ExtendsSoftware\ExaPHPExample\Application\Infrastructure\ApplicationFactory;

try {
    $projectRoot = dirname(__DIR__, 2);

    chdir($projectRoot);

    require_once $projectRoot . '/vendor/autoload.php';

    new ApplicationFactory()
        ->create($projectRoot)
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
