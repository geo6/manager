<?php

declare(strict_types=1);

namespace API\Handler\File;

use Psr\Container\ContainerInterface;

class ThumbnailHandlerFactory
{
    public function __invoke(ContainerInterface $container): ThumbnailHandler
    {
        return new ThumbnailHandler();
    }
}
