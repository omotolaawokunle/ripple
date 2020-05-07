<?php

namespace Ripple;


require_once('./autoload.php');

use ArrayAccess;
use ReflectionProperty;
use Ripple\Collection;
use Ripple\Database;
use Ripple\Relationships\ManyToMany;
use Ripple\Relationships\OneToMany;

abstract class Entity
{
    protected $table;
    private $db;
    public $created_at;

    public function __construct()
    {
        if ($this->db !== '') {
            $this->db = new Database();
        }
        $this->loadClassProperties();
    }

    /**
     * @return Entity
     */
    public function findAll()
    {
        $result = $this->db->select($this->table, '*', []);
        return $this->buildObject($result);
    }

    /**
     * @param $id
     * @return Entity
     */
    public function findById($id)
    {
        $result = $this->db->select($this->table, '*', ['id' => ['=', $id, '']]);
        $result = $this->buildObject($result);

        if ($result) {
            //return $result;
            return $result[0];
        }
        return (object) [];
    }

    /**
     * @return mixed
     */
    public function save()
    {
        $fields = $this->db->fetchFields($this->db->buildQueryString($this->table, 'select'));
        if (isset($this->id)) {
            return $this->db->update($this->table, $fields, (array) $this, ['id' => ['=', $this->id, '']]);
        }
        return $this->db->insert($this->table, $fields, (array) $this);
    }

    /**
     * @param $object
     * @return Entity
     */
    public static function morph(array $object)
    {
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        foreach ($class->getProperties(\ReflectionProperty::IS_PROTECTED) as $prop) {
            unset($entity->{$prop->getName()});
        }
        if ($entity->db) {
            unset($entity->db);
        }
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if (isset($object[$prop->getName()])) {
                $prop->setValue($entity, $object[$prop->getName()]);
            }
            if (isset($object['created_at']) && !$class->hasProperty('created_at')) {
                $property = $class->getProperty('created_at');
                $property->setValue($entity, $object['created_at']);
            }
        }
        //$entity->initialize();

        return $entity;
    }

    /**
     * @param $result
     * @param $class Class of the parsed $result
     * @return object
     */
    public function buildObject($result)
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
        return $this->collect($response);
        //return json_decode(json_encode($response));
    }

    private function collect(array $response)
    {
        return new Collection($response);
    }

    public function getTable()
    {
        $class = new \ReflectionClass($this);
        $entity = $class->newInstance();
        return $entity->table;
    }


    private function loadClassProperties()
    {
        $class = new \ReflectionClass($this);
        $table = '';
        if ($this->table) {
            $table = $this->table;
        } else {
            $table = strtolower($class->getShortName() . 's');
            $this->table = $table;
        }
        $fields = $this->db->fetchFields($this->db->buildQueryString($this->table, 'select'));
        foreach ($fields as $field) {
            $this->$field = null;
        }
    }

    public function hasMany($childClass, $foreign_key = null)
    {
        $class = new \ReflectionClass($this);
        $classname = strtolower($class->getShortName());
        $foreign_key = is_null($foreign_key) ? $classname . '_id' : $foreign_key;
        return new OneToMany($this, $childClass, $foreign_key);
    }
    /**
     * Many-to-many relationship
     * @param string $relatedClass Related Class
     * @param string $pivotTable Pivot table
     * @param string $parentKey Parent Key in the pivot table
     * @param string $relatedKey Related key in the pivot table
     */
    public function belongsToMany($relatedClass, $pivotTable, $parentKey, $relatedKey)
    {
        return new ManyToMany($this, $relatedClass, $pivotTable, $parentKey, $relatedKey);
    }
}
