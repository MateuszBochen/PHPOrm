<?php

namespace BlockBlog\Repository;

class BaseRepository
{
    protected $mysqlClass;
    protected $pdo;
    protected $prefix;

    public function __construct($mysqlClass)
    {
        $this->mysqlClass = $mysqlClass;
        $this->pdo = $mysqlClass->getPdo();
        $this->prefix = $mysqlClass->getPrefix();
    }

    protected function getTableName($className)
    {
        $array = explode('\\', $className);

        return end($array);
    }

    protected function transformToUnderscore($string, $plural = false)
    {
        return strtolower(preg_replace('/\B[A-Z]/', "_$0", $string)).($plural ? 's' : '');
    }

    protected function transformToCapitalLetter($string)
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
}
