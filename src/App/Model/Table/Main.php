<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table;
use ArrayObject;
use Zend\Db\Adapter\Adapter;
use App\Model\Column;

class Main extends Table
{
    private $config;

    public function __construct(Adapter $adapter, array $config)
    {
        $connection = $adapter->getDriver()->getConnection()->getConnectionParameters();

        parent::__construct($adapter, $connection['schema'], $connection['table']);

        $this->config = $config;

        foreach ($this->columns as &$column) {
            $column->readonly = $this->isColumnReadonly($column);
            $column->reference = $this->getColumnReference($column);
        }
    }

    private function isColumnReadonly(Column $column): bool
    {
        $name = $column->getName();

        $keyColumn = $this->getKeyColumn()->getName();

        if (in_array($name, [$keyColumn, 'updatetime', 'updateuser'])) {
            return true;
        }

        if (isset($this->config['columns'], $this->config['columns'][$name], $this->config['columns'][$name]['readonly'])) {
            return (bool) $this->config['columns'][$name]['readonly'];
        }

        return false;
    }

    private function getColumnReference(Column $column): ?ArrayObject
    {
        $name = $column->getName();
        $reference = $column->getForeignColumn();

        if (is_null($reference)) {
            return null;
        }

        $mode = 'default';
        $values = [];

        if (isset($this->config['columns'], $this->config['columns'][$name], $this->config['columns'][$name]['reference'])) {
            $config = $this->config['columns'][$name]['reference'];

            $mode = isset($config['mode']) && in_array($config['mode'], ['listbox', 'datalist']) ? $config['mode'] : $mode;

            if (isset($config['display'])) {
                $values = $reference->getValues($config['display']);
            }
        }

        return new ArrayObject([
            'mode'   => $mode,
            'values' => $values,
            'schema' => $reference->getSchemaName(),
            'table'  => $reference->getTableName(),
            'column' => $reference->getName(),
        ], ArrayObject::ARRAY_AS_PROPS);
    }

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
                'notnull'   => !$column->isNullable(),
                'reference' => $column->reference,
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
}
