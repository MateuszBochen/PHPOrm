<?php

namespace BlockBlog\Repository;

use BlockBlog\Mysql;
use BlockBlog\Repository\RepositoryException;

class Repository extends BaseRepository
{

    private $entityClass;
    
    private $tableName;
    private $entityClassString;
    private $orderByString;
    private $repositoryClass;

    public function __construct(Mysql $mysqlClass, $entityClass)
    {
        parent::__construct($mysqlClass);

        $this->entityClass = $entityClass;
        $this->prepareObiect($this->entityClass);

        $repositoryClassString = $this->entityClassString.'Repository';

        if (class_exists($repositoryClassString)) {
            $this->repositoryClass = new $repositoryClassString($mysqlClass);
        }
    }

    public function __call($method, $args) {

        if ($this->repositoryClass && method_exists($this->repositoryClass, $method)) {
            return call_user_func_array(array($this->repositoryClass, $method), $args);   
        }
        else {
           new RepositoryException('Unknown function '.get_class($this->repositoryClass).':'.$method, E_USER_ERROR);
        }
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

        $select = $this->prepareSelect($this->getVars($this->entityClass));

        $query = "SELECT {$select} FROM `{$this->tableName}` {$where} {$this->orderByString}LIMIT {$start}, {$limit}";
        $query = $this->pdo->prepare($query);
        $query->execute();

        $this->orderByString = '';

        return $query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->entityClassString);
    }

    public function findOneBy(array $conditions)
    {
        $where = $this->prepareWhere($conditions);

        $select = $this->prepareSelect($this->getVars($this->entityClass));

        $query = "SELECT {$select} FROM `{$this->tableName}` {$where} LIMIT 1";
        $query = $this->pdo->prepare($query);
        $query->execute();

        return $query->fetchObject($this->entityClassString);
    }

    private function prepareObiect($entityClass)
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
        $this->tableName = $this->prefix.$this->tableName;
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
}
