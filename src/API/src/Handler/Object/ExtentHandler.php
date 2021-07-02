<?php

declare(strict_types=1);

namespace API\Handler\Object;

use API\Middleware\DatabaseMiddleware;
use API\Middleware\QueryMiddleware;
use API\Middleware\TableMiddleware;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExtentHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Connection */
        $connection = $request->getAttribute(DatabaseMiddleware::CONNECTION_ATTRIBUTE);

        /** @var Table */
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);
        /** @var Column|null */
        $geometryColumn = $request->getAttribute(TableMiddleware::GEOMETRY_ATTRIBUTE);

        /** @var QueryBuilder */
        $query = $request->getAttribute(QueryMiddleware::QUERY_ATTRIBUTE);

        if (is_null($geometryColumn)) {
            return new JsonResponse(null);
        }

        if (is_null($table->getNamespaceName())) {
            $qsz = sprintf(
                'SELECT ST_AsGeoJSON(ST_EstimatedExtent(%s, %s), 6);',
                $connection->getDatabasePlatform()->quoteStringLiteral($table->getName()),
                $connection->getDatabasePlatform()->quoteStringLiteral($geometryColumn->getName())
            );
        } else {
            $qsz = sprintf(
                'SELECT ST_AsGeoJSON(ST_EstimatedExtent(%s, %s, %s), 6);',
                $connection->getDatabasePlatform()->quoteStringLiteral($table->getNamespaceName()),
                $connection->getDatabasePlatform()->quoteStringLiteral($table->getName()),
                $connection->getDatabasePlatform()->quoteStringLiteral($geometryColumn->getName())
            );
        }
        $result = $connection->executeQuery($qsz)->fetchOne();
        if (!is_null($result)) {
            return new JsonResponse(json_decode($result));
        }

        $query
            ->select(
                sprintf(
                    'ST_AsGeoJSON(ST_Extent(a.%s), 6)',
                    $connection->getDatabasePlatform()->quoteIdentifier($geometryColumn->getName())
                )
            );
        $result = $query->executeQuery()->fetchOne();

        return new JsonResponse(!is_null($result) ? json_decode($result) : null);
    }
}
