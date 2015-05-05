<?php

namespace BlockBlog;

use BlockBlog\Mysql;
use BlockBlog\Repository\Repository;

class ORM
{   
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
