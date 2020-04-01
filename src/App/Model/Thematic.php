<?php

declare(strict_types=1);

namespace App\Model;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;

class Thematic
{
    /** @var Adapter */
    private $adapter;
    /** @var array|null */
    private $config;

    /** @var string|null */
    public $column;
    /** @var string */
    public $default = '#3399cc';
    /** @var string[] */
    public $values = [];

    /**
     * @param Adapter    $adapter
     * @param array|null $config
     */
    public function __construct(Adapter $adapter, ?array $config = null)
    {
        $this->adapter = $adapter;
        $this->config = $config;

        if (!is_null($config) && isset($config['thematic'])) {
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
