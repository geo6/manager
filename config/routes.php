<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

/**
 * FastRoute route configuration.
 *
 * @see https://github.com/nikic/FastRoute
 *
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/{id:\d+}', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/{id:\d+}', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/{id:\d+}', App\Handler\AlbumDeleteHandler::class, 'album.delete');
 *
 * Or with multiple request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class, ['GET', 'POST', ...], 'contact');
 *
 * Or handling all request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class)->setName('contact');
 *
 * or:
 *
 * $app->route(
 *     '/contact',
 *     App\Handler\ContactHandler::class,
 *     Mezzio\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */
return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', App\Handler\HomePageHandler::class, 'home');
    $app->get('/map', App\Handler\MapHandler::class, 'map');
    $app->get('/table[/{offset:\d+}]', App\Handler\TableHandler::class, 'table');

    $app->get('/api/object[/{id:\d+}]', API\Handler\Object\GetHandler::class, 'api.object.get');
    $app->delete('/api/object/{id:\d+}', API\Handler\Object\DeleteHandler::class, 'api.object.delete');
    $app->patch('/api/object/{id:\d+}', [BodyParamsMiddleware::class, API\Handler\Object\PatchPutHandler::class], 'api.object.patch');
    $app->put('/api/object/{id:\d+}', [BodyParamsMiddleware::class, API\Handler\Object\PatchPutHandler::class], 'api.object.put');

    $app->get('/api/file/{action:info|thumbnail}/{id:\d+}/{column}', [API\Handler\File\ThumbnailHandler::class], 'api.file.thumbnail');

    $app->get('/api/ping', API\Handler\PingHandler::class, 'api.ping');
};
