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

class GetHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Connection */
        $connection = $request->getAttribute(DatabaseMiddleware::CONNECTION_ATTRIBUTE);

        /** @var Table */
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);

        /** @var QueryBuilder */
        $query = $request->getAttribute(QueryMiddleware::QUERY_ATTRIBUTE);

        /** @var Table */
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);
        /** @var Column */
        $primaryKey = $request->getAttribute(TableMiddleware::PRIMARYKEY_ATTRIBUTE);
        /** @var array */
        $foreignKeys = $request->getAttribute(TableMiddleware::FOREIGNKEYS_ATTRIBUTE);
        /** @var Column|null */
        $geometryColumn = $request->getAttribute(TableMiddleware::GEOMETRY_ATTRIBUTE);

        /** @var int|null */
        $id = $request->getAttribute('id');

        $columns = $table->getColumns();

        if (!is_null($id)) {
            $query
                ->where(
                    $query->expr()->eq(sprintf('a.%s', $primaryKey->getName()), ':id')
                )
                ->setParameter('id', $id);
        }

        $stmt = $query->executeQuery();
        $records = $stmt->fetchAllAssociative();

        $features = array_map(
            function ($record) use ($connection, $table, $columns, $primaryKey, $foreignKeys, $geometryColumn) {
                foreach ($columns as $column) {
                    $name = sprintf('%s_%s', $table->getName(), $column->getName());
                    switch ($column->getType()->getName()) {
                        case 'geometry':
                            $record[$name] = $column->getType()->convertToPHPValue($record[$name], $connection->getDatabasePlatform());
                            break;
                    }
                }
                foreach ($foreignKeys as $fk) {
                    foreach ($fk['foreignTable']->getColumns() as $column) {
                        $name = sprintf('%s_%s', $fk['foreignTable']->getName(), $column->getName());
                        switch ($column->getType()->getName()) {
                            case 'geometry':
                                $record[$name] = $column->getType()->convertToPHPValue($record[$name], $connection->getDatabasePlatform());
                                break;
                        }
                    }
                }

                $_id = sprintf('%s_%s', $table->getName(), $primaryKey->getName());
                $id = $record[$_id];
                unset($record[$_id]);

                $_geometry = sprintf('%s_%s', $table->getName(), $geometryColumn->getName());
                if (isset($record[$_geometry])) {
                    $geometry = $record[$_geometry];
                    unset($record[$_geometry]);
                }

                return [
                    'type'       => 'Feature',
                    'id'         => $id,
                    'properties' => $record,
                    'geometry'   => $geometry ?? null,
                ];
            },
            $records
        );

        return new JsonResponse(is_null($id) ? ['type' => 'FeatureCollection', 'features' => $features] : current($features));
    }
}
