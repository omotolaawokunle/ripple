<?php

namespace Ripple;


use ArrayAccess;
use ReflectionProperty;
use Ripple\Collection;
use Ripple\Database;
use Ripple\QueryBuilder\DB;
use Ripple\Traits\HasRelationship;

abstract class Entity
{
    use HasRelationship;

    protected $table;
    protected static $traitInitializers = [];
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    protected $db;
    const DELETED_AT = 'deleted_at';
    const CREATED_AT = 'created_at';


    public function __construct()
    {
        if (is_null($this->db)) {
            $this->db = new Database();
        }
        $this->loadClassProperties();
        $this->boot();
        $this->initializeTraits();
    }

    protected function boot()
    {
        static::bootTraits();
    }

    protected function newQueryBuilder()
    {
        return new DB($this->table);
    }

    protected static function bootTraits()
    {
        $class = static::class;
        $booted = [];

        static::$traitInitializers[$class] = [];
        foreach (class_uses($class) as $trait) {
            $method = 'boot' . class_basename($trait);

            if (method_exists($class, $method) && !in_array($method, $booted)) {
                forward_static_call([$class, $method]);
                $booted[] = $method;
            }

            if (method_exists($class, $method = 'initialize' . class_basename($trait))) {
                static::$traitInitializers[$class][] = $method;

                static::$traitInitializers[$class] = array_unique(
                    static::$traitInitializers[$class]
                );
            }
        }
    }

    protected function initializeTraits()
    {
        foreach (static::$traitInitializers[static::class] as $method) {
            $this->{$method}();
        }
    }

    /**
     * 
     * @return \ORM\Ripple\Collection
     */
    public function findAll()
    {
        $result = $this->db->select($this->table, '*', []);
        $result = $this->traitsFunctions($result);
        return $this->buildObject($result);
    }

    /**
     * 
     * @return \ORM\Ripple\Collection
     */
    public static function all()
    {
        $entity = new static;
        //$result = $entity->db->select($entity->table, '*', []);
        $result = $entity->newQueryBuilder()->select()->get();
        //$result = $entity->traitsFunctions($result);
        $result = $entity->buildArray($result);

        return $result;
    }

    private function buildArray($result)
    {
        $response = [];
        if ($result) {
            $fields = !empty($result) ? array_keys($result[0]) : [];
            $values = $result;
            $num_of_rows = count($values);
            $num_of_fields = count($fields);
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
     * @param int $id
     * @return $this
     */
    public function findById($id)
    {
        $result = $this->db->select($this->table, '*', ['id' => ['=', $id, '']]);
        $result = $this->traitsFunctions($result);
        $result = $this->buildObject($result);
        if ($result->first()) {
            return $result->first();
        }
        return false;
    }

    protected function traitsFunctions($result)
    {
        return $result;
    }

    public function removeAttribute($key)
    {
        unset($this->$key);
    }

    public function getKeyName()
    {
        return $this->primaryKey;
    }

    public function setKeyName($key)
    {
        $this->primaryKey = $key;

        return $this;
    }

    public function getKeyType()
    {
        return $this->keyType;
    }

    public function getForeignKey()
    {
        return class_basename($this) . '_' . $this->getKeyName();
    }

    public function setKeyType($type)
    {
        $this->keyType = $type;

        return $this;
    }


    /**
     * @param array $conditions
     * @return Collection $result
     */
    public function find(array $conditions)
    {
        foreach ($conditions as $key => $value) {
            $conditions[$key] = ['=', $value, ''];
        }

        $result = $this->db->select($this->table, '*', $conditions);
        $result = $this->traitsFunctions($result);
        $result = $this->buildObject($result);
        if ($result->first()) {
            return $result;
        }
        return false;
    }

    public function delete()
    {
        $this->performDelete();
        return true;
    }

    public function forceDelete()
    {
        return $this->delete();
    }


    protected function performDelete()
    {
        return $this->db->delete($this->table, ['id' => ['=', $this->id], '']);
    }

    public static function destroy($ids)
    {
        $count = 0;

        if ($ids instanceof Collection) {
            $ids = $ids->all();
        }

        $ids = is_array($ids) ? $ids : func_get_args();
        $instance = new static;
        foreach ($ids as $id) {
            $model = $instance->findById($id);
            if ($model->delete()) {
                $count++;
            }
        }

        return $count;
    }


    /**
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * 
     * @return Collection
     */

    public static function where($field, $operator = '=', $value)
    {
        $class = new \ReflectionClass(get_called_class());
        $entity = $class->newInstance();
        $conditions = [];
        $conditions[$field] = [$operator, $value, ''];

        $result = $entity->db->select($entity->table, '*', $conditions);
        $result = ($instance = new static)->traitsFunctions($result);
        $result = $instance->buildObject($result);
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
        $result = ($instance = new static)->traitsFunctions($result);
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
        $class = get_called_class();
        $morpher = new Morpher;
        $entity = $morpher($class, $object);
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
        $all = static::all();
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
        $all = static::all();
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


    private function checkTraits($trait)
    {
        $traits = class_uses(static::class);
        $traits = collect($traits)->map(function ($value) {
            $value = new \ReflectionClass($value);
            return $value->getShortName();
        });

        return $traits->contains($trait);
    }
}
