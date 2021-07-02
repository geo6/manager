<?php

declare(strict_types=1);

namespace App\Handler;

use API\Middleware\DatabaseMiddleware;
use API\Middleware\QueryMiddleware;
use API\Middleware\TableMiddleware;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
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
        /** @var Column */
        $primaryKey = $request->getAttribute(TableMiddleware::PRIMARYKEY_ATTRIBUTE);

        /** @var QueryBuilder */
        $query = $request->getAttribute(QueryMiddleware::QUERY_ATTRIBUTE);

        /** @var int */
        $limit = $request->getAttribute(TableMiddleware::LIMIT_ATTRIBUTE);

        /** @var int */
        $offset = $request->getAttribute('offset', 0);
        $offset = intval(floor(intval($offset) / $limit) * $limit);

        $params = $request->getQueryParams();

        $sort = isset($params['sort']) ? $params['sort'] : $table->getName().'_'.$primaryKey->getName();
        $order = isset($params['order']) && in_array(strtolower($params['order']), ['asc', 'desc']) ? strtolower($params['order']) : 'asc';

        $stmt = $query->executeQuery();
        $count = $stmt->rowCount();

        $query
            ->setMaxResults($limit)
            ->setFirstResult($offset);

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
                'filter'  => [
                    'active' => isset($params['search']) && strlen($params['search']) > 0,
                    'count'  => $count,
                ],
            ]
        ));
    }
}
