<?php

declare(strict_types=1);

namespace API\Handler\File;

use Psr\Container\ContainerInterface;

class UploadHandlerFactory
{
    public function __invoke(ContainerInterface $container): UploadHandler
    {
        $config = $container->get('config');

        $file = $config['fileColumns'] ?? [];

        return new UploadHandler($file);
    }
}
