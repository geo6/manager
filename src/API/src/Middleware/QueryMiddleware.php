<?php

declare(strict_types=1);

namespace API\Middleware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class QueryMiddleware implements MiddlewareInterface
{
    public const QUERY_ATTRIBUTE = 'sqlQuery';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Connection */
        $connection = $request->getAttribute(DatabaseMiddleware::CONNECTION_ATTRIBUTE);

        /** @var Table */
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);
        /** @var Column */
        $primaryKey = $request->getAttribute(TableMiddleware::PRIMARYKEY_ATTRIBUTE);
        /** @var array */
        $foreignKeys = $request->getAttribute(TableMiddleware::FOREIGNKEYS_ATTRIBUTE);

        $params = $request->getQueryParams();

        $sort = isset($params['sort']) ? $params['sort'] : $table->getName().'_'.$primaryKey->getName();
        $order = isset($params['order']) && in_array(strtolower($params['order']), ['asc', 'desc']) ? strtolower($params['order']) : 'asc';

        $select = [];
        $where = [];

        $columns = $table->getColumns();
        foreach ($columns as $column) {
            $name = $column->getQuotedName($connection->getDatabasePlatform());
            $select[] = sprintf(
                '%s as %s_%s',
                $column->getType()->convertToPHPValueSQL(sprintf('a.%s', $name), $connection->getDatabasePlatform()),
                $table->getName(),
                $name
            );

            if (isset($params['search']) && strlen($params['search']) > 0 && $column->getType()->getName() === 'string') {
                $where[] = sprintf('a.%s ILIKE :search', $name);
            }
        }

        $query = $connection->createQueryBuilder()
            ->select(...$select)
            ->from($table->getName(), 'a')
            ->orderBy($sort, $order);

        if (isset($params['search']) && strlen($params['search']) > 0) {
            $query
                ->where($query->expr()->or(...$where))
                ->setParameter('search', sprintf('%%%s%%', $params['search']));
        }

        foreach ($foreignKeys as $i => $fk) {
            $foreignColumns = $fk['foreignTable']->getColumns();
            $foreignSelect = [];
            foreach ($foreignColumns as $foreignColumn) {
                $name = $foreignColumn->getQuotedName($connection->getDatabasePlatform());
                $foreignSelect[] = sprintf(
                    '%s as %s_%s',
                    $foreignColumn->getType()->convertToPHPValueSQL(sprintf('b%d.%s', $i, $name), $connection->getDatabasePlatform()),
                    $fk['foreignTable']->getName(),
                    $name
                );
            }

            $query
                ->leftJoin(
                    'a',
                    $fk['foreignTable']->getName(),
                    sprintf('b%d', $i),
                    sprintf('a.%s = b%d.%s', $fk['localColumn'], $i, $fk['foreignColumn'])
                )
                ->addSelect(...$foreignSelect);
        }

        return $handler->handle($request->withAttribute(self::QUERY_ATTRIBUTE, $query));
    }
}
