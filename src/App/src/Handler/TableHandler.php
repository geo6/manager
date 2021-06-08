<?php

declare(strict_types=1);

namespace App\Handler;

use API\Middleware\DatabaseMiddleware;
use API\Middleware\TableMiddleware;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TableHandler implements RequestHandlerInterface
{
    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    public function __construct(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Connection */
        $connection = $request->getAttribute(DatabaseMiddleware::CONNECTION_ATTRIBUTE);

        /** @var Table */
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);
        /** @var string */
        $primaryKey = $request->getAttribute(TableMiddleware::PRIMARYKEY_ATTRIBUTE);
        /** @var array */
        $foreignKeys = $request->getAttribute(TableMiddleware::FOREIGNKEYS_ATTRIBUTE);

        /** @var int */
        $limit = $request->getAttribute(TableMiddleware::LIMIT_ATTRIBUTE);

        /** @var int */
        $offset = $request->getAttribute('offset', 0);
        $offset = intval(floor(intval($offset) / $limit) * $limit);

        $params = $request->getQueryParams();

        $sort = isset($params['sort']) ? $params['sort'] : $table->getName().'_'.$primaryKey;
        $order = isset($params['order']) && in_array(strtolower($params['order']), ['asc', 'desc']) ? strtolower($params['order']) : 'asc';

        $columns = $table->getColumns();
        $select = [];
        foreach ($columns as $column) {
            $name = $column->getQuotedName($connection->getDatabasePlatform());
            $select[] = sprintf(
                '%s as %s_%s',
                $column->getType()->convertToPHPValueSQL(sprintf('a.%s', $name), $connection->getDatabasePlatform()),
                $table->getName(),
                $name
            );
        }

        $query = $connection->createQueryBuilder();
        $query
            ->select(...$select)
            ->from($table->getName(), 'a')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy($sort, $order);

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

        $stmt = $query->executeQuery();
        $records = $stmt->fetchAllAssociative();

        return new HtmlResponse($this->renderer->render(
            'app::table',
            [
                'sort'    => $sort,
                'order'   => $order,
                'offset'  => $offset,
                'limit'   => $limit,
                'records' => $records,
            ]
        ));
    }
}
