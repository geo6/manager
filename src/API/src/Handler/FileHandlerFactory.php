<?php

declare(strict_types=1);

namespace API\Handler;

use Psr\Container\ContainerInterface;

class FileHandlerFactory
{
    public function __invoke(ContainerInterface $container) : FileHandler
    {
        return new FileHandler();
    }
}
