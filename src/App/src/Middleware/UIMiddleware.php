<?php

declare(strict_types=1);

namespace App\Middleware;

use API\Middleware\TableMiddleware;
use Doctrine\DBAL\Schema\Column;
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

    public function __construct(TemplateRendererInterface $template, array $columns, array $file)
    {
        $this->template = $template;

        $this->fileColumns = $columns['file'] ?? [];
        $this->readonlyColumns = $columns['readonly'] ?? [];

        $this->fileConfig = $file;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Table */
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);
        /** @var bool */
        $isView = $request->getAttribute(TableMiddleware::ISVIEW_ATTRIBUTE);
        /** @var string */
        $primaryKey = $request->getAttribute(TableMiddleware::PRIMARYKEY_ATTRIBUTE);
        /** @var array */
        $foreignKeys = $request->getAttribute(TableMiddleware::FOREIGNKEYS_ATTRIBUTE);
        /** @var int */
        $count = $request->getAttribute(TableMiddleware::COUNT_ATTRIBUTE);

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

        $search = isset($params['search']) ? $params['search'] : null;

        $this->template->addDefaultParam(
            $this->template::TEMPLATE_ALL,
            'table',
            [
                'table'       => $table,
                'isView'      => $isView,
                'primaryKey'  => $primaryKey,
                'foreignKeys' => $foreignKeys,
                'count'       => $count,
                'columns'     => [
                    'readonly' => [
                        $primaryKey,
                        ...$this->readonlyColumns,
                        ...$geometryColumns,
                    ],
                    'file'     => $this->fileColumns,
                    'geometry' => $geometryColumns,
                ],
            ]
        );

        $this->template->addDefaultParam(
            $this->template::TEMPLATE_ALL,
            'ui',
            [
                'search' => $search,
                'file'   => $this->fileConfig,
            ]
        );

        return $handler->handle($request);
    }
}
