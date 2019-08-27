<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Model\Table;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Session\SessionMiddleware;

class TableMiddleware implements MiddlewareInterface
{
    public const TABLE_ATTRIBUTE = 'table';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (is_null($config['config'])) {
            $schema = $session->get('schema');
            $table = $session->get('table');
        } else {
            $connection = $adapter->getDriver()->getConnection()->getConnectionParameters();

            $schema = $connection['schema'];
            $table = $connection['table'];
        }

        return $handler->handle(
            $request->withAttribute(
                self::TABLE_ATTRIBUTE,
                new Table($adapter, $schema, $table, $config['config'])
            )
        );
    }
}
