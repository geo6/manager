<?php

declare(strict_types=1);

namespace API\Handler;

use Psr\Container\ContainerInterface;

class ConfigHandlerFactory
{
    public function __invoke(ContainerInterface $container): ConfigHandler
    {
        $config = $container->get('config');

        return new ConfigHandler($config);
    }
}
