<?php

declare (strict_types = 1);

namespace App\Model;

use ArrayObject;
use ErrorException;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Insert;

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
        if (is_null($this->id)) {
            $keyColumn = $this->table->getKeyColumn()->getName();
            $this->id = $object->{$keyColumn};
        }

        $this->properties = array_filter((array)$object, function ($key) {
            return substr($key, 0, 1) !== '_' && $key !== $this->table->getGeometryColumn()->getName();
        }, ARRAY_FILTER_USE_KEY);

        $this->geometry = json_decode($object->_geojson);

        return $this;
    }

    public function select(bool $execute = false)
    {
        $keyColumn = $this->table->getKeyColumn()->getName();
        $geometryColumn = $this->table->getGeometryColumn()->getName();

        $select = new Select($this->table->getIdentifier());
        $select = $select->columns([
            '*',
            '_geojson' => new Expression('ST_AsGeoJSON(' . $geometryColumn . '::geometry) '),
            '_length'  => new Expression('ST_Length(' . $geometryColumn . '::geometry::geography) '),
            '_area'    => new Expression('ST_Area(' . $geometryColumn . '::geometry::geography) '),
        ]);

        if (!is_null($this->id)) {
            $select = $select->where([$keyColumn => $this->id]);
        }

        return $execute ? $this->execute($select) : $select;
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
            $geometryColumn = $this->table->getGeometryColumn()->getName();

            $set = array_merge($set, [
                $geometryColumn => new Expression('ST_GeomFromGeoJSON(\'' . json_encode($data['geometry']) . '\')'),
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
        return [
            'type'       => 'Feature',
            'id'         => $this->id,
            'properties' => $this->properties,
            'geometry'   => $this->geometry,
        ];
    }
}
