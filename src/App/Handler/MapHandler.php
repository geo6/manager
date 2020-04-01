<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Middleware\TableMiddleware;
use App\Model\Table;
use App\Model\Thematic;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;

class MapHandler implements RequestHandlerInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var TemplateRendererInterface */
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
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);

        $columns = $table->getColumns();

        $foreignTables = [];
        foreach ($columns as $column) {
            if ($column->isForeignKey() === true) {
                $reference = $column->getReferenceColumn();

                $foreignTables[$column->getName()] = new Table($adapter, $reference->getSchemaName(), $reference->getTableName());
            }
        }

        $thematic = new Thematic($adapter, $config['config'] ?? null);

        return new HtmlResponse($this->renderer->render(
            'app::map',
            [
                'config'        => $config['config'],
                'configId'      => $config['custom'],
                'table'         => $table,
                'thematic'      => $thematic,
                'baselayers'    => self::getBaselayers($config['global']['baselayers'] ?? []),
                'foreignTables' => $foreignTables,
            ]
        ));
    }

    /**
     * @param array $configBaselayers
     *
     * @return array
     */
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
