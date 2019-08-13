<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface;

class NewHandlerFactory
{
    public function __invoke(ContainerInterface $container): NewHandler
    {
        $router = $container->get(RouterInterface::class);

        return new NewHandler($router);
    }
}
