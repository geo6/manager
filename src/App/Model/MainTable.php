<?php

declare(strict_types=1);

namespace App\Model;

use Zend\Db\Adapter\Adapter;

class MainTable extends Table
{
    private $config;

    public function __construct(Adapter $adapter, array $config)
    {
        parent::__construct($adapter);

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
}
