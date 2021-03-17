<?php

declare(strict_types=1);

namespace API\Middleware;

use API\Doctrine\Types\GeographyType;
use API\Doctrine\Types\GeometryType;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DatabaseMiddleware implements MiddlewareInterface
{
    public const CONNECTION_ATTRIBUTE = 'dbConnection';

    private array $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $connection = DriverManager::getConnection($this->params);

        if ($connection->getDatabasePlatform()->getName() === 'postgresql') {
            Type::addType('postgis_geography', 'API\Doctrine\Types\GeographyType');
            Type::addType('postgis_geometry', 'API\Doctrine\Types\GeometryType');

            $connection->getDatabasePlatform()->registerDoctrineTypeMapping(GeographyType::TYPE, 'postgis_geography');
            $connection->getDatabasePlatform()->registerDoctrineTypeMapping(GeometryType::TYPE, 'postgis_geometry');
        }

        return $handler->handle($request->withAttribute(self::CONNECTION_ATTRIBUTE, $connection));
    }
}
