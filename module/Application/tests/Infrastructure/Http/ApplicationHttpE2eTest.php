<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Tests\Infrastructure\Http;

use ExtendsSoftware\ExaPHPExample\Application\Tests\Support\E2eClientFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function json_decode;

use const JSON_THROW_ON_ERROR;

#[Group('e2e')]
final class ApplicationHttpE2eTest extends TestCase
{
    #[Test]
    public function getReturnsNotFoundProblemDetailsForUnknownRoute(): void
    {
        $client = new E2eClientFactory()->create();

        $response = $client->request('GET', '/v1/nonexistent');

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('/problems/router/not-found', $body['type']);
        self::assertSame('Not found', $body['title']);
        self::assertSame('Request could not be matched by a route.', $body['detail']);
        self::assertSame(404, $body['status']);
        self::assertSame('/v1/nonexistent', $body['instance']);
    }

    #[Test]
    public function postReturnsMethodNotAllowedProblemDetails(): void
    {
        $client = new E2eClientFactory()->create();

        $response = $client->request('POST', '/v1');

        self::assertSame(405, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));
        self::assertSame('GET', $response->getHeaderLine('Allow'));

        $body = json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('/problems/router/method-not-allowed', $body['type']);
        self::assertSame('Method not allowed', $body['title']);
        self::assertSame('Method is not allowed.', $body['detail']);
        self::assertSame(405, $body['status']);
        self::assertSame('/v1', $body['instance']);
        self::assertSame('POST', $body['metadata']['method']);
        self::assertSame(['GET'], $body['metadata']['allowed_methods']);
    }

    #[Test]
    public function getReturnsBadRequestProblemDetailsForUnsupportedQueryParameter(): void
    {
        $client = new E2eClientFactory()->create();

        $response = $client->request('GET', '/v1?unexpected=value');

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('/problems/router/query-parameter-not-allowed', $body['type']);
        self::assertSame('Query parameter not allowed', $body['title']);
        self::assertSame('Query string parameters are not allowed.', $body['detail']);
        self::assertSame(400, $body['status']);
        self::assertSame('/v1?unexpected=value', $body['instance']);
        self::assertSame(['unexpected'], $body['metadata']['parameters']);
    }
}
