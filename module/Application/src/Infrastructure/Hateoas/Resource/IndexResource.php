<?php

declare(strict_types=1);

namespace ExtendsSoftware\ExaPHPExample\Application\Infrastructure\Hateoas\Resource;

use ExtendsSoftware\ExaPHP\Authorization\Permission\Permission;
use ExtendsSoftware\ExaPHP\Hateoas\Attribute\Attribute;
use ExtendsSoftware\ExaPHP\Hateoas\Builder\Builder;
use ExtendsSoftware\ExaPHP\Hateoas\Link\Link;
use ExtendsSoftware\ExaPHP\Http\Request\RequestInterface;
use ExtendsSoftware\ExaPHP\Router\RouterException;
use ExtendsSoftware\ExaPHP\Router\RouterInterface;

class IndexResource extends Builder
{
    /**
     * @throws RouterException When the self-link route cannot be assembled.
     */
    public function __construct(RouterInterface $router, RequestInterface $request)
    {
        $this
            ->addLink(
                'self',
                new Link(
                    $router
                        ->assemble('v1/index/get')
                        ->getUri(),
                    permission: new Permission('v1/index/get'),
                ),
            )
            ->addAttribute('message', new Attribute('Welcome to the ExaPHP DDD CQRS example project API.'))
            ->addAttribute('version', new Attribute('0.1.0'))
            ->addAttribute('remoteAddress', new Attribute($request->getServerParameter('Remote-Addr')));
    }
}
