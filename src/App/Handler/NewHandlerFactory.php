<?php

declare(strict_types=1);

namespace App\Handler;

use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

class NewHandlerFactory
{
    public function __invoke(ContainerInterface $container): NewHandler
    {
        $router = $container->get(RouterInterface::class);

        return new NewHandler($router);
    }
}
