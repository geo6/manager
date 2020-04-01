<?php

declare(strict_types=1);

namespace App\Handler\API\Database;

use App\Middleware\TableMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
