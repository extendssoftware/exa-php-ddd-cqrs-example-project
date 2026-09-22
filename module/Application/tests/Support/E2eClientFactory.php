<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

use function getenv;

final readonly class E2eClientFactory
{
    public function create(): ClientInterface
    {
        // Start the HTTP services with `just docker-up`; override the URL outside Docker.
        return new Client([
            'base_uri' => getenv('TEST_BASE_URI') ?: 'http://nginx',
            'http_errors' => false,
            'allow_redirects' => false,
            'connect_timeout' => 5,
            'timeout' => 10,
        ]);
    }
}
