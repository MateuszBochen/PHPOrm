<?php

namespace BlockBlog;

class Mysql
{
    private $pdo;
    private $query;
    private $prefix;
    private $insertColumns;
    private $addingToColumnsList = true;

    public function __construct($configs)
    {
        $dns = 'mysql:host='.$configs['host'];
        $dns .= (isset($configs['port']) && $configs['port'] ? ';port='.$configs['port'] : '');
        
        $dns .= ';dbname='.$configs['databaseName'];
        $dns .= ';charset='.$configs['charset'];
        
        $this->pdo = new \PDO($dns, $configs['user'], $configs['password']);
        
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

    public function query($query)
    {
        $this->query = $this->pdo->prepare($query);

        return $this;
    }

    public function get()
    {
        $this->query->execute();

        return $this->query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function exec()
    {
        return $this->query->execute();
    }

    public function insert($tableName, array $array)
    {
        $tableName = $this->prefix.$tableName;
        $values ;
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

    private function prepareValuesToInsert(array $array)
    {
        array_walk($array, function(&$val, $column){
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
}
