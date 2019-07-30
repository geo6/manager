<?php

declare(strict_types=1);

namespace App\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Metadata\Object\ColumnObject;
use Zend\Db\Metadata\Object\ConstraintObject;

class Column extends ColumnObject
{
    /** @var Adapter */
    private $adapter;

    /** @var ConstraintObject[] */
    private $constraints = [];

    public function __construct(Adapter $adapter, string $name, string $tableName, ?string $schemaName = null)
    {
        parent::__construct($name, $tableName, $schemaName);

        $this->adapter = $adapter;

        $constraints = (new Metadata($adapter))->getConstraints($this->tableName, $this->schemaName);
        $this->constraints = array_filter($constraints, function (ConstraintObject $constraint) use ($name) {
            return in_array($name, $constraint->getColumns());
        });
    }

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

    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function setDataType(/*string*/$dataType)
    {
        $driver = $this->adapter->getDriver()->getConnection()->getDriverName();

        if ($dataType === 'USER-DEFINED' && $driver === 'pgsql') {
            $dataType = $this->getPostgreSQLDataType();
        }

        $this->dataType = $dataType;

        return $this;
    }

    public function setConstraints(array $constraints)
    {
        $name = $this->name;

        $this->constraints = array_filter($constraints, function (ConstraintObject $constraint) use ($name) {
            return in_array($name, $constraint->getColumns());
        });

        return $this;
    }

    public function getForeignColumn(): ?self
    {
        $name = $this->name;

        $foreign = current(array_filter($this->constraints, function ($constraint) use ($name) {
            return $constraint->isForeignKey() && $constraint->hasColumns() && $constraint->getColumns() === [$name];
        }));

        if ($foreign === false) {
            return null;
        }

        return new self(
            $this->adapter,
            current($foreign->getReferencedColumns()),
            $foreign->getReferencedTableName(),
            $foreign->getReferencedTableSchema()
        );
    }

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
