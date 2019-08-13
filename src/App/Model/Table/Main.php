<?php

declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Column;
use App\Model\Table;
use ArrayObject;
use Zend\Db\Adapter\Adapter;

class Main extends Table
{
    /** @var array */
    private $configColumns;

    /**
     * @param Adapter $adapter
     * @param string  $schema
     * @param string  $table
     * @param array   $config
     */
    public function __construct(Adapter $adapter, string $schema, string $table, ?array $config = null)
    {
        parent::__construct($adapter, $schema, $table);

        $this->configColumns = $config['columns'] ?? null;

        foreach ($this->columns as &$column) {
            $column->readonly = $this->isColumnReadonly($column);
            $column->reference = $this->getColumnReference($column);
        }
    }

    /**
     * @param Column $column
     *
     * @return bool Is the column readonly ?
     */
    private function isColumnReadonly(Column $column): bool
    {
        $name = $column->getName();

        $keyColumn = $this->getKeyColumn()->getName();

        if (in_array($name, [$keyColumn, 'updatetime', 'updateuser'])) {
            return true;
        }

        if (isset($this->configColumns[$name], $this->configColumns[$name]['readonly'])) {
            return (bool) $this->configColumns[$name]['readonly'];
        }

        return false;
    }

    /**
     * @param Column $column
     *
     * @return ArrayObject|null Information (name, mode, values) about reference.
     */
    private function getColumnReference(Column $column): ?ArrayObject
    {
        $name = $column->getName();
        $reference = $column->getForeignColumn();

        if (is_null($reference)) {
            return null;
        }

        $mode = 'default';
        $display = $reference->getName();

        if (isset($this->configColumns[$name], $this->configColumns[$name]['reference'])) {
            $configReference = $this->configColumns[$name]['reference'];

            if (isset($configReference['mode']) && in_array($configReference['mode'], ['listbox', 'datalist'])) {
                $mode = $configReference['mode'];
            }

            if (isset($configReference['display']) && strlen($configReference['display']) > 0) {
                $display = $configReference['display'];
            }
        }

        $values = $reference->getValues($display);

        return new ArrayObject([
            'mode'   => $mode,
            'values' => $values,
            'schema' => $reference->getSchemaName(),
            'table'  => $reference->getTableName(),
            'column' => $reference->getName(),
        ], ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * @return array
     */
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
