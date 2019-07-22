<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
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
    /** @var RouterInterface */
    private $router;

    /** @var TemplateRendererInterface */
    private $renderer;

    public function __construct(RouterInterface $router, TemplateRendererInterface $renderer)
    {
        $this->router = $router;
        $this->renderer = $renderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);
        $basePath = $request->getAttribute(BaseUrlMiddleware::BASE_PATH);

        $params = $request->getQueryParams();

        $offset = $request->getAttribute('offset', 0);
        $offset = intval(floor(intval($offset) / $config['config']['limit']) * $config['config']['limit']);

        $filter = isset($params['filter']) && strlen($params['filter']) > 0 ? $params['filter'] : null;

        $table = new Table($adapter, $config['config']);
        $total = $table->getCount();
        $count = $table->getCount($filter);
        $columns = $table->getColumns();

        $order = [
            'column' => 'id',
            'order'  => 'asc',
        ];

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

        if ($offset > $count) {
            $offset = intval(floor($count / $config['config']['limit']) * $config['config']['limit']);

            $redirect = ($basePath !== '/' ? $basePath : '');
            $redirect .= $this->router->generateUri('table', [
                'config' => $config['custom'],
                'offset' => $offset,
            ]);

            return new RedirectResponse($redirect . '?' . http_build_query([
                'filter' => $filter,
                'sort'   => $order['column'],
                'order'  => $order['order'],
            ]));
        }

        $thematic = new Thematic($adapter, $config['config']);

        $records = $table->getRecords(
            $filter,
            $order['column'] . ' ' . $order['order'],
            $config['config']['limit'],
            $offset
        );

        return new HtmlResponse($this->renderer->render(
            'app::table',
            [
                'config'         => $config['custom'],
                'pages'          => floor($count / $config['config']['limit']),
                'limit'          => $config['config']['limit'],
                'offset'         => $offset,
                'order'          => $order,
                'total'          => $total,
                'count'          => $count,
                'keyColumn'      => $table->getKeyColumn(),
                'geometryColumn' => $table->getGeometryColumn(),
                'table'          => $table,
                'columns'        => $columns,
                'records'        => $records,
                'filter'         => $filter,
                'thematic'       => $thematic,
            ]
        ));
    }
}
