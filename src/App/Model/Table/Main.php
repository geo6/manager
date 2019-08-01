<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Table;
use Zend\Db\Adapter\Adapter;

class Main extends Table
{
    private $config;

    public function __construct(Adapter $adapter, array $config)
    {
        $connection = $adapter->getDriver()->getConnection()->getConnectionParameters();

        parent::__construct($adapter, $connection['schema'], $connection['table']);

        $this->config = $config;

        foreach ($this->columns as &$column) {
            $name = $column->getName();

            $column->readonly = $this->isColumnReadonly($name);
        }
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
