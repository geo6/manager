<?php

namespace App\Model\Ddl\Column;

use Zend\Db\Sql\Ddl\Column\Column;

class Geography extends Column
{
    /**
     * @var string
     */
    protected $type = 'GEOGRAPHY';
}
