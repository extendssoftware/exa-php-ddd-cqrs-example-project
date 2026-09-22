<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Tests\Infrastructure\Http\Controller;

use ExtendsSoftware\ExaPHPExample\Application\Tests\Support\E2eClientFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function filter_var;
use function json_decode;

use const FILTER_VALIDATE_IP;
use const JSON_THROW_ON_ERROR;

#[Group('e2e')]
final class IndexControllerE2eTest extends TestCase
{
    #[Test]
    public function getReturnsApiInformationAsHalJson(): void
    {
        $client = new E2eClientFactory()->create();

        $response = $client->request('GET', '/v1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/hal+json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($body);
        self::assertSame('/v1', $body['_links']['self']['href']);
        self::assertSame('Welcome to the ExaPHP DDD CQRS example project API.', $body['message']);
        self::assertSame('0.1.0', $body['version']);
        self::assertIsString($body['remoteAddress']);
        self::assertNotFalse(filter_var($body['remoteAddress'], FILTER_VALIDATE_IP));
    }
}
