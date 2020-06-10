<?php

namespace Ripple;


use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

class Collection implements Countable, ArrayAccess, IteratorAggregate, JsonSerializable
{
    protected $items;

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    public function count()
    {
        return count($this->items);
    }

    public function all()
    {
        return $this->items;
    }

    public function take(int $number)
    {
        return array_slice($this->items, 0, $number);
    }

    public function paginate($limit, $pageType = "GET")
    {
        $pagination = new Paginator($limit, $pageType);
        if ($this->count() > 0) {
            $pagination->set_total($this->count());

            $offset = $pagination->getOffset();
            $response = new Collection(array_slice($this->items, $offset, $limit));
            $response->links = $pagination->links();
            return $response;
        } else {
            return $this;
        }
    }

    /**
     * Remove an item from the collection by key.
     *
     * @param  string|array  $keys
     * @return $this
     */
    public function forget($keys)
    {
        foreach ((array) $keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }

        return $default;
    }


    public function avg()
    {
        if ($count = $this->count()) {
            return $this->sum() / $count;
        }
    }

    public function sum()
    {
        return array_sum($this->items);
    }

    public function first()
    {
        return isset($this->items[0]) ? $this->items[0] : false;
    }

    /**
     * @param $depth
     * 
     * @return static
     */
    public function flatten($depth = INF)
    {
        return new static(Helper::flatten($this->items, $depth));
    }

    public function shuffle()
    {
        shuffle($this->items);
        return $this;
    }

    public function min()
    {
        return min($this->items);
    }

    public function max()
    {
        return max($this->items);
    }



    /**
     * @param mixed $value
     * @return static
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

        return new static($result);
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

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($this->isJson($value)) {
                return json_decode($value->toJson(), true);
            } elseif ($this->isArray($value)) {
                return $value->toArray();
            }

            return $value;
        }, $this->items);
    }

    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function isArray($array)
    {
        return is_array($array);
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $this->isArray($value) ? $value->toArray() : $value;
        }, $this->items);
    }

    /**
     * Run a map over each of the items.
     *
     * @param  callable  $callback
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);

        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items));
    }

    /**
     * 
     * @param string $key
     * 
     * @return $this
     */

    public function sort($key)
    {
        uasort($this->items, function ($a, $b) use (&$key) {
            return $a[$key] <=> $b[$key];
        });

        return $this;
    }
}
