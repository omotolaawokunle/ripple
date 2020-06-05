<?php

namespace Ripple\Relationships;

use Ripple\Database;

class ManyToMany extends Relationship
{
    private $relatedClass;
    private $parent;
    private $pivotTable;
    private $parentName;
    private $relatedName;
    private $parentKey;
    private $relatedKey;
    protected $items;

    public function __construct($parent, $relatedClass, $pivotTable, $parentKey, $relatedKey)
    {
        $this->parent = $parent;
        $this->relatedClass = new \ReflectionClass($relatedClass);
        $this->pivotTable = $pivotTable;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
        $this->parentName = $this->init_class($parent)->getTable();
        $this->relatedName = $this->init_child_class()->getTable();
        $this->items =  $this->getItems();
    }

    public function getItems()
    {
        if (!isset($db)) {
            $db = new Database();
        }
        $query = $this->buildString();
        $result = $db->db()->query($query);
        $response = [];
        if ($result) {
            $response['fields'] = $db->fetchFields($result);
            $response['values'] = $result->fetch_all(MYSQLI_ASSOC);
        }
        $classObj = $this->classify($response);
        return $classObj;
    }

    private function classify(array $array)
    {
        return $this->buildObject($array);
    }

    private function buildObject($result)
    {
        $response = [];
        if ($result) {
            $fields = $result['fields'];
            $values = $result['values'];
            $num_of_rows = count($result['values']);
            $num_of_fields = count($result['fields']);
            $buildResponse = [];

            for ($i = 0; $i < $num_of_rows; $i++) {
                for ($j = 0; $j < $num_of_fields; $j++) {
                    $buildResponse[$fields[$j]] = $values[$i][$fields[$j]];
                }
                $response[] = $this->morph($buildResponse);
            }
        }
        return $response;
    }

    private function morph(array $object)
    {
        $class = $this->relatedClass;
        $entity = $class->newInstanceWithoutConstructor();

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if (isset($object[$prop->getName()])) {
                $prop->setValue($entity, $object[$prop->getName()]);
            }


            $pivot = $this->getPivotObject();
            foreach ($pivot as $key => $value) {
                if (isset($object[$key])) {
                    $pivot->{$key} = $object[$key];
                }
            }
            $entity->pivot = $pivot;
        }
        return $entity;
    }

    public function getPivotObject()
    {
        $db = new \Ripple\Database();
        $string = $db->buildQueryString($this->pivotTable, 'select');
        $pivotFields = $db->fetchFields($string);
        $pivot = [];
        foreach ($pivotFields as $field) {
            if ($field !== 'id') {
                $pivot[$field] = '';
            }
        }
        return (object) json_decode(json_encode($pivot));
    }


    private function buildString()
    {
        $asString = $this->buildAsString();
        $parentKey = $this->parentKey;
        $relatedKey = $this->relatedKey;
        $parentTable = $this->parentName;
        $pivotTable = $this->pivotTable;
        $parentId = $this->parent->id;
        $relatedTable = $this->relatedName;

        $queryString = "SELECT * FROM $parentTable AS p JOIN (SELECT * FROM $pivotTable AS j JOIN $relatedTable AS c ON j.$relatedKey=c.id) AS jc ON p.id=jc.$parentKey AND jc.$parentKey=$parentId";
        return $queryString;
    }

    private function buildAsString()
    {
        $relatedClassFields = $this->relatedClass->getProperties(\ReflectionProperty::IS_PUBLIC);
        $new_class = new \ReflectionClass($this->parent);
        $parentFields = $new_class->getProperties(\ReflectionProperty::IS_PUBLIC);
        $asString = [];
        foreach ($relatedClassFields as $field) {
            $key = "c." . $field->getName();
            $value = $field->getName();
            $asString[] = $key . ' AS ' . $value;
        }
        foreach ($parentFields as $field) {
            $key = "p." . $field->getName();
            $value = $field->getName();
            $asString[] = $key . ' AS ' . $value;
        }
        $asString = implode(', ', $asString);
        return $asString;
    }

    private function init_child_class()
    {
        return $this->relatedClass->newInstance();
    }
    private function init_class($class)
    {
        $parent = new \ReflectionClass($class);
        return $parent->newInstance();
    }

    /**
     * Create a new instance of the child class
     */
    public function attach($id, $params)
    {
        return $this->add($this, $id, $params);
    }

    public function getChild()
    {
        return isset($this->child) ? $this->child : false;
    }

    public function getParent()
    {
        return isset($this->parent) ? $this->parent : false;
    }

    public function relatedKey()
    {
        return isset($this->relatedKey) ? $this->relatedKey : false;
    }

    public function parentKey()
    {
        return isset($this->parentKey) ? $this->parentKey : false;
    }

    public function relatedClass()
    {
        return isset($this->relatedClass) ? $this->relatedClass : false;
    }

    public function foreignKey()
    {
        return isset($this->foreign_key) ? $this->foreign_key : false;
    }

    public function pivot()
    {
        return isset($this->pivotTable) ? $this->pivotTable : false;
    }
}
