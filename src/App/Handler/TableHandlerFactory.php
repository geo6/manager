<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class TableHandlerFactory
{
    public function __invoke(ContainerInterface $container) : TableHandler
    {
        return new TableHandler($container->get(TemplateRendererInterface::class));
    }
}
