<?php

declare(strict_types=1);

namespace App\Handler;

use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class TableHandlerFactory
{
    public function __invoke(ContainerInterface $container): TableHandler
    {
        $router = $container->get(RouterInterface::class);
        $renderer = $container->get(TemplateRendererInterface::class);

        return new TableHandler($router, $renderer);
    }
}
