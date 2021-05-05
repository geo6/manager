<?php

declare(strict_types=1);

namespace API\Middleware;

use Psr\Container\ContainerInterface;
use stdClass;

class TableMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): TableMiddleware
    {
        $config = $container->get('config');

        $table = $config['table'];
        $primaryKeyColumn = $config['primaryKeyColumn'] ?? null;
        $readonlyColumns = $config['readonlyColumns'] ?? [];
        $fileColumns = $config['fileColumns'] ?? [];
        $relations = $config['relations'] ?? [];
        $limit = $config['limit'];

        $options = new stdClass;
        $options->readonlyColumns = $readonlyColumns;
        $options->fileColumns = $fileColumns;
        $options->limit = $limit;

        return new TableMiddleware($table, $primaryKeyColumn, $relations, $options);
    }
}
