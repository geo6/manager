<?php

declare(strict_types=1);

namespace App\Model\Column;

use App\Model\Table;
use Exception;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Object\ColumnObject;
use Zend\Db\Metadata\Object\ConstraintObject;

class Column extends ColumnObject
{
    const SEPARATOR = '.';

    /** @var Adapter */
    protected $adapter;

    /** @var ConstraintObject[] */
    protected $constraints = [];

    /** @var Table */
    protected $table;

    /** @var bool */
    public $readonly = false;

    /** @var bool */
    public $preview = true;

    /** @var bool */
    public $download = true;

    /** @var array */
    private $reference;

    /** @var array */
    private $upload;

    /**
     * @param Table  $table
     * @param string $name
     */
    public function __construct(Table $table, string $name)
    {
        parent::__construct($name, $table->getName(), $table->getSchema());

        $this->table = $table;
        $this->adapter = $table->getAdapter();

        $constraints = $table->getConstraints();
        $this->constraints = array_filter($constraints, function (ConstraintObject $constraint) {
            return in_array($this->name, $constraint->getColumns());
        });

        $key = $table->getKeyColumn()->getName();
        if (in_array($this->name, [$key, 'updateuser', 'updatetime'])) {
            $this->readonly = true;
        }
    }

    /**
     * @param Table        $table
     * @param ColumnObject $columObject
     *
     * @return self
     */
    public static function fromColumnObject(Table $table, ColumnObject $columnObject): self
    {
        $column = new self(
            $table,
            $columnObject->getName(),
        );
        $column->setOrdinalPosition($columnObject->getOrdinalPosition());
        $column->setColumnDefault($columnObject->getColumnDefault());
        $column->setIsNullable($columnObject->getIsNullable());
        $column->setDataType($columnObject->getDataType());
        $column->setCharacterMaximumLength($columnObject->getCharacterMaximumLength());
        $column->setCharacterOctetLength($columnObject->getCharacterOctetLength());
        $column->setNumericPrecision($columnObject->getNumericPrecision());
        $column->setNumericScale($columnObject->getNumericScale());
        $column->setNumericUnsigned($columnObject->getNumericUnsigned());
        $column->setErratas($columnObject->getErratas());

        return $column;
    }

    public function applyConfig(array $config)
    {
        if (isset($config['readonly'])) {
            $this->readonly = (bool) $config['readonly'];
        }
        if (isset($config['preview'])) {
            $this->preview = (bool) $config['preview'];
        }
        if (isset($config['download'])) {
            $this->download = (bool) $config['download'];
        }

        $foreign = $this->getForeignColumn();

        if (!is_null($foreign) && isset($config['reference'])) {
            $this->setReference($config['reference']);
        }
    }

    /**
     * @param mixed $dataType
     */
    public function setDataType(/*string*/$dataType) : void
    {
        $driver = $this->adapter->getDriver()->getConnection()->getDriverName();

        if ($dataType === 'USER-DEFINED' && $driver === 'pgsql') {
            $dataType = $this->getPostgreSQLDataType();
        }

        $this->dataType = $dataType;
    }

    /**
     * @param array $config
     */
    public function setReference(array $config) : void
    {
        if (isset($config['reference']['mode']) && in_array($config['reference']['mode'], ['default', 'datalist', 'listbox'])) {
            $mode = $config['reference']['mode'];
        } else {
            $mode = 'default';
        }

        $referenceTable = new Table($this->adapter, $foreign->getSchemaName(), $foreign->getTableName());
        if (isset($config['reference']['display']) && $referenceTable->hasColumn($config['reference']['display'])) {
            $display = $config['reference']['display'];
        } else {
            $display = $referenceTable->getKeyColumn()->getName();
        }

        $this->reference = [
                'mode'    => $mode,
                'display' => $display,
            ];
    }

    /**
     * @return array|null
     */
    public function getReference() : ?array
    {
        return $this->reference;
    }

    /**
     * @return ReferenceColumn|null
     */
    public function getReferenceColumn(): ?ReferenceColumn
    {
        $name = $this->name;

        $constraints = array_filter($this->constraints, function ($constraint) {
            return $constraint->isForeignKey() && $constraint->hasColumns() && in_array($this->name, $constraint->getColumns());
        });

        if (count($constraints) === 0) {
            return null;
        }

        $foreign = current($constraints);
        $columns = $foreign->getReferencedColumns();

        if (count($columns) > 1) {
            throw new Exception(
                sprintf(
                    'Reference is only compatible with single column. The column "%s" references %d columns (%s).',
                    $this->name,
                    count($columns),
                    implode(', ', $columns)
                )
            );
        }

        return new ReferenceColumn(
            new Table($this->adapter, $foreign->getReferencedTableSchema(), $foreign->getReferencedTableName()),
            $columns[0]
        );
    }

    /**
     * @return bool
     */
    public function isForeignKey(): bool
    {
        $constraints = array_filter($this->constraints, function ($constraint) {
            return $constraint->isForeignKey() && $constraint->hasColumns() && in_array($this->name, $constraint->getColumns());
        });

        return count($constraints) > 0;
    }

    /**
     * @return string
     */
    private function getPostgreSQLDataType(): string
    {
        $connection = $this->adapter->getDriver()->getConnection()->getConnectionParameters();

        $db = pg_connect(
            sprintf(
                'host=%s port=%d dbname=%s user=%s password=%s',
                $connection['host'],
                $connection['port'],
                $connection['dbname'],
                $connection['user'],
                $connection['password']
            )
        );

        $metadata = pg_meta_data($db, sprintf('%s.%s', $this->schemaName, $this->tableName));

        pg_close($db);

        return $metadata[$this->name]['type'];
    }
}
