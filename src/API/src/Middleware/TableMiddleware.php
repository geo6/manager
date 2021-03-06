<?php

declare(strict_types=1);

namespace API\Middleware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\View;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TableMiddleware implements MiddlewareInterface
{
    public const TABLE_ATTRIBUTE = 'table';
    public const ISVIEW_ATTRIBUTE = 'table.isview';
    public const PRIMARYKEY_ATTRIBUTE = 'table.pk';
    public const FOREIGNKEYS_ATTRIBUTE = 'table.fk';
    public const GEOMETRY_ATTRIBUTE = 'table.geometry';
    public const COUNT_ATTRIBUTE = 'table.count';
    public const LIMIT_ATTRIBUTE = 'table.limit';

    private string $table;
    private ?string $primaryKeyColumn;
    private array $relations;
    private int $limit;

    public function __construct(string $table, ?string $primaryKeyColumn, array $relations, int $limit)
    {
        $this->table = $table;
        $this->primaryKeyColumn = $primaryKeyColumn;
        $this->relations = $relations;
        $this->limit = $limit;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Connection */
        $connection = $request->getAttribute(DatabaseMiddleware::CONNECTION_ATTRIBUTE);

        $schema = $connection->createSchemaManager();

        $table = $schema->listTableDetails($this->table);

        // Detect if the "table" is a VIEW
        $views = $schema->listViews();
        $isView = count(
            array_filter($views, function (View $view) use ($connection, $table) {
                $platform = $connection->getDatabasePlatform();
                $namespace = $table->getNamespaceName();

                if (is_null($namespace) && $platform->getName() === 'postgresql') {
                    $namespace = $platform->getDefaultSchemaName();
                }

                return $namespace === $view->getNamespaceName()
                    && $table->getShortestName($platform->getDefaultSchemaName()) === $view->getShortestName($platform->getDefaultSchemaName());
            })
        ) === 1;

        // Get primary key column from database structure or configuration
        if (!is_null($table->getPrimaryKey())) {
            $primaryKeyColumns = $table->getPrimaryKeyColumns();
            if (count($primaryKeyColumns) > 1) {
                throw new Exception('This application doesn\'t support multiple columns primary key.');
            }
            $primaryKey = current($primaryKeyColumns);
        } elseif (!is_null($this->primaryKeyColumn)) {
            $primaryKey = $table->getColumn($this->primaryKeyColumn);
        } else {
            throw new Exception('You need to configure the primary key.');
        }

        // Get geometry column (first geometry/geography column available)
        $geometryColumns = array_filter($table->getColumns(), function (Column $column) { return in_array($column->getType()->getName(), ['geometry', 'geography']); });
        if (count($geometryColumns) > 0) {
            $geometryColumn = current($geometryColumns);
        } else {
            $geometryColumn = null;
        }

        // Count number of records
        $stmt = $connection->executeQuery(sprintf('SELECT COUNT(*) FROM %s', $table->getQuotedName($connection->getDatabasePlatform())));
        $count = $stmt->fetchOne();

        // List foreign keys from database structure or configuration
        $foreignKeys = [];
        foreach ($table->getForeignKeys() as $fk) {
            $foreignKeys[] = [
                'localColumn'   => $fk->getLocalColumns()[0],
                'foreignTable'  => $schema->listTableDetails($fk->getForeignTableName()),
                'foreignColumn' => $fk->getForeignColumns()[0],
            ];
        }
        foreach ($this->relations as $fk) {
            $foreignKeys[] = [
                'localColumn'   => $fk['localColumn'],
                'foreignTable'  => $schema->listTableDetails($fk['foreignTable']),
                'foreignColumn' => $fk['foreignColumn'],
            ];
        }

        $request = $request->withAttribute(self::TABLE_ATTRIBUTE, $table);
        $request = $request->withAttribute(self::COUNT_ATTRIBUTE, $count);
        $request = $request->withAttribute(self::FOREIGNKEYS_ATTRIBUTE, $foreignKeys);
        $request = $request->withAttribute(self::ISVIEW_ATTRIBUTE, $isView);
        $request = $request->withAttribute(self::PRIMARYKEY_ATTRIBUTE, $primaryKey);
        $request = $request->withAttribute(self::GEOMETRY_ATTRIBUTE, $geometryColumn);

        $request = $request->withAttribute(self::LIMIT_ATTRIBUTE, $this->limit);

        return $handler->handle($request);
    }
}
