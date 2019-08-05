<?php

declare(strict_types=1);

namespace App\Model\Column;

use App\Model\Column;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\TableIdentifier;

class Foreign extends Column
{
    public function getValues(string $column): array
    {
        $table = new TableIdentifier($this->tableName, $this->schemaName);

        $sql = new Sql($this->adapter);

        $select = $sql->select()->from($table)
            ->columns([$this->name, $column])
            ->group([$this->name, $column])
            ->order($column);

        $qsz = $sql->buildSqlString($select);
        $query = $this->adapter->query($qsz, $this->adapter::QUERY_MODE_EXECUTE);

        $values = [];
        foreach ($query as $r) {
            $key = $r[$this->name];

            $values[$key] = $r[$column];
        }

        return $values;
    }
}
