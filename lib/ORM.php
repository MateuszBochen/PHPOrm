<?php

namespace BlockBlog;

use BlockBlog\Mysql;
use BlockBlog\Repository\Repository;

class ORM
{
    private $pdo;
    private $prefix;
    private $orderByString;
    private $tableName;
    private $entityClass;
    private $entityClassString;
    private $mysqlClass;

    public function __construct(Mysql $mysqlClass)
    {  
        $this->mysqlClass = $mysqlClass;
    }

    public function getRepository($entityClass)
    {
        return new Repository($this->mysqlClass, $entityClass);
    }
}
