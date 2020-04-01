<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use App\Model\Ddl\Column\Geography;
use App\Model\Ddl\Column\Serial;
use Blast\BaseUrl\BaseUrlMiddleware;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\Metadata;
use Laminas\Db\Sql\Ddl;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouterInterface;
use Mezzio\Session\SessionMiddleware;

class NewHandler implements RequestHandlerInterface
{
    /** @var RouterInterface */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $basePath = $request->getAttribute(BaseUrlMiddleware::BASE_PATH);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if (!isset($config['global']['new'])) {
            throw new Exception('Unable to find configuration for new table.');
        }

        if ($session->has('schema') || $session->has('table')) {
            self::dropTable(
                $adapter,
                $session->get('schema'),
                $session->get('table')
            );

            $session->unset('schema');
            $session->unset('table');
        }

        $table = self::createTable(
            $adapter,
            $config['global']['new']['schema'] ?? 'temp',
            $config['global']['new']['columns'] ?? []
        );

        $session->set('schema', $table->getSchema());
        $session->set('table', $table->getTable());

        $redirect = ($basePath !== '/' ? $basePath : '');
        $redirect .= $this->router->generateUri('table', ['config' => 'new']);

        return new RedirectResponse($redirect);
    }

    /**
     * Generate new (temporary) table name.
     *
     * @return string New (temporary) table name.
     */
    private static function tableName(): string
    {
        return date('YmdHis');
    }

    /**
     * Create a new (temporary) table (and schema if necessary).
     * Clean outdated tables.
     *
     * @param Adapter  $adapter
     * @param string   $schema
     * @param string[] $columns
     *
     * @return TableIdentifier TableIdentifier with the new table table and schema.
     */
    private static function createTable(Adapter $adapter, string $schema, array $columns = []): TableIdentifier
    {
        // Create schema (if not exists)
        $adapter->query(
            sprintf(
                'CREATE SCHEMA IF NOT EXISTS "%s";',
                $schema
            ),
            $adapter::QUERY_MODE_EXECUTE
        );

        // Clean outdated tables
        self::cleanOldTables($adapter, $schema);

        // Create table
        $name = self::tableName();
        $table = new TableIdentifier($name, $schema);

        $ddl = new Ddl\CreateTable($table);

        $ddl->addColumn(new Serial('id'));
        foreach ($columns as $name => $column) {
            $ddl->addColumn(
                new Ddl\Column\Varchar(
                    $name,
                    null,
                    (isset($column['notnull']) && $column['notnull'] === true ? false : true)
                )
            );
        }
        $ddl->addColumn(new Geography('the_geog'));

        $ddl->addConstraint(new Ddl\Constraint\PrimaryKey('id'));

        $sql = new Sql($adapter);
        $qsz = $sql->getSqlStringForSqlObject($ddl);

        $adapter->query(
            $qsz,
            $adapter::QUERY_MODE_EXECUTE
        );

        return $table;
    }

    /**
     * Drop a table.
     *
     * @param Adapter $adapter
     * @param string  $schema
     * @param string  $table
     */
    private static function dropTable(Adapter $adapter, string $schema, string $table): void
    {
        $adapter->query(
            sprintf(
                'DROP TABLE IF EXISTS "%s"."%s";',
                $schema,
                $table
            ),
            $adapter::QUERY_MODE_EXECUTE
        );
    }

    /**
     * Drop outdated tables (in specified schema).
     *
     * @param Adapter $adapter
     * @param string  $schema
     */
    private static function cleanOldTables(Adapter $adapter, string $schema): void
    {
        $metadata = new Metadata($adapter);
        $tables = $metadata->getTables($schema);

        foreach ($tables as $table) {
            $name = (string) $table->getName();
            if (preg_match('/([0-9]{14})(?:-([a-z]+))?/', $name, $matches) === 1) {
                $datetime = DateTime::createFromFormat('YmdHis', $matches[1]);
                $now = new DateTime();

                $diff = $now->getTimestamp() - $datetime->getTimestamp();

                if ($diff > 24 * 60 * 60) {
                    self::dropTable($adapter, $schema, $name);
                }
            }
        }
    }
}
