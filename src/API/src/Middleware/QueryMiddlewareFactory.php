<?php

declare(strict_types=1);

namespace API\Middleware;

use Psr\Container\ContainerInterface;

class QueryMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): QueryMiddleware
    {
        return new QueryMiddleware();
    }
}
