<?php

namespace Ripple\Relationships;

require_once('./autoload.php');

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Ripple\Collection;
use Ripple\Database;

class ManyToMany implements ArrayAccess, IteratorAggregate
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
            if (isset($object['created_at']) && !$class->hasProperty('created_at')) {
                $property = $class->getProperty('created_at');
                $property->setValue($entity, $object['created_at']);
            }
        }
        return $entity;
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

        /*$queryString = "SELECT * FROM $parentTable p INNER JOIN $relatedClassTable c ON c.$this->foreign_key = p.id AND c.post_id = $parentId";*/
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

    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
}
