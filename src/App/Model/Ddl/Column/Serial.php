<?php

namespace App\Model\Ddl\Column;

use Zend\Db\Sql\Ddl\Column\Column;

class Serial extends Column
{
    /**
     * @var string
     */
    protected $type = 'SERIAL';
}
