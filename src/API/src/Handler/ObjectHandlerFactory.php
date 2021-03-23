<?php

declare(strict_types=1);

namespace API\Handler;

use Psr\Container\ContainerInterface;

class ObjectHandlerFactory
{
    public function __invoke(ContainerInterface $container) : ObjectHandler
    {
        return new ObjectHandler();
    }
}
