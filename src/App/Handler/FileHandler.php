<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Middleware\TableMiddleware;
use App\Model\Filesystem;
use App\Model\Record;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Expressive\Router\RouteResult;

class FileHandler implements RequestHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $request->getAttribute(RouteResult::class)->getMatchedRouteName();

        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);

        $id = $request->getAttribute('id');
        $column = $request->getAttribute('column');

        $filesystem = new Filesystem($config['config']['adapter']);

        $record = new Record($adapter, $table, intval($id));
        $path = $record->properties[$column];

        if (!is_null($path)) {
            $path = trim($path);
        }

        if (is_null($path) || strlen($path) === 0 || $filesystem->has($path) !== true) {
            return new EmptyResponse(404);
        }

        var_dump($route, $path, $filesystem->getMimetype($path));
        exit();
    }
}
