<?php

declare (strict_types = 1);

namespace App\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\TableIdentifier;
use Zend\Db\Sql\Select;

class Thematic
{
    private $adapter;
    private $config;

    public $column;
    public $default = '#3399cc';
    public $values = [];

    public function __construct(Adapter $adapter, array $config)
    {
        $this->adapter = $adapter;
        $this->config = $config;

        if (isset($config['thematic'])) {
            if (isset($config['thematic']['default'])) {
                $this->default = $config['thematic']['default'];
            }

            if (isset($config['thematic']['column'])) {
                $connection = $this->adapter->getDriver()->getConnection()->getConnectionParameters();
                $identifier = new TableIdentifier($connection['table'], $connection['schema']);

                $this->column = $config['thematic']['column'];

                $select = (new Select($identifier))
                    ->columns([$this->column])
                    ->group([$this->column])
                    ->order([$this->column]);

                $sql = new Sql($this->adapter);

                $qsz = $sql->buildSqlString($select);
                $query = $this->adapter->query($qsz, $this->adapter::QUERY_MODE_EXECUTE);

                $values = array_column($query->toArray(), $this->column);

                foreach ($values as $value) {
                    if (isset($config['thematic']['values'], $config['thematic']['values'][$value])) {
                        $this->values[$value] = [
                            'color' => $config['thematic']['values'][$value],
                        ];
                    } else {
                        $this->values[$value] = [
                            'color' => $this->default,
                        ];
                    }
                }

                ksort($this->values);
            }
        }
    }
}
