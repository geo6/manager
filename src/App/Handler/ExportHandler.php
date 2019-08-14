<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Middleware\TableMiddleware;
use App\Model\Table;
use App\Model\Table\Main as MainTable;
use App\Model\Thematic;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class ExportHandler implements RequestHandlerInterface
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
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);

        $format = $request->getAttribute('format');

        if (!in_array($format, ['json', 'geojson'])) {
            return new EmptyResponse(404);
        }

        switch ($format) {
            case 'geojson':
            case 'json':
                $response = self::geojson($table);
                $format = 'json';
                break;
        }

        $name = sprintf(
            '%s-%s.%s',
            $table->getName(),
            date('Ymd'),
            $format
        );

        return $response->withHeader(
            'Content-Disposition',
            sprintf('attachment; filename="%s"', $name)
        );
    }

    private static function geojson(MainTable $table): JsonResponse
    {
        return new JsonResponse([
            'type'     => 'FeatureCollection',
            'features' => $table->getRecords(
                null,
                null,
                null,
                null,
                true
            ),
        ]);
    }
}
