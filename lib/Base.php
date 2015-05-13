<?php

namespace BlockBlog;

class Base
{
    protected $mysqlClass;
    protected $pdo;
    protected $prefix;
    protected $entityClassString;

    public function __construct($mysqlClass)
    {
        $this->mysqlClass = $mysqlClass;
        $this->pdo = $mysqlClass->getPdo();
        $this->prefix = $mysqlClass->getPrefix();
    }

    protected function getTableName($className)
    {
        if (is_object($className)) {
           $className = get_class($className); 
        }

        $this->entityClassString = $className;
        $array = explode('\\', $className);

        return $this->prefix.$this->transformToUnderscore(end($array), true);
    }

    protected function getVars($entity)
    {
        $properties = [];

        $reflect = new \ReflectionObject($entity);

        foreach ($reflect->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
            $properties[] = $property->name;
        }

        return $properties;
    }

    protected function prepareSelect($properties)
    {
        $columns = [];

        foreach ($properties as $property) {
            $columns[] = "`{$this->transformToUnderscore($property)}` as `{$property}`";
        }

        return implode(', ', $columns);
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
