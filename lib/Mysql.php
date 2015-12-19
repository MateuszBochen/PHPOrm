<?php

namespace BlockBlog;

class Mysql
{
    private $pdo;
    private $query;
    private $prefix;
    private $insertColumns;
    private $addingToColumnsList = true;
    private $queriesLog = [];

    public function __construct($configs)
    {
        $dns = 'mysql:host='.$configs['host'];
        $dns .= (isset($configs['port']) && $configs['port'] ? ';port='.$configs['port'] : '');

        $dns .= ';dbname='.$configs['databaseName'];
        $dns .= ';charset='.$configs['charset'];

        $this->pdo = new \PDO($dns, $configs['user'], $configs['password']);

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->prefix = $configs['prefix'];
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function update($tableName, array $array, array $conditions)
    {
        if (!preg_match("/^{$this->prefix}[a-z0-9]+/", $tableName)) {
            $tableName = $this->prefix.$tableName;
        }

        $conditions = $this->prepareConditions($conditions);
        $values = $this->prepareValuesToUpdate($array);

        $query = "UPDATE `{$tableName}` SET {$values} WHERE $conditions LIMIT 1";
        $this->queriesLog[] = $query;

        $this->query($query)->exec();
    }

    public function insert($tableName, array $array)
    {
        if (!preg_match("/^{$this->prefix}[a-z0-9]+/", $tableName)) {
            $tableName = $this->prefix.$tableName;
        }

        $values = [];
        $columns = '';
        $this->insertColumns = null;
        $this->addingToColumnsList = true;

        if (isset($array[0]) && is_array($array[0])) {
            foreach ($array as $item) {
                $values[] = $this->prepareValuesToInsert($item);
                $this->addingToColumnsList = false;
            }

            $values = implode(', ', $values);
        }
        else {
            $values = $this->prepareValuesToInsert($array);
        }

        $columns = $this->prepareColumnsToInsert();
        $query = "INSERT INTO `{$tableName}` {$columns} VALUES {$values}";

        $this->query($query)->exec();

        return $this->lastId();
    }

    public function lastId()
    {
        return $this->pdo->lastInsertId();
    }

    public function query($query)
    {
        $this->queriesLog[] = $query;
        $this->query = $this->pdo->prepare($query);

        return $this;
    }

    public function get()
    {
        $this->query->execute();

        return $this->query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getQueriesLog()
    {
        return $this->queriesLog;
    }

    public function addToQueryLog($query)
    {
        $this->queriesLog[] = $query;

        return $this;
    }

    public function exec()
    {
        try {
            return $this->query->execute();
        }
        catch(\PDOException $e) {
            throw new MysqlException($e->getMessage(), $this->getQueriesLog());
        }
    }

    private function prepareValuesToInsert(array $array)
    {
        array_walk($array, function(&$val, $column){
            $val = addslashes($val);
            $val = "'{$val}'";
            if ($this->addingToColumnsList) {
                $this->insertColumns[] = "`{$column}`";
            }
        });

        return '('.implode(', ', $array).')';
    }

    private function prepareColumnsToInsert()
    {
        return '('.implode(', ', $this->insertColumns).')';
    }

    private function prepareValuesToUpdate(array $array)
    {
        return implode(', ', $this->makeArrayColumnToValue($array));
    }

    public function prepareConditions(array $array)
    {
        return implode(' AND ', $this->makeArrayColumnToValue($array)); 
    }

    private function makeArrayColumnToValue($array)
    {
        $tmp = [];

        foreach ($array as $column => $val) {
            $val = addslashes($val);
            $tmp[] = "`$column` = '{$val}'";
        }

        return $tmp;
    }
}
