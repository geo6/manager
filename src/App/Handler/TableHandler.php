<?php

declare (strict_types = 1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Model\Table;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;

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
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);

        $params = $request->getQueryParams();

        $offset = $request->getAttribute('offset', 0);
        $offset = intval(floor(intval($offset) / $config['config']['limit']) * $config['config']['limit']);

        $order = [
            'column' => 'id',
            'order'  => 'asc',
        ];

        $table = new Table($adapter, $config['config']);
        $count = $table->getCount();
        $columns = $table->getColumns();

        if (isset($params['sort'])) {
            $result = array_filter($columns, function ($column) use ($params) {
                return $column->getName() === $params['sort'];
            });

            if (count($result) > 0) {
                $order = [
                    'column' => current($result)->getName(),
                    'order'  => 'asc',
                ];

                if (isset($params['order']) && in_array(strtolower($params['order']), ['asc', 'desc'])) {
                    $order['order'] = strtolower($params['order']);
                }
            } else {
                $order = [
                    'column' => 'id',
                    'order'  => 'asc',
                ];
            }
        }

        return new HtmlResponse($this->renderer->render(
            'app::table',
            [
                'config'         => $config['custom'],
                'pages'          => floor($count / $config['config']['limit']),
                'limit'          => $config['config']['limit'],
                'offset'         => $offset,
                'order'          => $order,
                'count'          => $count,
                'keyColumn'      => $table->getKeyColumn(),
                'geometryColumn' => $table->getGeometryColumn(),
                'table'          => $table,
                'columns'        => $columns,
                'records'        => $table->getRecords(
                    $order['column'] . ' ' . $order['order'],
                    $config['config']['limit'],
                    $offset
                ),
            ]
        ));
    }
}
