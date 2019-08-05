<?php

declare(strict_types=1);

namespace App\Handler\API\Database;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Model\Record;
use App\Model\Table\Main as MainTable;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class RecordsHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);

        $id = $request->getAttribute('id');

        $params = $request->getQueryParams();

        $table = new MainTable($adapter, $config['config']);

        switch ($request->getMethod()) {
            case 'GET':
                if (!is_null($id)) {
                    $record = new Record($adapter, $table, intval($id));
                    return new JsonResponse($record->toGeoJSON());
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
                $record = new Record($adapter, $table);

                try {
                    $insert = $record->insert($data, true);
                } catch (Exception $e) {
                    return new JsonResponse(['error' => $e->getMessage()], 500);
                }

                return new JsonResponse($record->toGeoJSON());

            case 'PUT':
                $data = $request->getParsedBody();
                $record = new Record($adapter, $table, intval($id));
                $update = $record->update($data, true);

                return new JsonResponse($record->toGeoJSON());

            case 'DELETE':
                $data = $request->getParsedBody();
                $record = new Record($adapter, $table, intval($id));
                $delete = $record->delete(true);

                return new JsonResponse((object) []);

            default:
                return new EmptyResponse(405);
        }
    }
}
