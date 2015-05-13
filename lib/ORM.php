<?php

namespace BlockBlog;

use BlockBlog\Mysql;
use BlockBlog\Repository\Repository;
use BlockBlog\Base;

class ORM extends Base
{
    private $insertCollection = [];
    private $updateCollection = [];
    private $collection = [];

    public function __construct(Mysql $mysqlClass)
    {  
        $this->mysqlClass = $mysqlClass;

        parent::__construct($mysqlClass);
    }

    public function getRepository($entityClass)
    {
        return new Repository($this->mysqlClass, $entityClass);
    }

    public function add($entity)
    {
        if (is_array($entity)) {
            $this->collection = array_merge($this->collection, $entity);
        }
        elseif (is_object($entity)) {
            $this->collection[] = $entity;
        }

        return $this;
    }

    public function save()
    {
        foreach($this->collection as $entity) {
            if (method_exists($entity, 'getId')) {
                if ($entity->getId()  > 0) {
                    $this->addToUpdate($entity);
                }
                else {
                    $this->addToInsert($entity);
                }
            }
        }

        $this->insert();
        $this->update();

        $this->insertCollection = [];
        $this->updateCollection = [];
        $this->collection = [];
    }

    public function delete()
    {
        foreach($this->collection as $entity) {
            $tableName = '';
            if (method_exists($entity, 'getId')) {
                $tableName = $thid->getTableName($entity);
                $id = $entity->getId();

                $mysqlClass->query("DELETE FROM `$tableName` WHERE `id` = '$id' LIMIT 1");
                $mysqlClass->exec();
            }
        }

        $this->collection = [];
    }

    private function addToInsert($entity)
    {
        $tableName = $this->getTableName($entity);
        $properties = $this->getVars($entity);
        $valuesArray = $this->getValuesArray($entity, $properties);

        $this->insertCollection[$tableName][] = $valuesArray;
    }

    private function addToUpdate($entity)
    {
        $tableName = $this->getTableName($entity);
        $properties = $this->getVars($entity);
        $valuesArray = $this->getValuesArray($entity, $properties);

        $this->updateCollection[] = [
            'tableName' => $tableName,
            'values' => $valuesArray,
            'conditions' => ['id' => $entity->getId()]
        ];
    }

    private function insert()
    {
       foreach ($this->insertCollection as $tableName => $values) {
            $this->mysqlClass->insert($tableName, $values);
        }
    }

    private function update()
    {
        foreach ($this->updateCollection as $values) {
            $this->mysqlClass->update($values['tableName'], $values['values'], $values['conditions']);
        }
    }

    private function getValuesArray($entity, array $properties)
    {
        $valuesArray = [];

        foreach ($properties as $property) {

            $columnName = $this->transformToUnderscore($property);
            $functionName = 'get'.ucfirst($property);

            $valuesArray[$columnName] = $entity->$functionName();
        }

        return $valuesArray;
    }
}
