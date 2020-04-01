<?php

declare(strict_types=1);

namespace App\Model\Column;

use Laminas\Db\Sql\Sql;

class ReferenceColumn extends Column
{
    public function getValues(string $column): array
    {
        $sql = new Sql($this->adapter);

        $select = $sql->select()->from($this->table->getIdentifier())
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
