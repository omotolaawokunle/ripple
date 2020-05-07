<?php

namespace Ripple\Relationships;

require_once('./autoload.php');

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Ripple\Collection;
use Ripple\Database;

class ManyToOne implements ArrayAccess, IteratorAggregate
{
    private $parent;
    private $child;
    private $foreign_key;
    protected $items;

    public function __construct($child, $parent, $foreign_key)
    {
        $this->child = $child;
        $this->parent = new \ReflectionClass($parent);
        $this->foreign_key = $foreign_key;
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
        //print_r($response['values']);
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
        //return json_decode(json_encode($response));
    }

    private function morph(array $object)
    {
        $class = $this->parent;
        $entity = $class->newInstanceWithoutConstructor();

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if (isset($object[$prop->getName()])) {
                $prop->setValue($entity, $object[$prop->getName()]);
            }
            if (isset($object['created_at']) && !$class->hasProperty('created_at')) {
                $property = $class->getProperty('created_at');
                $property->setValue($entity, $object['created_at']);
            }
            if (isset($object['parent_id']) && !$class->hasProperty('parent')) {
                $entity->parent = $this->child;
            }
        }
        return $entity;
    }


    private function buildString()
    {
        $asString = $this->buildAsString();
        $childTable = $this->child->getTable();
        $childId = $this->child->id;
        $parentTable = $this->init_child_class()->getTable();
        $queryString = "SELECT $asString FROM $parentTable p INNER JOIN $childTable c ON c.$this->foreign_key = p.id AND c.id = $childId";
        return $queryString;
    }

    private function buildAsString()
    {
        $childFields = $this->parent->getProperties(\ReflectionProperty::IS_PUBLIC);
        $asString = [];
        foreach ($childFields as $field) {
            $key = "p." . $field->getName();
            $value = $field->getName();
            $asString[] = $key . ' AS ' . $value;
        }
        $asString = implode(', ', $asString);
        return $asString;
    }

    private function init_child_class()
    {
        return $this->parent->newInstance();
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
