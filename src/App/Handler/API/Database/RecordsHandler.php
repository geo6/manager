<?php

declare(strict_types=1);

namespace App\Handler\API\Database;

use App\Formatter\GeoJSON;
use App\Middleware\DbAdapterMiddleware;
use App\Middleware\TableMiddleware;
use App\Model\Record;
use Exception;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RecordsHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);

        $id = $request->getAttribute('id');

        $params = $request->getQueryParams();

        switch ($request->getMethod()) {
            case 'GET':
                if (!is_null($id)) {
                    try {
                        $record = new Record($adapter, $table, intval($id));
                    } catch (Exception $e) {
                        return new JsonResponse(['error' => $e->getMessage()], 500);
                    }

                    return new JsonResponse(GeoJSON::format($record, false));
                }

                return new JsonResponse([
                    'type'     => 'FeatureCollection',
                    'features' => $table->getRecords(
                        isset($params['filter']) ? $params['filter'] : null,
                        isset($params['order']) ? $params['order'] : null,
                        isset($params['limit']) ? intval($params['limit']) : null,
                        null,
                        true
                    ),
                ]);

            case 'POST':
                $data = $request->getParsedBody();

                try {
                    $record = new Record($adapter, $table);
                    $insert = $record->insert($data, true);
                } catch (Exception $e) {
                    return new JsonResponse(['error' => $e->getMessage()], 500);
                }

                return new JsonResponse(GeoJSON::format($record, false));

            case 'PUT':
                $data = $request->getParsedBody();

                try {
                    $record = new Record($adapter, $table, intval($id));
                    $update = $record->update($data, true);
                } catch (Exception $e) {
                    return new JsonResponse(['error' => $e->getMessage()], 500);
                }

                return new JsonResponse(GeoJSON::format($record, false));

            case 'DELETE':
                $data = $request->getParsedBody();

                try {
                    $record = new Record($adapter, $table, intval($id));
                    $delete = $record->delete(true);
                } catch (Exception $e) {
                    return new JsonResponse(['error' => $e->getMessage()], 500);
                }

                return new JsonResponse((object) []);

            default:
                return new EmptyResponse(405);
        }
    }
}
