<?php

declare(strict_types=1);

namespace App\Model;

use ArrayObject;
use ErrorException;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Update;

class Record
{
    private $adapter;

    private $table;
    private $keys;
    private $geometryName;
    private $geometryType;

    public $id;
    public $properties;
    public $geometry;

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

    public function hydrate(ArrayObject $object): self
    {
        $table = $this->table->getName();
        $keyColumn = $this->table->getKeyColumn()->getName();

        if (is_null($this->id)) {
            $this->id = $object->{$table . Column::SEPARATOR . $keyColumn};
        }

        $this->properties = array_filter((array) $object, function ($key) {
            list($table, $column) = explode(Column::SEPARATOR, $key);

            return substr($column, 0, 1) !== '_' && $column !== $this->table->getGeometryColumn()->getName();
        }, ARRAY_FILTER_USE_KEY);

        $this->geometry = json_decode($object->{$table . Column::SEPARATOR . '_geojson'});

        return $this;
    }

    public function select(bool $execute = false)
    {
        $keyColumn = $this->table->getKeyColumn()->getName();
        $columns = $this->table->getSelectColumns();

        $select = new Select($this->table->getIdentifier());
        $select = $select->columns($columns, true);

        if (!is_null($this->id)) {
            $key = sprintf('%s.%s', $this->table->getName(), $keyColumn);
            $select = $select->where([$key => $this->id]);
        }

        return $execute ? $this->execute($select) : $select;
    }

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

            $this->id = $this->adapter->getDriver()->getLastGeneratedValue('eve_point_id_seq');
            $this->refresh();

            return $result;
        }

        return $insert;
    }

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

    public function delete(bool $execute)
    {
        $keyColumn = $this->table->getKeyColumn()->getName();

        $delete = new Delete($this->table->getIdentifier());
        $delete = $delete->where([$keyColumn => $this->id]);

        return $execute ? $this->execute($delete) : $delete;
    }

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

    public function toGeoJSON(): array
    {
        $properties = array_map(function ($key, $value) {
            list($table, $column) = explode(Column::SEPARATOR, $key);

            return [
                $table === $this->table->getName() ? $column : $key,
                $value,
            ];
        }, array_keys($this->properties), $this->properties);

        $properties = array_column($properties, 1, 0);

        return [
            'type'       => 'Feature',
            'id'         => $this->id,
            'properties' => $properties,
            'geometry'   => $this->geometry,
        ];
    }
}
