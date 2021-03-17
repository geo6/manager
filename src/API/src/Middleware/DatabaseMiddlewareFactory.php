<?php

declare(strict_types=1);

namespace API\Middleware;

use Psr\Container\ContainerInterface;

class DatabaseMiddlewareFactory
{
    public function __invoke(ContainerInterface $container) : DatabaseMiddleware
    {
        $config = $container->get('config');

        return new DatabaseMiddleware($config['connection']);
    }
}
