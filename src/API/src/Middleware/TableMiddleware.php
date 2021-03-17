<?php

declare(strict_types=1);

namespace API\Middleware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\View;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TableMiddleware implements MiddlewareInterface
{
    public const TABLE_ATTRIBUTE = 'table';
    public const LIMIT_ATTRIBUTE = 'table.limit';
    public const COUNT_ATTRIBUTE = 'table.count';
    public const FOREIGNKEYS_ATTRIBUTE = 'table.fk';
    public const ISVIEW_ATTRIBUTE = 'table.isview';
    public const PRIMARYKEY_ATTRIBUTE = 'table.pk';

    private string $table;
    private ?string $primaryKeyColumn;
    private array $relations;
    private int $limit;

    public function __construct(string $table, ?string $primaryKeyColumn, array $relations, int $limit)
    {
        $this->table = $table;
        $this->limit = $limit;
        $this->relations = $relations;
        $this->primaryKeyColumn = $primaryKeyColumn;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Connection */ $connection = $request->getAttribute(DatabaseMiddleware::CONNECTION_ATTRIBUTE);

        $table = $connection->getSchemaManager()->listTableDetails($this->table);

        // Detect if the "table" is a VIEW
        $views = $connection->getSchemaManager()->listViews();
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
        $primaryKey = !is_null($table->getPrimaryKey()) ? $table->getPrimaryKey()->getName() : $table->getName().'_'.$this->primaryKeyColumn;
        if (is_null($primaryKey)) {
            throw new Exception('You need to configure the primary key.');
        }

        // Count number of records
        $stmt = $connection->executeQuery(sprintf('SELECT COUNT(*) FROM %s', $table->getQuotedName($connection->getDatabasePlatform())));
        $count = $stmt->fetchOne();

        // List foreign keys from database structure or configuration
        $foreignKeys = [];
        foreach ($table->getForeignKeys() as $fk) {
            $foreignKeys[] = [
                'localColumn'   => $fk->getLocalColumns()[0],
                'foreignTable'  => $connection->getSchemaManager()->listTableDetails($fk->getForeignTableName()),
                'foreignColumn' => $fk->getForeignColumns()[0],
            ];
        }
        foreach ($this->relations as $fk) {
            $foreignKeys[] = [
                'localColumn'   => $fk['localColumn'],
                'foreignTable'  => $connection->getSchemaManager()->listTableDetails($fk['foreignTable']),
                'foreignColumn' => $fk['foreignColumn'],
            ];
        }

        $request = $request->withAttribute(self::TABLE_ATTRIBUTE, $table);
        $request = $request->withAttribute(self::LIMIT_ATTRIBUTE, $this->limit);
        $request = $request->withAttribute(self::COUNT_ATTRIBUTE, $count);
        $request = $request->withAttribute(self::FOREIGNKEYS_ATTRIBUTE, $foreignKeys);
        $request = $request->withAttribute(self::ISVIEW_ATTRIBUTE, $isView);
        $request = $request->withAttribute(self::PRIMARYKEY_ATTRIBUTE, $primaryKey);

        return $handler->handle($request);
    }
}
