<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Model\MainTable;
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

        $table = new MainTable($adapter, $config['config']);
        $columns = $table->getColumns();

        $thematic = new Thematic($adapter, $config['config']);

        return new HtmlResponse($this->renderer->render(
            'app::map',
            [
                'config'         => $config['config'],
                'configId'       => $config['custom'],
                'table'          => $table,
                'columns'        => $columns,
                'keyColumn'      => $table->getKeyColumn(),
                'geometryColumn' => $table->getGeometryColumn(),
                'thematic'       => $thematic,
                'baselayers'     => self::getBaselayers($config['global']['baselayers'] ?? []),
            ]
        ));
    }

    private static function getBaselayers(array $configBaselayers): array
    {
        $baselayers = $configBaselayers;

        if (count($baselayers) === 0) {
            $baselayers = [
                'osm' => [
                    'name'         => 'OpenStreetMap',
                    'url'          => 'https://{a-c}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    'attributions' => [
                        '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors.',
                    ],
                    'maxZoom' => 19,
                ],
            ];
        }

        return $baselayers;
    }
}
