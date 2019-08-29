<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\TableMiddleware;
use App\Model\Table;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class ExportHandler implements RequestHandlerInterface
{
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

    private static function geojson(Table $table): JsonResponse
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
