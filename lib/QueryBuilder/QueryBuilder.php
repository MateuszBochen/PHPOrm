<?php

namespace BlockBlog\QueryBuilder;

use BlockBlog\Mysql;
use BlockBlog\Base;

class QueryBuilder extends Base
{
    const SELECT = 1;
    const DELETE = 2;
    const UPDATE = 3;

    private $select = '';
    private $update = [];
    private $limit = '';
    private $whereCollection = [];
    private $tableName = '';
    private $entityClass;
    private $method;

    public function __construct(Mysql $mysqlClass)
    {
        parent::__construct($mysqlClass);
    }

    public function delete()
    {
        $this->method = self::DELETE;

        return $this;
    }

    public function select()
    {
        $args = func_get_args();
        $this->method = self::SELECT;

        if (count($args) == 0) {
            return $this;
        }

        $this->select = $this->prepareSelect($args);

        return $this;
    }

    public function update($values)
    {
        $this->method = self::UPDATE;

        $this->update = $values;

        return $this;
    }

    public function from($tableOrEntity)
    {
        if (is_object($tableOrEntity)) {
            $this->tableName = $this->getTableName($tableOrEntity);
            $this->entityClass = $tableOrEntity;
        }
        elseif (file_exists($tableOrEntity) && class_exists($tableOrEntity)) {

            $this->tableName = $this->getTableName($tableOrEntity);
            $this->entityClass = new $tableOrEntity();
        }
        else {
            $this->tableName = $this->transformToUnderscore($tableOrEntity);
        }

        return $this;
    }

    public function where($column, $condition, $operator = '=')
    {
        if ($this->whereCollection !== array()) {
            throw new QueryBuilderException('After using where() please use andWhere() or orWhere()');
        }

        $this->whereCollection[] = [
            'glue' => '',
            'column' => $column,
            'condition' => $condition,
            'operator' => $operator
        ];

        return $this;
    }

    public function andWhere($column, $condition, $operator = '=')
    {
        if ($this->whereCollection === array()) {
            throw new QueryBuilderException('Before You use andWhere() please use where()');
        }

        $this->whereCollection[] = [
            'glue' => 'AND',
            'column' => $column,
            'condition' => $condition,
            'operator' => $operator
        ];

        return $this;
    }

    public function orWhere($column, $condition, $operator = '=')
    {
        if ($this->whereCollection === array()) {
            throw new QueryBuilderException('Before You use orWhere() please use where()');
        }

        $this->whereCollection[] = [
            'glue' => 'OR',
            'column' => $column,
            'condition' => $condition,
            'operator' => $operator
        ];

        return $this;
    }

    public function limit($limit, $offSet = NULL)
    {
        $this->limit = "LIMIT ". ($offSet ? $offSet.' '.$limit : $limit);

        return $this;
    }

    public function exec()
    {
        switch($this->method) {
            case self::SELECT:
                return $this->execSelect();
                break;
            case self::UPDATE:
                return $this->execUpdate();
                break;
            case self::DELETE:
                return $this->execDelete();
                break;
        }
    }

    private function execSelect()
    {
        $select = '';

        if ($this->select == '' && $this->entityClass) {
            $allColumns = $this->getVars($tihs->entityClass);
            $select = $this->prepareSelect($allColumns);
        }
        elseif ($this->select == '' && !$this->entityClass) {
            $select = '*';
        }
        else {
            $select = $this->select;
        }

        $query = "SELECT {$select} FROM `{$this->tableName}` {$this->prepareWhere()} {$this->limit} ";
        $this->mysqlClass->addToQueryLog($query);

        $query = $this->pdo->prepare($query);
        $query->execute();

        if ($this->entityClassString) {
            return $query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->entityClassString);
        }
        else {
            return $query->fetchAll(\PDO::FETCH_OBJ);
        }
    }

    private function execUpdate()
    {
        $query = "UPDATE `{$this->tableName}` SET {$this->prepareUpdate()} {$this->prepareWhere()} {$this->limit}";
        $this->mysqlClass->addToQueryLog($query);

        $query = $this->pdo->prepare($query);

        return $query->execute();
    }

    private function execDelete()
    {
        $query = "DELETE FROM `{$this->tableName}` {$this->prepareWhere()} {$this->limit}";
        $this->mysqlClass->addToQueryLog($query);

        return $this->pdo->exec($query);
    }

    private function prepareWhere()
    {
        $where = '';

        foreach ($this->whereCollection as $value) {
            $column = $this->transformToUnderscore($value['column']);

            $where .= "{$value['glue']} `{$column}` {$value['operator']} '{$value['condition']}' ";
        }

        return ($where ? 'WHERE'.$where : '');
    }

    private function prepareUpdate()
    {
        $array = [];

        foreach ($this->update as $column => $value) {
            $column = $this->transformToUnderscore($column);
            $array[] = "`{$column}` = '{$value}'";
        }

        return implode(', ', $array);
    }
}
