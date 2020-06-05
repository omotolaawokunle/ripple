<?php

namespace Ripple\Relationships;


use ArrayAccess;
use ArrayIterator;
use Countable;
use Exception;
use IteratorAggregate;
use Ripple\Paginator;

abstract class Relationship implements ArrayAccess, IteratorAggregate
{
    protected $items;

    public function save($class, array $params)
    {
        $child = $class->getChild();
        $parent = is_object($class->getParent()) ? $class->getParent()->id : $class->getParent();
        $entity = $child->newInstance();

        foreach ($child->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            foreach ($params as $key => $value) {
                if (!is_array($value)) {
                    if ($prop->getName() == $key) {
                        $entity->{$key} = $value;
                    }
                } else {
                    $entity = $child->newInstance();
                    foreach ($value as $k => $v) {
                        if ($prop->getName() == $k) {
                            $entity->{$k} = $v;
                        }
                    }
                    if ($entity->save()) {
                    } else {
                        print_r($entity);
                        throw new Exception($child->getShortName() . ' could not be saved', 1);
                    }
                }
            }
        }
        if (!is_array($params[0])) {
            $entity->{$class->foreignKey()} = $parent;

            if ($id = $entity->save()) {
                return $id;
            } else {
                print_r($entity);
                throw new Exception($child->getShortName() . ' could not be saved', 1);
            }
        }
        return true;
    }


    public function add($class, $id, array $params)
    {
        $parent = $class->getParent();
        $pivot = $class->pivot();
        $paramKeys = array_keys($params);
        $paramKeys[] = $class->parentKey();
        $paramKeys[] = $class->relatedKey();
        $params[$class->parentKey()] = $parent->id;
        $params[$class->relatedKey()] = $id;
        $db = new \Ripple\Database();
        $condition = [$class->parentKey() => ['=', $parent->id, '']];
        if ($this->checkExists($pivot, $condition)) {
            return $db->update($pivot, array_keys($params), $params, $condition);
        } else {
            return $db->insert($pivot, $paramKeys, $params);
        }
    }

    public function paginate($limit, $pageType = "GET")
    {
        $pagination = new Paginator($limit, $pageType);
        if ($this->count() > 0) {
            $pagination->set_total($this->count());

            $offset = $pagination->getOffset();
            $response = (object) array_slice($this->items, $offset, $limit);
            $response->links = $pagination->links();
            return $response;
        } else {
            return $this;
        }
    }

    private function checkExists($pivot, array $conditions)
    {
        $db = new \Ripple\Database();
        $string = $db->buildQueryString($pivot, 'select', $conditions);
        $query = $db->db()->query($string);
        return (bool) !empty($query->fetch_assoc());
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

    public function take(int $number)
    {
        $this->items = array_slice($this->items, 0, $number);
        return $this;
    }

    public function sort($element, $type)
    {
        switch ($type) {
            case 'date_compare':
                usort($this->items, function ($elem1, $elem2) use ($element) {
                    $date1 = strtotime($elem1->{$element});
                    $date2 = strtotime($elem2->{$element});
                    return $date2 - $date1;
                });
                break;

            default:
                usort($this->items, function ($elem1, $elem2) use ($element) {
                    return $elem2 - $elem1;
                });
                break;
        }
        return $this;
    }

    public function find($value, $column)
    {
        $key = array_search($value, array_column($this->items, $column));
        return $this->items[$key];
    }

    /**
     * 
     * @return $this->items
     */
    public function first()
    {
        return isset($this->items[0]) ? $this->items[0] : false;
    }

    /**
     * 
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * 
     * @return \ORM\Ripple\Collection
     */
    public function search($value)
    {
        $result = [];
        $value = preg_quote($value, '~');
        $data = collect($this->items)->flatten();
        foreach ($this->items as $key => $item) {
            $data = collect((array) $item)->flatten();
            $res = preg_grep('~' . $value . '~', $data->all());
            if (!empty($res)) {
                $result[] = $this->items[$key];
            }
        }

        return collect($result);
    }
}
