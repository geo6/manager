<?php

declare(strict_types=1);

namespace API;

/**
 * The configuration provider for the API module.
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array.
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    /**
     * Returns the container dependencies.
     */
    public function getDependencies(): array
    {
        return [
            'invokables' => [
                Handler\PingHandler::class => Handler\PingHandler::class,
            ],
            'factories'  => [
                Handler\Object\DeleteHandler::class   => Handler\Object\DeleteHandlerFactory::class,
                Handler\Object\GetHandler::class      => Handler\Object\GetHandlerFactory::class,
                Middleware\DatabaseMiddleware::class  => Middleware\DatabaseMiddlewareFactory::class,
                Middleware\TableMiddleware::class     => Middleware\TableMiddlewareFactory::class,
            ],
        ];
    }

    /**
     * Returns the templates configuration.
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'api'    => [__DIR__.'/../templates/'],
            ],
        ];
    }
}
