<?php

declare (strict_types = 1);

namespace App\Handler\API\Database;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Model\Record;
use App\Model\Table;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;

class RecordsHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);

        $id = $request->getAttribute('id');

        $params = $request->getQueryParams();

        $table = new Table($adapter, $config['config']);

        switch ($request->getMethod()) {
            case 'GET':
                if (is_null($id)) {
                    return new JsonResponse([
                        'type'     => 'FeatureCollection',
                        'features' => $table->getRecords(
                            isset($params['order']) ? $params['order'] : null,
                            isset($params['limit']) ? intval($params['limit']) : null,
                            null,
                            true
                        ),
                    ]);
                } else {
                    return new JsonResponse($table->getRecord(intval($id))->toGeoJSON());
                }
            case 'PUT':
                $data = $request->getParsedBody();
                $record = new Record($adapter, $table, intval($id));
                $update = $record->update($data, true);

                return new JsonResponse($record->toGeoJSON());
            case 'DELETE':
                $data = $request->getParsedBody();
                $record = new Record($adapter, $table, intval($id));
                $delete = $record->delete(true);

                return new JsonResponse((object)[]);
        }
    }
}
