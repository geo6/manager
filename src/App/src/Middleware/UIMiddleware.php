<?php

declare(strict_types=1);

namespace App\Middleware;

use API\Middleware\QueryMiddleware;
use API\Middleware\TableMiddleware;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UIMiddleware implements MiddlewareInterface
{
    /** @var TemplateRendererInterface */
    private $template;
    /** @var string[] */
    private $fileColumns;
    /** @var string[] */
    private $readonlyColumns;
    /** @var array */
    private $fileConfig;
    /** @var array */
    private $themeConfig;

    public function __construct(TemplateRendererInterface $template, array $columns, array $file, array $theme)
    {
        $this->template = $template;

        $this->fileColumns = $columns['file'] ?? [];
        $this->readonlyColumns = $columns['readonly'] ?? [];

        $this->fileConfig = $file;
        $this->themeConfig = $theme;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Table */
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);
        /** @var bool */
        $isView = $request->getAttribute(TableMiddleware::ISVIEW_ATTRIBUTE);
        /** @var Column */
        $primaryKey = $request->getAttribute(TableMiddleware::PRIMARYKEY_ATTRIBUTE);
        /** @var array */
        $foreignKeys = $request->getAttribute(TableMiddleware::FOREIGNKEYS_ATTRIBUTE);
        /** @var string|null */
        $geometryColumn = $request->getAttribute(TableMiddleware::GEOMETRY_ATTRIBUTE);
        /** @var int */
        $count = $request->getAttribute(TableMiddleware::COUNT_ATTRIBUTE);
        /** @var QueryBuilder */
        $query = $request->getAttribute(QueryMiddleware::QUERY_ATTRIBUTE);

        /** @var RouteResult */
        $route = $request->getAttribute(RouteResult::class);

        /** @var string[] */
        $geometryColumns = array_values(
            array_map(
                function (Column $column) {
                    return $column->getName();
                },
                array_filter($table->getColumns(), function (Column $column) {
                    return in_array($column->getType()->getName(), ['geometry', 'geography']);
                })
            )
        );

        $params = $request->getQueryParams();

        // Add `table` parameter
        $this->template->addDefaultParam(
            $this->template::TEMPLATE_ALL,
            'table',
            [
                'table'       => $table,
                'isView'      => $isView,
                'primaryKey'  => $primaryKey,
                'foreignKeys' => $foreignKeys,
                'geometry'    => $geometryColumn,
                'count'       => $count,
                'columns'     => [
                    'readonly' => [
                        $primaryKey->getName(),
                        ...$this->readonlyColumns,
                        ...$geometryColumns,
                    ],
                    'file'     => $this->fileColumns,
                    'geometry' => $geometryColumns,
                ],
            ]
        );

        // Add `sql` parameter
        $sql = [
            'pretty' => (new SqlFormatter())->format($query->getSQL()),
            'raw '   => (new SqlFormatter(new NullHighlighter()))->format($query->getSQL()),
            'params' => [],
        ];
        if (isset($params['search']) && strlen($params['search']) > 0) {
            $sql['params']['search'] = sprintf('%%%s%%', $params['search']);
        }
        $this->template->addDefaultParam($this->template::TEMPLATE_ALL, 'sql', $sql);

        // Add `ui` parameter
        $this->template->addDefaultParam(
            $this->template::TEMPLATE_ALL,
            'ui',
            [
                'file'   => $this->fileConfig,
                'route'  => $route->getMatchedRoute(),
                'search' => isset($params['search']) && strlen($params['search']) > 0 ? $params['search'] : null,
                'theme'  => $this->themeConfig,
            ]
        );

        return $handler->handle($request);
    }
}
