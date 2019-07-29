<?php

declare(strict_types=1);

namespace App\Model;

use Exception;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Metadata\Object\ColumnObject;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\TableIdentifier;

class Table
{
    private $adapter;
    private $config;
    private $columns;
    private $constraints;
    private $identifier;
    private $name;
    private $schema;

    public function __construct(Adapter $adapter, array $config)
    {
        $this->adapter = $adapter;
        $this->config = $config;

        $connection = $this->adapter->getDriver()->getConnection()->getConnectionParameters();

        $this->name = $connection['table'];
        $this->schema = $connection['schema'];

        $this->identifier = new TableIdentifier($this->name, $this->schema);

        $this->count = $this->getCount();

        $metadata = new Metadata($this->adapter);

        $this->constraints = $metadata->getConstraints($this->name, $this->schema);
        $this->columns = $metadata->getColumns($this->name, $this->schema);

        foreach ($this->columns as &$column) {
            $name = $column->getName();

            $column->readonly = $this->isColumnReadonly($name);
            $column->notnull = $this->isColumnNotNull($name);

            $datatype = $column->getDataType();

            if ($datatype === 'USER-DEFINED') {
                $datatype = $this->getColumnDataType($name);
                $column->setDataType($datatype);
            }
        }
    }

    public function getCount(
        ?string $filter = null
    ): int {
        $sql = new Sql($this->adapter);

        $select = $sql->select()->from($this->identifier)->columns(['count' => new Expression('COUNT(*)')]);

        if (!is_null($filter)) {
            $select = $select->where((new Filter($filter))->getPredicate());
        }

        $qsz = $sql->buildSqlString($select);
        $query = $this->adapter->query($qsz, $this->adapter::QUERY_MODE_EXECUTE);

        $count = (array_column($query->toArray(), 'count'))[0];

        return $count;
    }

    public function getKeyColumn(): ColumnObject
    {
        $primaryKey = current(array_filter($this->constraints, function ($constraint) {
            return $constraint->isPrimaryKey();
        }));

        $keys = $primaryKey->getColumns();

        if (count($keys) === 0) {
            throw new Exception(sprintf('Table "%s" is missing a PRIMARY KEY.', $this->name));
        }

        $column = current(array_filter($this->columns, function ($column) use ($keys) {
            return $column->getName() === $keys[0];
        }));

        if (count($keys) > 1 || $column->getDataType() !== 'integer') {
            throw new Exception(
                sprintf(
                    'The PRIMARY KEY for table "%s" should be only one single auto-incremented INTEGER column.',
                    $this->name
                )
            );
        }

        return $column;
    }

    public function getKeySequence(): string
    {
        $default = $this->getKeyColumn()->getColumnDefault();

        if (is_null($default)) {
            throw new Exception(
                sprintf('The PRIMARY KEY for table "%s" is currently not auto-incremented.', $this->name)
            );
        }

        if (preg_match('/^nextval\(\'(\w +) \'(?:\:\:regclass)?\)$/', $default, $matches) === 0) {
            throw new Exception(
                sprintf('The PRIMARY KEY for table "%s" is currently not auto-incremented.', $this->name)
            );
        }

        return $matches[1];
    }

    public function getGeometryColumn(): ColumnObject
    {
        $columns = array_filter($this->columns, function ($column) {
            return in_array(
                $column->getDataType(),
                [
                    'geography',
                    'geometry',
                    'point',
                    'line',
                    'polygon',
                ]
            );
        });

        return current($columns);
    }

    private function getColumnDataType(string $column): string
    {
        $connection = $this->adapter->getDriver()->getConnection()->getConnectionParameters();

        $db = pg_connect(
            sprintf(
                'host=%s port=%d dbname=%s user=%s password=%s',
                $connection['host'],
                $connection['port'],
                $connection['dbname'],
                $connection['user'],
                $connection['password']
            )
        );

        $metadata = pg_meta_data($db, $this->schema . '.' . $this->name);

        pg_close($db);

        return $metadata[$column]['type'];
    }

    private function isColumnReadonly(string $column): bool
    {
        $keyColumn = $this->getKeyColumn()->getName();

        if (in_array($column, [$keyColumn, 'updatetime', 'updateuser'])) {
            return true;
        }

        if (isset($this->config['columns'], $this->config['columns'][$column], $this->config['columns'][$column]['readonly'])) {
            return (bool) $this->config['columns'][$column]['readonly'];
        }

        return false;
    }

    private function isColumnNotNull(string $column): bool
    {
        $notnull = array_filter($this->constraints, function ($constraint) use ($column) {
            return $constraint->isCheck() && $constraint->getCheckClause() === sprintf('%s IS NOT NULL', $column);
        });

        return count($notnull) > 0;
    }

    // private function isColumnUnique(string $column): bool
    // {
    //     $unique = array_filter($this->constraints, function ($constraint) use ($column) {
    //         return $constraint->isUnique() && $constraint->hasColumns() && in_array($column, $constraint->getColumns()) && count($constraint->getColumns()) === 1;
    //     });

    //     return count($unique) > 0;
    // }

    public function toArray(): array
    {
        $columns = [];
        foreach ($this->columns as $column) {
            $columns[] = [
                'name'      => $column->getName(),
                'type'      => $column->getDataType(),
                'default'   => $column->getColumnDefault(),
                'maxlength' => $column->getCharacterMaximumLength(),
                'readonly'  => $column->readonly,
                'notnull'   => $column->notnull,
            ];
        }

        $constraints = [];
        foreach ($this->constraints as $constraint) {
            $constraints[] = [
                'name'      => $constraint->getName(),
                'type'      => $constraint->getType(),
                'columns'   => $constraint->hasColumns() ? $constraint->getColumns() : null,
                'check'     => $constraint->isCheck() ? $constraint->getCheckClause() : null,
                'reference' => $constraint->isForeignKey() ? [
                    'schema'  => $constraint->getReferencedTableSchema(),
                    'table'   => $constraint->getReferencedTableName(),
                    'columns' => $constraint->getReferencedColumns(),
                ] : null,
            ];
        }

        return [
            'schema' => $this->schema,
            'table'  => $this->name,
            // 'count' => $count,
            'key'         => $this->getKeyColumn()->getName(),
            'geometry'    => $this->getGeometryColumn()->getName(),
            'columns'     => $columns,
            'constraints' => $constraints,
        ];
    }

    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getIdentifier(): TableIdentifier
    {
        return $this->identifier;
    }

    public function getRecords(
        ?string $filter = null,
        ?string $order = null,
        ?int $limit = null,
        ?int $start = null,
        bool $geojson = false
    ): array {
        $sql = new Sql($this->adapter);

        $select = (new Record($this->adapter, $this))->select();

        if (!is_null($filter)) {
            $select = $select->where((new Filter($filter))->getPredicate());
        }
        if (!is_null($order)) {
            $select = $select->order($order);
        } else {
            $select = $select->order($this->getKeyColumn()->getName());
        }
        if (!is_null($limit)) {
            $select = $select->limit($limit);
        }
        if (!is_null($start)) {
            $select = $select->offset($start);
        }

        $qsz = $sql->buildSqlString($select);
        $query = $this->adapter->query($qsz, $this->adapter::QUERY_MODE_EXECUTE);

        $records = [];

        foreach ($query as $result) {
            $record = (new Record($this->adapter, $this))->hydrate($result);
            $records[] = $geojson ? $record->toGeoJSON() : $record;
        }

        return $records;
    }
}
