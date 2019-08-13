<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Column\Foreign as ForeignColumn;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Metadata\Object\ColumnObject;
use Zend\Db\Metadata\Object\ConstraintObject;

class Column extends ColumnObject
{
    const SEPARATOR = '.';

    /** @var Adapter */
    protected $adapter;

    /** @var ConstraintObject[] */
    protected $constraints = [];

    /**
     * @param Adapter $adapter
     * @param string $name
     * @param string $tableName
     * @param string|null $schemaName
     */
    public function __construct(Adapter $adapter, string $name, string $tableName, ?string $schemaName = null)
    {
        parent::__construct($name, $tableName, $schemaName);

        $this->adapter = $adapter;

        $constraints = (new Metadata($adapter))->getConstraints($this->tableName, $this->schemaName);
        $this->constraints = array_filter($constraints, function (ConstraintObject $constraint) use ($name) {
            return in_array($name, $constraint->getColumns());
        });
    }

    /**
     * @param Adapter $adapter
     * @param ColumnObject $columObject
     *
     * @return self
     */
    public static function fromColumnObject(Adapter $adapter, ColumnObject $columnObject): self
    {
        $column = new self(
            $adapter,
            $columnObject->getName(),
            $columnObject->getTableName(),
            $columnObject->getSchemaName()
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

    /**
     * @param Adapter $adapter
     *
     * @return self
     */
    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @param mixed $dataType
     *
     * @return self
     */
    public function setDataType(/*string*/$dataType)
    {
        $driver = $this->adapter->getDriver()->getConnection()->getDriverName();

        if ($dataType === 'USER-DEFINED' && $driver === 'pgsql') {
            $dataType = $this->getPostgreSQLDataType();
        }

        $this->dataType = $dataType;

        return $this;
    }

    /**
     * @param array $constraints
     *
     * @return self
     */
    public function setConstraints(array $constraints)
    {
        $name = $this->name;

        $this->constraints = array_filter($constraints, function (ConstraintObject $constraint) use ($name) {
            return in_array($name, $constraint->getColumns());
        });

        return $this;
    }

    /**
     * @return ForeignColumn|null
     */
    public function getForeignColumn(): ?ForeignColumn
    {
        $name = $this->name;

        $foreign = current(array_filter($this->constraints, function ($constraint) use ($name) {
            return $constraint->isForeignKey() && $constraint->hasColumns() && $constraint->getColumns() === [$name];
        }));

        if ($foreign === false) {
            return null;
        }

        return new ForeignColumn(
            $this->adapter,
            current($foreign->getReferencedColumns()),
            $foreign->getReferencedTableName(),
            $foreign->getReferencedTableSchema()
        );
    }

    /**
     * @return bool
     */
    public function isForeignKey(): bool
    {
        return !is_null($this->getForeignColumn());
    }

    // private function isUnique(): bool
    // {
    //     $name = $this->name;

    //     $unique = array_filter($this->constraints, function ($constraint) use ($name) {
    //         return $constraint->isUnique() && $constraint->hasColumns() && in_array($name, $constraint->getColumns()) && count($constraint->getColumns()) === 1;
    //     });

    //     return count($unique) > 0;
    // }

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
