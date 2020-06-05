<?php

namespace Ripple;


use ArrayAccess;
use ReflectionProperty;
use Ripple\Collection;
use Ripple\Relationships\ManyToMany;
use Ripple\Relationships\ManyToOne;
use Ripple\Relationships\OneToMany;
use Ripple\Database;
use Ripple\Relationships\OneToOne;

abstract class Entity
{
    protected $table;
    private $db;


    public function __construct()
    {
        if (is_null($this->db)) {
            $this->db = new Database();
        }
        $this->loadClassProperties();
    }

    /**
     * 
     * @return \ORM\Ripple\Collection
     */
    public function findAll()
    {
        $result = $this->db->select($this->table, '*', []);
        return $this->buildObject($result);
    }

    /**
     * 
     * @return \ORM\Ripple\Collection
     */
    public static function all()
    {
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        $result = $entity->db->select($entity->table, '*', []);
        return $entity->buildObject($result);
    }

    /**
     * @param int $id
     * @return $this
     */
    public function findById($id)
    {
        $result = $this->db->select($this->table, '*', ['id' => ['=', $id, '']]);
        $result = $this->buildObject($result);
        if ($result->first()) {
            return $result->first();
        }
        return false;
    }


    /**
     * @param array $conditions
     * @return $this
     */
    public function find(array $conditions)
    {
        foreach ($conditions as $key => $value) {
            $conditions[$key] = ['=', $value, ''];
        }

        $result = $this->db->select($this->table, '*', $conditions);
        $result = $this->buildObject($result);

        if ($result->first()) {
            return $result;
        }
        return false;
    }

    /**
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * 
     * @return self
     */

    public static function where($field, $operator = '=', $value)
    {
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        $conditions = [];
        $conditions[$field] = [$operator, $value, ''];

        $result = $entity->db->select($entity->table, '*', $conditions);
        $result = $entity->buildObject($result);

        if ($result->first()) {
            return $result;
        }
        return false;
    }

    /**
     * @param string $term
     * 
     * @return self
     */
    public static function search($term)
    {
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        $fields = $entity->db->fetchFields($entity->db->buildQueryString($entity->table, 'select'));
        $conditions = [];
        foreach ($fields as $field) {
            $conditions[$field] = [' LIKE ', '%' . $term . '%', ' OR '];
        }

        $result = $entity->db->select($entity->table, '*', $conditions);
        $result = $entity->buildObject($result);

        if ($result->first()) {
            return $result;
        }
        return false;
    }



    /**
     * @return mixed
     */
    public function save()
    {
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        $fields = $entity->db->fetchFields($entity->db->buildQueryString($entity->table, 'select'));
        if (isset($this->id)) {
            return $entity->db->update($entity->table, $fields, (array) $this, ['id' => ['=', $this->id, '']]);
        }
        return $entity->db->insert($entity->table, $fields, (array) $this);
    }

    /**
     * @param array $object
     * @return Entity
     */
    public static function morph(array $object)
    {
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        foreach ($class->getProperties(\ReflectionProperty::IS_PROTECTED) as $prop) {
            unset($entity->{$prop->getName()});
        }
        unset($entity->db);
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if (isset($object[$prop->getName()])) {
                $prop->setValue($entity, $object[$prop->getName()]);
            }
        }

        return $entity;
    }

    /**
     * @param array|object $result
     *
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
    }

    /**
     * @param array $response
     * 
     * @return \ORM\Ripple\Collection
     */
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

    public static function paginate($limit, $pageType = "GET")
    {
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        $all = $entity->findAll();
        $pagination = new Paginator($limit, $pageType);
        if ($all->count() > 0) {
            $pagination->set_total($all->count());

            $offset = $pagination->getOffset();
            $result = $entity->db->select($entity->table, '*', [], $limit, $offset);
            $response = $entity->buildObject($result);
            $response->links = $pagination->links();
            return $response;
        } else {
            return $all;
        }
    }

    public static function simplePaginate($limit, $pageType = "GET")
    {
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        $all = $entity->findAll();
        $pagination = new Paginator($limit, $pageType, true);
        if ($all->count() > 0) {
            $pagination->set_total($all->count());

            $offset = $pagination->getOffset();
            $result = $entity->db->select($entity->table, '*', [], $limit, $offset);
            $response = $entity->buildObject($result);
            $response->links = $pagination->links();
            return $response;
        } else {
            return $all;
        }
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

    public function belongsTo($parentClass, $foreign_key)
    {
        $class = new \ReflectionClass($this);
        $classname = strtolower($class->getShortName());
        $foreign_key = is_null($foreign_key) ? $classname . '_id' : $foreign_key;
        return new ManyToOne($this, $parentClass, $foreign_key);
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

    /**
     * One-to-one relationship
     * @param string $relatedClass Related Class
     * @param string $foreignKey foreign key in the child table
     */
    public function hasOne($relatedClass, $foreignKey)
    {
        return new OneToOne($this->id, $relatedClass, $foreignKey);
    }
}
