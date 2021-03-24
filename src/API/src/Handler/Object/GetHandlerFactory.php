<?php

declare(strict_types=1);

namespace API\Handler\Object;

use Psr\Container\ContainerInterface;

class GetHandlerFactory
{
    public function __invoke(ContainerInterface $container): GetHandler
    {
        return new GetHandler();
    }
}
