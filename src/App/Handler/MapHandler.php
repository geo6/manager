<?php

declare(strict_types = 1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Model\Table;
use App\Model\Thematic;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;

class MapHandler implements RequestHandlerInterface
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

        $table = new Table($adapter, $config['config']);
        $columns = $table->getColumns();

        $thematic = new Thematic($adapter, $config['config']);

        return new HtmlResponse($this->renderer->render(
            'app::map',
            [
                'config'         => $config['custom'],
                'table'          => $table,
                'columns'        => $columns,
                'keyColumn'      => $table->getKeyColumn(),
                'geometryColumn' => $table->getGeometryColumn(),
                'thematic'       => $thematic,
            ]
        ));
    }
}
