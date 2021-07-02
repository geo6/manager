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
                Handler\ConfigHandler::class          => Handler\ConfigHandlerFactory::class,
                Handler\File\ThumbnailHandler::class  => Handler\File\ThumbnailHandlerFactory::class,
                Handler\File\UploadHandler::class     => Handler\File\UploadHandlerFactory::class,
                Handler\Object\DeleteHandler::class   => Handler\Object\DeleteHandlerFactory::class,
                Handler\Object\ExtentHandler::class   => Handler\Object\ExtentHandlerFactory::class,
                Handler\Object\GetHandler::class      => Handler\Object\GetHandlerFactory::class,
                Handler\Object\PatchPutHandler::class => Handler\Object\PatchPutHandlerFactory::class,
                Middleware\DatabaseMiddleware::class  => Middleware\DatabaseMiddlewareFactory::class,
                Middleware\QueryMiddleware::class     => Middleware\QueryMiddlewareFactory::class,
                Middleware\TableMiddleware::class     => Middleware\TableMiddlewareFactory::class,
            ],
        ];
    }
}
