<?php

declare(strict_types=1);

namespace App\Handler\API\Database;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Middleware\TableMiddleware;
use App\Model\Table\Main as MainTable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;

class TableHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);

        $count = $table->getCount();

        $result = array_merge(
            ['count' => $count],
            $table->toArray()
        );

        return new JsonResponse($result);
    }
}
