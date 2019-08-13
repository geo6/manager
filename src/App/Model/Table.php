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
    /** @var Adapter */
    protected $adapter;
    /** @var \App\Model\Column[] */
    protected $columns = [];
    /** @var \Zend\Db\Metadata\Object\ConstraintObject[] */
    protected $constraints;
    /** @var int */
    protected $count;
    /** @var \Zend\Db\Sql\TableIdentifier */
    protected $identifier;
    /** @var string */
    protected $name;
    /** @var string */
    protected $schema;

    /**
     * @param Adapter $adapter
     * @param string $schema
     * @param string $table
     */
    public function __construct(Adapter $adapter, string $schema, string $table)
    {
        $this->adapter = $adapter;

        $this->name = $table;
        $this->schema = $schema;

        $this->identifier = new TableIdentifier($this->name, $this->schema);

        $this->count = $this->getCount();

        $metadata = new Metadata($this->adapter);

        $this->constraints = $metadata->getConstraints($this->name, $this->schema);

        $columns = $metadata->getColumns($this->name, $this->schema);
        foreach ($columns as $column) {
            $col = Column::fromColumnObject($adapter, $column);

            $this->columns[] = $col;
        }
    }

    /**
     * @param string|null $filter
     *
     * @return int
     */
    public function getCount(?string $filter = null): int
    {
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

    /**
     * @return ColumnObject
     */
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

    /**
     * @return string
     */
    public function getKeySequence(): string
    {
        $default = $this->getKeyColumn()->getColumnDefault();

        if (is_null($default)) {
            throw new Exception(
                sprintf('The PRIMARY KEY for table "%s" is currently not auto-incremented.', $this->name)
            );
        }

        if (preg_match('/^nextval\(\'((?:\w+)|(?:\w+\."\w+"))\'(?:\:\:regclass)?\)$/', $default, $matches) === 0) {
            throw new Exception(
                sprintf('The PRIMARY KEY for table "%s" is currently not auto-incremented.', $this->name)
            );
        }

        return $matches[1];
    }

    /**
     * @return ColumnObject|null
     */
    public function getGeometryColumn(): ?ColumnObject
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

        return count($columns) > 0 ? current($columns) : null;
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return TableIdentifier
     */
    public function getIdentifier(): TableIdentifier
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    /**
     * @return array
     */
    public function getSelectColumns(): array
    {
        $geometryColumn = $this->getGeometryColumn();

        if (!is_null($geometryColumn)) {
            $columns = array_filter($this->getColumns(), function ($column) use ($geometryColumn) {
                return $column->getName() !== $geometryColumn->getName();
            });
        } else {
            $columns = $this->getColumns();
        }

        $columnsName = [];
        foreach ($columns as $column) {
            $alias = $this->name . Column::SEPARATOR . $column->getName();
            $columnsName[$alias] = $column->getName();
        }

        if (!is_null($geometryColumn)) {
            $columnsName = array_merge(
                $columnsName,
                [
                    $this->name . Column::SEPARATOR . '_geojson' => new Expression(sprintf('ST_AsGeoJSON("%s"."%s"::geometry)', $this->name, $geometryColumn->getName())),
                    $this->name . Column::SEPARATOR . '_length'  => new Expression(sprintf('ST_Length("%s"."%s"::geometry::geography)', $this->name, $geometryColumn->getName())),
                    $this->name . Column::SEPARATOR . '_area'    => new Expression(sprintf('ST_Area("%s"."%s"::geometry::geography)', $this->name, $geometryColumn->getName())),
                ]
            );
        }

        return $columnsName;
    }

    /**
     * @param string|null $filter
     * @param string|null $order
     * @param int|null $limit
     * @param int|null $start
     * @param bool $geojson
     *
     * @return array
     */
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
            $select = $select->order($this->name . '.' . $order);
        } else {
            $select = $select->order($this->name . '.' . $this->getKeyColumn()->getName());
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
