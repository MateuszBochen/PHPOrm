<?php

namespace BlockBlog;

use BlockBlog\Mysql;

class ORM
{

    private $pdo;
    private $prefix;
    private $orderByString;
    private $tableName;
    private $entityClass;
    private $entityClassString;

    public function __construct(Mysql $mysqlClass)
    {
        $this->pdo = $mysqlClass->getPdo();
        $this->prefix = $mysqlClass->getPrefix();
    }

    public function setEntity($entityClass)
    {

        if (is_string($entityClass)) {
            $this->entityClassString = $entityClass;
            $this->entityClass = new $entityClass();
        }
        elseif (is_object($entityClass)) {
            $this->entityClass = $entityClass;
            $this->entityClassString = get_class($this->entityClass);
        }

        $this->tableName = $this->getTableName($this->entityClassString);
        $this->tableName = $this->prefix.$this->transformToUnderscore($this->tableName, true);

        return $this;
    }

    public function setOrderBy(array $conditions)
    {
        $orderColumns = [];

        foreach ($conditions as $columnName => $orderType) {
            $orderColumns[] = "`{$this->transformToUnderscore($columnName)}` {$orderType}";
        }

        $this->orderByString = "ORDER BY ".implode(', ', $orderColumns)." ";

        return $this;
    }

    public function findAll(array $conditions = [], $start = 0, $limit = 4294967295)
    {
        $where = 'WHERE 1';

        if ($conditions !== array()) {
            $where = $this->prepareWhere($conditions);
        }

        $select = $this->prepareSelect($this->getVars());

        $query = "SELECT {$select} FROM `{$this->tableName}` {$where} {$this->orderByString}LIMIT {$start}, {$limit}";
        $query = $this->pdo->prepare($query);
        $query->execute();

        $this->orderByString = '';

        return $query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->entityClassString);
    }

    public function findOneBy(array $conditions)
    {
        $where = $this->prepareWhere($conditions);

        $select = $this->prepareSelect($this->getVars());

        $query = "SELECT {$select} FROM `{$this->tableName}` {$where} LIMIT 1";
        $query = $this->pdo->prepare($query);
        $query->execute();

        return $query->fetchObject($this->entityClassString);
    }

    private function prepareWhere(array $conditions)
    {
        $whereColumns = [];

        foreach ($conditions as $column => $value) {
            $whereColumns[] = "`{$this->transformToUnderscore($column)}` = '{$value}'";
        }

        return 'WHERE '.implode(' AND ', $whereColumns);
    }

    private function prepareSelect($properties)
    {
        $columns = [];

        foreach ($properties as $property) {
            $columns[] = "`{$this->transformToUnderscore($property)}` as `{$property}`";
        }

        return implode(', ', $columns);
    }

    private function getTableName($className)
    {
        $array = explode('\\', $className);

        return end($array);
    }

    private function transformToUnderscore($string, $plural = false)
    {
        return strtolower(preg_replace('/\B[A-Z]/', "_$0", $string)).($plural ? 's' : '');
    }

    private function transformToCapitalLetter($string)
    {
        return preg_replace_callback(
            '/\B(_([a-z]))/',
             function($matches){
                if (isset($matches[2])) {
                    return strtoupper($matches[2]);
                }
            },
            $string
        );
    }

    private function getVars()
    {
        $properties = [];

        $reflect = new \ReflectionObject($this->entityClass);

        foreach ($reflect->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
            $properties[] = $property->name;
        }

        return $properties;
    }
}
