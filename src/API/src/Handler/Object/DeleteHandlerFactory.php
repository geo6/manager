<?php

declare(strict_types=1);

namespace API\Handler\Object;

use Psr\Container\ContainerInterface;

class DeleteHandlerFactory
{
    public function __invoke(ContainerInterface $container) : DeleteHandler
    {
        return new DeleteHandler();
    }
}
