<?php

declare(strict_types=1);

namespace API\Handler\Object;

use API\Middleware\DatabaseMiddleware;
use API\Middleware\TableMiddleware;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PatchPutHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Connection */
        $connection = $request->getAttribute(DatabaseMiddleware::CONNECTION_ATTRIBUTE);

        /** @var Table */
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);
        /** @var Column */
        $primaryKey = $request->getAttribute(TableMiddleware::PRIMARYKEY_ATTRIBUTE);

        /** @var int */
        $id = $request->getAttribute('id');

        /** @var Column[] */
        $columns = array_filter($table->getColumns(), function (Column $column) use ($primaryKey) {
            return $column->getName() !== $primaryKey->getName();
        });

        $method = $request->getMethod();

        $data = $request->getParsedBody();

        $query = $connection->createQueryBuilder();
        $query
            ->update($table->getName())
            ->where(
                $query->expr()->eq($primaryKey->getName(), ':id')
            )
            ->setParameter('id', $id);

        foreach ($columns as $column) {
            $name = $column->getName();
            $param = $query->createNamedParameter($name, $column->getType());

            if (isset($data[$name])) {
                if (strlen((string) $data[$name]) > 0) {
                    $value = $column->getType()->convertToDatabaseValueSQL($data[$name], $connection->getDatabasePlatform());
                    $query->set($name, $param)->setParameter(substr($param, 1), $value, $column->getType());
                } else {
                    $query->set($name, $param)->setParameter(substr($param, 1), null, $column->getType());
                }
            } elseif ($method === 'PUT') {
                $query->set($name, $param)->setParameter(substr($param, 1), null, $column->getType());
            }
        }

        $stmt = $query->executeStatement();

        return new JsonResponse($stmt);
    }
}
