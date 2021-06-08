<?php

declare(strict_types=1);

namespace API\Middleware;

use Psr\Container\ContainerInterface;

class TableMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): TableMiddleware
    {
        $config = $container->get('config');

        $table = $config['table'];
        $primaryKeyColumn = $config['primaryKeyColumn'] ?? null;
        $relations = $config['relations'] ?? [];
        $limit = $config['limit'];

        return new TableMiddleware($table, $primaryKeyColumn, $relations, $limit);
    }
}
