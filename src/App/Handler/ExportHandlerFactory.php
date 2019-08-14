<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class ExportHandlerFactory
{
    public function __invoke(ContainerInterface $container): ExportHandler
    {
        $router = $container->get(RouterInterface::class);
        $renderer = $container->get(TemplateRendererInterface::class);

        return new ExportHandler($router, $renderer);
    }
}
