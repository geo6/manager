<?php

declare (strict_types = 1);

namespace App\Model;

use ArrayObject;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Update;

class Record
{
    private $adapter;

    private $table;

    public $id;
    public $properties;
    public $geometry;

    public function __construct(Adapter $adapter, Table $table, ?int $id = null)
    {
        $this->adapter = $adapter;
        $this->table = $table;
        $this->id = $id;

        if (!is_null($this->id)) {
            $query = $this->select(true);

            $result = $query->current();

            if (is_null($result)) {
                throw new Exception(sprintf('Record #%d does not exist.', $this->id));
            }

            $this->hydrate($result);
        }
    }

    public function hydrate(ArrayObject $object): self
    {
        $table = $this->table;

        if (is_null($this->id)) {
            $keyColumn = $this->table->getKeyColumn();
            $this->id = $object->{$keyColumn};
        }

        $this->properties = array_filter((array)$object, function ($key) use ($table) {
            return substr($key, 0, 1) !== '_' && $key !== $table->getGeometryColumn();
        }, ARRAY_FILTER_USE_KEY);

        $this->geometry = json_decode($object->_geojson);

        return $this;
    }

    public function select(bool $execute = false)
    {
        $keyColumn = $this->table->getKeyColumn();
        $geometryColumn = $this->table->getGeometryColumn();

        $select = new Select($this->table->getIdentifier());
        $select = $select->columns([
            '*',
            '_geojson' => new Expression('ST_AsGeoJSON(' . $geometryColumn . ') '),
            '_length'  => new Expression('ST_Length(' . $geometryColumn . ') '),
            '_area'    => new Expression('ST_Area(' . $geometryColumn . ') '),
        ]);

        if (!is_null($this->id)) {
            $select = $select->where([$keyColumn => $this->id]);
        }

        return $execute ? $this->execute($select) : $select;
    }

    public function update(array $data, bool $execute)
    {
        $keyColumn = $this->table->getKeyColumn();

        $update = new Update($this->table->getIdentifier());

        if (isset($data['properties'])) {
            $properties = array_map(function ($value) {
                return strlen($value) === 0 ? null : $value;
            }, $data['properties']);

            $update = $update->set($properties);

            $this->properties = array_merge($this->properties, $properties);
        }
        if (isset($data['geometry'])) {
            $geometryColumn = $this->table->getGeometryColumn();

            $update = $update->set([
                $geometryColumn => new Expression('ST_GeomFromGeoJSON(\'' . json_encode($data['geometry']) . '\')')
            ]);

            $this->geometry = $data['geometry'];
        }

        $update = $update->where([$keyColumn => $this->id]);

        return $execute ? $this->execute($update) : $update;
    }

    public function delete(bool $execute)
    {
        $keyColumn = $this->table->getKeyColumn();

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
