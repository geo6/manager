<?php

declare(strict_types=1);

namespace App\Model;

use Exception;
use Zend\Db\Sql\TableIdentifier;
use Zend\Db\Sql\Predicate\Predicate;

class Filter
{
    /** @var string */
    private $filter;

    /** @var string */
    private $identifier;

    /** @var string */
    private $operation;

    /** @var string|null */
    private $value;

    /** @var string */
    private $key;

    /** @var TableIdentifier */
    private $table;

    /** @var Predicate */
    private $predicate;

    public function __construct(string $filter)
    {
        $this->filter = $filter;

        $this->extractFields();
        $this->extractTableAndKey();
        $this->buildPredicate();
    }

    public function getTable(): TableIdentifier
    {
        return $this->table;
    }

    public function getPredicate(): Predicate
    {
        return $this->predicate;
    }

    private function extractFields(): void
    {
        $match = preg_match('/^(.+?) (eq|ne|gt|ge|lt|le|like|nlike|null|nnull)(?: (.+?))?$/', $this->filter, $matches);

        if ($match !== 1) {
            throw new Exception(sprintf('Invalid filter "%s".', $this->filter));
        }

        if (count($matches) === 4) {
            list($f, $identifier, $operation, $value) = $matches;

            $this->identifier = $identifier;
            $this->operation = $operation;
            $this->value = $value;
        } else {
            list($f, $identifier, $operation) = $matches;

            $this->identifier = $identifier;
            $this->operation = $operation;
            $this->value = null;
        }
    }

    private function extractTableAndKey(): void
    {
        $match = preg_match('/^(.+?)\.(.+?)\.(.+?)$/', $this->identifier, $matches);

        if ($match !== 1 || count($matches) !== 4) {
            throw new Exception(sprintf('Invalid identifier "%s".', $this->identifier));
        }

        list($i, $schema, $table, $column) = $matches;

        $this->table = new TableIdentifier($table, $schema);
        $this->key = $column;
    }

    private function buildPredicate(): void
    {
        $this->predicate = new Predicate();

        switch ($this->operation) {
            case 'eq':
                $this->predicate->equalTo($this->key, $this->value);
                break;
            case 'ne':
                $this->predicate->notEqualTo($this->key, $this->value);
                break;
            case 'gt':
                $this->predicate->greaterThan($this->key, $this->value);
                break;
            case 'ge':
                $this->predicate->greaterThanOrEqualTo($this->key, $this->value);
                break;
            case 'lt':
                $this->predicate->lessThan($this->key, $this->value);
                break;
            case 'le':
                $this->predicate->lessThanOrEqualTo($this->key, $this->value);
                break;
            case 'like':
                $this->predicate->like($this->key, $this->value);
                break;
            case 'nlike':
                $this->predicate->notLike($this->key, $this->value);
                break;
            case 'null':
                $this->predicate->isNull($this->key);
                break;
            case 'nnull':
                $this->predicate->isNotNull($this->key);
                break;
        }
    }
}
