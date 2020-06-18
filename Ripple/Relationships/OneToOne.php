<?php

namespace Ripple\Relationships;


use Ripple\Database;
use Ripple\Morpher;

class OneToOne extends Relationship
{
    private $child;
    private $parent;
    private $foreign_key;
    protected $items;

    public function __construct($parent_id, $child, $foreign_key)
    {
        $this->parent = $parent_id;
        $this->child = new \ReflectionClass($child);
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
        $class = $this->child->getName();
        $morpher = new Morpher;
        $entity = $morpher($class, $object);
        return $entity;
    }


    private function buildString()
    {
        $parentId = $this->parent;
        $childTable = $this->init_child_class()->getTable();

        $queryString = "SELECT * FROM $childTable WHERE $this->foreign_key = $parentId";
        return $queryString;
    }

    private function init_child_class()
    {
        return $this->child->newInstance();
    }

    /**
     * Create a new instance of the child class
     */
    public function create($params)
    {
        return $this->save($this, $params);
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
