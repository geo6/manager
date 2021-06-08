<?php

declare(strict_types=1);

namespace API\Handler\Object;

use API\Middleware\DatabaseMiddleware;
use API\Middleware\TableMiddleware;
use Doctrine\DBAL\Connection;
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
        /** @var string */
        $primaryKey = $request->getAttribute(TableMiddleware::PRIMARYKEY_ATTRIBUTE);
        /** @var string|null */
        $geometryColumn = $request->getAttribute(TableMiddleware::GEOMETRY_ATTRIBUTE);

        /** @var int|null */
        $id = $request->getAttribute('id');

        $columns = $table->getColumns();

        $select = [];
        foreach ($columns as $column) {
            $name = $column->getQuotedName($connection->getDatabasePlatform());
            $select[] = sprintf(
                '%s as %s',
                $column->getType()->convertToPHPValueSQL(sprintf('a.%s', $name), $connection->getDatabasePlatform()),
                $name
            );
        }

        $query = $connection->createQueryBuilder();
        $query->select($select)->from($table->getName(), 'a');
        if (!is_null($id)) {
            $query->where('id = ?')->setParameter(0, $id);
        }

        $stmt = $query->execute();
        $records = $stmt->fetchAllAssociative();

        $features = array_map(
            function ($record) use ($connection, $columns, $primaryKey, $geometryColumn) {
                $properties = [];
                foreach ($columns as $column) {
                    $name = $column->getName();
                    if (!is_null($geometryColumn) && $name === $geometryColumn) {
                        $geometry = $column->getType()->convertToPHPValue($record[$name], $connection->getDatabasePlatform());
                    } elseif ($name !== $primaryKey) {
                        $properties[$name] = $column->getType()->convertToPHPValue($record[$name], $connection->getDatabasePlatform());
                    }
                }

                return [
                    'type'       => 'Feature',
                    'id'         => $record[$primaryKey],
                    'properties' => $properties,
                    'geometry'   => $geometry ?? null,
                ];
            },
            $records
        );

        return new JsonResponse(is_null($id) ? ['type' => 'FeatureCollection', 'features' => $features] : current($features));
    }
}
