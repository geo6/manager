<?php

declare(strict_types=1);

namespace App\Handler;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class MapHandlerFactory
{
    public function __invoke(ContainerInterface $container) : MapHandler
    {
        return new MapHandler($container->get(TemplateRendererInterface::class));
    }
}
