<?php

declare(strict_types=1);

namespace App\Middleware;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class UIMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): UIMiddleware
    {
        $config = $container->get('config');

        $template = $container->get(TemplateRendererInterface::class);
        $columns = [
            'file'     => isset($config['fileColumns']) && is_array($config['fileColumns']) ? array_keys($config['fileColumns']) : [],
            'readonly' => $config['readonlyColumns'] ?? [],
        ];
        $file = $config['fileColumns'] ?? [];
        $theme = $config['theme'] ?? [];
        $defaults = $config['defaults'] ?? [];

        return new UIMiddleware($template, $defaults, $columns, $file, $theme);
    }
}
