<?php

declare(strict_types=1);

namespace App\Middleware;

use Exception;
use Laminas\Db\Adapter\Adapter;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DbAdapterMiddleware implements MiddlewareInterface
{
    public const DBADAPTER_ATTRIBUTE = 'adapter';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);

        if (!isset($config['global']['database'])) {
            throw new Exception(sprintf(
                'Cannot create %s; could not locate global PostgreSQL parameters.',
                self::class
            ));
        }

        if (!is_null($config['config']) && !isset($config['config']['database'])) {
            throw new Exception(sprintf(
                'Cannot create %s; could not locate PostgreSQL parameters in application configuration (%s).',
                self::class,
                $config['custom']
            ));
        }

        $adapter = new Adapter(
            array_merge([
                'driver'         => 'Pdo_Pgsql',
                'driver_options' => [
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ],
            ], is_null($config['config']) ? $config['global']['database'] : $config['config']['database'])
        );

        return $handler->handle($request->withAttribute(self::DBADAPTER_ATTRIBUTE, $adapter));
    }
}
