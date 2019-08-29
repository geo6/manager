<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Middleware\TableMiddleware;
use App\Model\Column\Column;
use App\Model\Table;
use App\Model\Thematic;
use Blast\BaseUrl\BaseUrlMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class TableHandler implements RequestHandlerInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    /**
     * @param RouterInterface           $router
     * @param TemplateRendererInterface $renderer
     */
    public function __construct(RouterInterface $router, TemplateRendererInterface $renderer)
    {
        $this->router = $router;
        $this->renderer = $renderer;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $basePath = $request->getAttribute(BaseUrlMiddleware::BASE_PATH);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);

        $params = $request->getQueryParams();

        $total = $table->getCount();

        $columns = $table->getColumns();

        // Get foreign tables information
        $foreignTables = [];
        foreach ($columns as $column) {
            if ($column->isForeignKey() === true) {
                $reference = $column->getReferenceColumn();

                $tableName = $reference->getTableName();
                $configColumns = array_filter($config['config']['columns'] ?? [], function ($key) use ($tableName) {
                    if (preg_match('/^(\w+)\.(\w+)$/', $key, $matches) === 1) {
                        return $matches[1] === $tableName;
                    }

                    return false;
                }, ARRAY_FILTER_USE_KEY);

                $foreignTables[$column->getName()] = new Table(
                    $adapter,
                    $reference->getSchemaName(),
                    $reference->getTableName(),
                    [
                        'columns' => $configColumns,
                    ]
                );
            }
        }

        // Parameter: limit
        $limit = isset($config['config']['limit']) ? intval($config['config']['limit']) : 100;

        // Parameter: offset
        $offset = $request->getAttribute('offset', 0);
        $offset = intval(floor(intval($offset) / $limit) * $limit);

        // Parameter: filter
        $filter = isset($params['filter']) && strlen($params['filter']) > 0 ? $params['filter'] : null;

        // Parameter: sort
        if (isset($params['sort']) && preg_match('/^(\w+)\.(\w+)$/', $params['sort'], $matches) === 1) {
            $sort = $params['sort'];

            if ($matches[1] === $table->getName()) {
                $sortColumn = array_filter($columns, function (Column $column) use ($matches) {
                    return $column->getName() === $matches[2];
                });

                if (count($sortColumn) !== 1) {
                    $sort = null;
                }
            } else {
                $sortTable = array_filter($foreignTables, function (Table $table) use ($matches) {
                    return $table->getName() === $matches[1];
                });

                if (count($sortTable) === 1) {
                    $sortTable = current($sortTable);
                    $sortColumn = array_filter($sortTable->getColumns(), function (Column $column) use ($matches) {
                        return $column->getName() === $matches[2];
                    });

                    if (count($sortColumn) !== 1) {
                        $sort = null;
                    }
                } else {
                    $sort = null;
                }
            }
        } else {
            $sort = null;
        }

        // Parameter: order
        $order = isset($params['order']) && in_array(strtolower($params['order']), ['asc', 'desc']) ? strtolower($params['order']) : 'asc';

        // If requested offset greater than $count, redirect to last page.
        $count = $table->getCount($filter);
        if ($offset > $count) {
            $offset = intval(floor($count / $limit) * $limit);

            $redirect = ($basePath !== '/' ? $basePath : '');
            $redirect .= $this->router->generateUri(
                'table',
                [
                'config' => $config['custom'],
                'offset' => $offset,
                ]
            );

            return new RedirectResponse(
                $redirect . '?' . http_build_query(
                    [
                    'filter' => $filter,
                    'sort'   => $params['sort'],
                    'order'  => $params['order'],
                    ]
                )
            );
        }

        // Get records
        $records = $table->getRecords(
            $filter,
            !is_null($sort) ? sprintf('%s %s', $sort, $order) : null,
            $limit,
            $offset
        );

        return new HtmlResponse(
            $this->renderer->render(
                'app::table',
                [
                'configId'      => $config['custom'],
                'limit'         => $limit,
                'offset'        => $offset,
                'sort'          => $sort,
                'order'         => $order,
                'total'         => $total,
                'count'         => $count,
                'table'         => $table,
                'records'       => $records,
                'filter'        => $filter,
                'thematic'      => new Thematic($adapter, $config['config'] ?? null),
                'foreignTables' => $foreignTables,
                'adapter'       => $config['config']['adapter'],
                ]
            )
        );
    }
}
