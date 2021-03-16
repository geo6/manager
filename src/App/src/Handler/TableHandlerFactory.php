<?php

declare(strict_types=1);

namespace App\Handler;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class TableHandlerFactory
{
    public function __invoke(ContainerInterface $container) : TableHandler
    {
        return new TableHandler($container->get(TemplateRendererInterface::class));
    }
}
