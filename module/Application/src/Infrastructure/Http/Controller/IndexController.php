<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Http\Controller;

use ExtendsSoftware\ExaPHP\Http\Request\RequestInterface;
use ExtendsSoftware\ExaPHP\Http\Response\Response;
use ExtendsSoftware\ExaPHP\Http\Response\ResponseInterface;
use ExtendsSoftware\ExaPHP\Router\Route\Route;
use ExtendsSoftware\ExaPHP\Router\RouterException;
use ExtendsSoftware\ExaPHP\Router\RouterInterface;
use ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Hateoas\Resource\IndexResource;

final readonly class IndexController
{
    public function __construct(private RouterInterface $router) {}

    /**
     * @throws RouterException When a resource link cannot be assembled.
     */
    #[Route('/v1', name: 'v1/index/get')]
    public function get(RequestInterface $request): ResponseInterface
    {
        return new Response()->withBody(
            new IndexResource($this->router, $request),
        );
    }
}
