<?php

namespace BlockBlog;

class Mysql
{
    private $pdo;
    private $query;
    private $prefix;
    
    public function __construct($configs)
    {
        
        $dns = 'mysql:host='.$configs['host'];
        $dns .= (isset($configs['port']) && $configs['port'] ? ';port='.$configs['port'] : '');
        
        $dns .= ';dbname='.$configs['databaseName'];
        $dns .= ';charset='.$configs['charset'];
        
        $this->pdo = new \PDO($dns, $configs['user'], $configs['password']);
        
        $this->prefix = $configs['prefix'];
    }
    
    public function query($query)
    {
        $this->query = $this->pdo->prepare($query);
        
        return $this;
    }
    
    public function get()
    {
        $array = ['user_name'];

        $this->query->execute();
        //return $this->query->fetchObject('System\User\UserEntity\MainUser');//(\PDO::FETCH_ASSOC);
        return $this->query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, 'System\User\UserEntity\MainUser');//(\PDO::FETCH_ASSOC);
    }
    
    public function exec()
    {
        return $this->query->execute();
    }
    
    public function insertMulti($array)
    {
        
    }
    
    public function getPdo()
    {
        return $this->pdo;
    }

    /* public function select($tableName, array $conditions = [], array $selectColumns = [])
    {
        $what = '*';
        $where = 'true';

        if ($selectColumns !== array()) {
            array_walk($selectColumns, function(&$value, $index){
                $value = '`'.$value.'`';
            });
            
            $what = implode(', ', $selectColumns);
        }

        if ($conditions !== array()) {
            array_walk($conditions, function(&$value, $index){
                $value = "`".$index."` = '".$value."'";
            });
            
            $where = 'WHERE '.implode('AND ',$conditions);
        }
        
        $query = 'SELECT '.$what.' FROM `'.$tableName.'` '.$where;
    } */
    
}
