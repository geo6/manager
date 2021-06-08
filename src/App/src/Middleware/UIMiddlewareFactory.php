<?php

declare(strict_types=1);

namespace App\Middleware;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class UIMiddlewareFactory
{
    public function __invoke(ContainerInterface $container) : UIMiddleware
    {
        $config = $container->get('config');

        $template = $container->get(TemplateRendererInterface::class);
        $columns = [
            'readonly' => $config['readonlyColumns'] ?? [],
        ];

        return new UIMiddleware($template, $columns);
    }
}
