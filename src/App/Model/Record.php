<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Column\Column;
use ArrayObject;
use ErrorException;
use geoPHP;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;

class Record
{
    /** @var Adapter */
    private $adapter;

    /** @var Table */
    private $table;
    /** @var string[] */
    private $keys;

    /** @var int|null */
    public $id;
    /** @var array|null */
    public $properties;
    /** @var array|null */
    public $geometry;

    /**
     * @param Adapter  $adapter
     * @param Table    $table
     * @param int|null $id
     */
    public function __construct(Adapter $adapter, Table $table, ?int $id = null)
    {
        $this->adapter = $adapter;
        $this->table = $table;
        $this->id = $id;

        $columns = $this->table->getColumns();
        $propertiesColumns = array_filter($columns, function ($column) {
            return $column !== $this->table->getGeometryColumn();
        });
        $propertiesNames = array_map(function ($column) {
            return $column->getName();
        }, $propertiesColumns);

        $this->keys = array_values($propertiesNames);

        if (!is_null($this->id)) {
            $this->refresh();
        }
    }

    /**
     * @param ArrayObject $object
     *
     * @return self
     */
    public function hydrate(ArrayObject $object): self
    {
        $keyColumn = $this->table->getKeyColumn()->getName();

        if (is_null($this->id)) {
            $this->id = $object->{$keyColumn};
        }

        $pattern = sprintf('/^(\w+)%s(\w+)$/', preg_quote(Column::SEPARATOR));

        $this->properties = array_filter((array) $object, function ($key) use ($pattern) {
            if (preg_match($pattern, $key, $matches) === 1) {
                $column = $matches[2];
            } else {
                $column = $key;
            }

            return substr($column, 0, 1) !== '_';
        }, ARRAY_FILTER_USE_KEY);

        $this->geometry = geoPHP::load($object->_ewkt, 'ewkt');

        return $this;
    }

    /**
     * @param bool $execute
     */
    public function select(bool $execute = false)
    {
        $keyColumn = $this->table->getKeyColumn()->getName();
        $columns = $this->table->getSelectColumns(true, false);

        $select = new Select($this->table->getIdentifier());
        $select = $select->columns($columns, true);

        $foreignColumns = array_filter($this->table->getColumns(), function (Column $column) {
            return $column->isForeignKey();
        });
        foreach ($foreignColumns as $column) {
            $reference = $column->getReferenceColumn();

            $foreignTable = new Table($this->adapter, $reference->getSchemaName(), $reference->getTableName());

            $foreignColumns = $foreignTable->getSelectColumns(false, true);

            $on = sprintf(
                '%s.%s = %s.%s',
                $reference->getTableName(),
                $reference->getName(),
                $this->table->getName(),
                $column->getName()
            );

            $select->join($foreignTable->getIdentifier(), $on, $foreignColumns, Select::JOIN_LEFT);
        }

        if (!is_null($this->id)) {
            $key = sprintf('%s.%s', $this->table->getName(), $keyColumn);
            $select = $select->where([$key => $this->id]);
        }

        return $execute ? $this->execute($select) : $select;
    }

    /**
     * @param array       $data
     * @param bool        $execute
     * @param string|null $user
     */
    public function insert(array $data, bool $execute, ?string $user = null)
    {
        if (!isset($data['properties'], $data['geometry'])) {
            throw new ErrorException('Missing "properties" or "geometry" parameters.');
        }

        $insert = new Insert($this->table->getIdentifier());

        $properties = array_map(function ($value) {
            return strlen($value) === 0 ? null : $value;
        }, $data['properties']);

        $insert = $insert->values($properties);

        $geometryColumn = $this->table->getGeometryColumn();

        $geometry = [
            $geometryColumn->getName() => new Expression(
                sprintf(
                    'ST_GeomFromGeoJSON(\'%s\')::%s',
                    json_encode($data['geometry']),
                    $geometryColumn->getDataType()
                )
            ),
        ];

        $insert = $insert->values($geometry, Insert::VALUES_MERGE);

        if (in_array('updatetime', $this->keys)) {
            $insert = $insert->values(['updatetime' => date('Y-m-d H:i:s')], Insert::VALUES_MERGE);
        }
        if (in_array('updateuser', $this->keys)) {
            $insert = $insert->values(['updateuser' => $user], Insert::VALUES_MERGE);
        }

        if ($execute === true) {
            $result = $this->execute($insert);

            $sequence = $this->table->getKeySequence();

            $this->id = $this->adapter->getDriver()->getLastGeneratedValue($sequence);
            $this->refresh();

            return $result;
        }

        return $insert;
    }

    /**
     * @param array       $data
     * @param bool        $execute
     * @param string|null $user
     */
    public function update(array $data, bool $execute, ?string $user = null)
    {
        if (!isset($data['properties']) && !isset($data['geometry'])) {
            throw new ErrorException('Missing "properties" and "geometry" parameters.');
        }

        $columns = $this->table->getColumns();
        $columnsName = [];
        foreach ($columns as $column) {
            $columnsName[] = $column->getName();
        }

        $set = [];

        if (isset($data['properties'])) {
            $properties = array_map(function ($value) {
                return strlen($value) === 0 ? null : $value;
            }, $data['properties']);

            $set = array_merge($set, $properties);
        }

        if (isset($data['geometry'])) {
            $geometryColumn = $this->table->getGeometryColumn();

            $set = array_merge($set, [
                $geometryColumn->getName() => new Expression(
                    sprintf(
                        'ST_GeomFromGeoJSON(\'%s\')::%s',
                        json_encode($data['geometry']),
                        $geometryColumn->getDataType()
                    )
                ),
            ]);
        }

        if (in_array('updatetime', $columnsName)) {
            $datetime = date('Y-m-d H:i:s');

            $set = array_merge($set, ['updatetime' => $datetime]);
        }
        if (in_array('updateuser', $columnsName)) {
            $set = array_merge($set, ['updateuser' => $user]);
        }

        $keyColumn = $this->table->getKeyColumn()->getName();

        $update = new Update($this->table->getIdentifier());
        $update = $update->set($set);
        $update = $update->where([$keyColumn => $this->id]);

        if ($execute === true) {
            $result = $this->execute($update);

            $this->refresh();

            return $result;
        }

        return $update;
    }

    /**
     * @param bool $execute
     */
    public function delete(bool $execute)
    {
        $keyColumn = $this->table->getKeyColumn()->getName();

        $delete = new Delete($this->table->getIdentifier());
        $delete = $delete->where([$keyColumn => $this->id]);

        return $execute ? $this->execute($delete) : $delete;
    }

    /**
     * @param mixed $query
     */
    private function execute($query)
    {
        $sql = new Sql($this->adapter);

        $qsz = $sql->buildSqlString($query);

        return $this->adapter->query($qsz, $this->adapter::QUERY_MODE_EXECUTE);
    }

    private function refresh()
    {
        if (!is_null($this->id)) {
            $query = $this->select(true);

            $result = $query->current();

            if (is_null($result)) {
                throw new Exception(sprintf('Record #%d does not exist.', $this->id));
            }

            $this->hydrate($result);
        }
    }
}
