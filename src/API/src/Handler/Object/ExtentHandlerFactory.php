<?php

declare(strict_types=1);

namespace API\Handler\Object;

use Psr\Container\ContainerInterface;

class ExtentHandlerFactory
{
    public function __invoke(ContainerInterface $container): ExtentHandler
    {
        return new ExtentHandler();
    }
}
