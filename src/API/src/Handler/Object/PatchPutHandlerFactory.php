<?php

declare(strict_types=1);

namespace API\Handler\Object;

use Psr\Container\ContainerInterface;

class PatchPutHandlerFactory
{
    public function __invoke(ContainerInterface $container) : PatchPutHandler
    {
        return new PatchPutHandler();
    }
}
