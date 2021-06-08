<?php

declare(strict_types=1);

namespace API\Handler\File;

use Psr\Container\ContainerInterface;

class ThumbnailHandlerFactory
{
    public function __invoke(ContainerInterface $container): ThumbnailHandler
    {
        $config = $container->get('config');

        $file = $config['fileColumns'] ?? [];

        return new ThumbnailHandler($file);
    }
}
