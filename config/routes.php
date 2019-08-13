<?php

declare(strict_types=1);

use App\Middleware\TableMiddleware;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\Helper\BodyParams\BodyParamsMiddleware;
use Zend\Expressive\MiddlewareFactory;

/*
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/:id', App\Handler\AlbumDeleteHandler::class, 'album.delete');
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
 *     Zend\Expressive\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', App\Handler\HomePageHandler::class, 'home');
    $app->get('/api/ping', App\Handler\PingHandler::class, 'api.ping');

    $app->get('/{config:\w+}[/map]', App\Handler\MapHandler::class, 'map');
    $app->get('/{config:\w+}/table[/{offset:\d+}]', App\Handler\TableHandler::class, 'table');

    $app->get('/{config:\w+}[/map]', [TableMiddleware::class, App\Handler\MapHandler::class], 'map');
    $app->get('/{config:\w+}/table[/{offset:\d+}]', [TableMiddleware::class, App\Handler\TableHandler::class], 'table');

    $app->get('/{config:\w+}/export/{format:\w+}', [TableMiddleware::class, App\Handler\ExportHandler::class], 'export');

    $app->get('/{config:\w+}/api/db/table', [TableMiddleware::class, App\Handler\API\Database\TableHandler::class], 'api.db.table');
    $app->route(
        '/{config:\w+}/api/db/records',
        [
            TableMiddleware::class,
        App\Handler\API\Database\RecordsHandler::class,
        ],
        ['GET', 'POST'],
        'api.db.records'
    );
    $app->route(
        '/{config:\w+}/api/db/records/{id:\d+}',
        [
            TableMiddleware::class,
            BodyParamsMiddleware::class,
            App\Handler\API\Database\RecordsHandler::class,
        ],
        ['GET', 'PUT', 'DELETE'],
        'api.db.records.id'
    );
};
