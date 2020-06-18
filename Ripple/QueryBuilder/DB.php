<?php

namespace Ripple\QueryBuilder;

use ClanCats\Hydrahon\Builder;
use ClanCats\Hydrahon\Query\Sql\FetchableInterface;
use ClanCats\Hydrahon\Query\Expression;
use Ripple\QueryBuilder\Connector;

class DB
{
    protected $db, $table, $builder;

    public function __construct($table = null)
    {
        $this->db = (new Connector())->getConnection();
        $this->builder = new Builder('mysql', function ($query, $queryString, $queryParameters) {
            $statement = $this->db->prepare($queryString);
            $statement->execute($queryParameters);

            if ($query instanceof FetchableInterface) {
                return $statement->fetchAll(\PDO::FETCH_ASSOC);
            }
        });
        $this->builder = $this->builder->table($table);
    }

    public function insert(array $values = [])
    {
        return $this->builder->insert($values);
        return $this;
    }

    public function values(array $values)
    {
        return $this->builder->values($values);
        return $this;
    }

    public function resetValues()
    {
        return $this->builder->resetValues();
        return $this;
    }

    public function ignore($ignore = true)
    {
        return $this->builder->ignore($ignore);
    }

    public function update(array $values = [])
    {
        return $this->builder->update();
        return $this;
    }

    public function set($param1, $param2 = null)
    {
        return $this->builder->set($param1, $param2);
        return $this;
    }

    public function where($column, $param1 = null, $param2 = null, $type = 'and')
    {
        return $this->builder->where($column, $param1, $param2, $type);
        return $this;
    }

    public function orWhere($column, $param1 = null, $param2 = null)
    {
        return $this->builder->orWhere($column, $param1, $param2);
        return $this;
    }

    public function andWhere($column, $param1 = null, $param2 = null)
    {
        return $this->builder->orWhere($column, $param1, $param2);
        return $this;
    }

    public function whereIn($column, array $value = [])
    {
        return $this->builder->whereIn($column, $value);
        return $this;
    }

    public function whereNotIn($column, array $value = [])
    {
        return $this->builder->whereNotIn($column, $value);
        return $this;
    }

    public function whereNull($column)
    {
        return $this->builder->whereNull($column);
        return $this;
    }

    public function whereNotNull($column)
    {
        return $this->builder->whereNotNull($column);
        return $this;
    }

    public function orWhereNull($column)
    {
        return $this->builder->orWhereNull($column);
        return $this;
    }

    public function orWhereNotNull($column)
    {
        return $this->builder->orWhereNotNull($column);
        return $this;
    }

    public function resetWheres()
    {
        return $this->builder->resetWheres();
        return $this;
    }

    public function groupBy($groupKeys)
    {
        return $this->builder->groupBy($groupKeys);
        return $this;
    }

    public function orderBy($columns, $direction = 'asc')
    {
        return $this->builder->orderBy($columns, $direction);
        return $this;
    }

    public function limit($limit, $limit2 = null)
    {
        return $this->builder->limit($limit, $limit2);
        return $this;
    }

    public function offset($offset)
    {
        return $this->builder->offset($offset);
        return $this;
    }

    public function page($page, $size = 25)
    {
        return $this->builder->page($page, $size);
        return $this;
    }

    public function distinct($distinct = true)
    {
        return $this->builder->distinct($distinct);
        return $this;
    }

    public function one()
    {
        return $this->builder->one();
        return $this;
    }

    public function first($key = 'id')
    {
        return $this->builder->first($key);
        return $this;
    }

    public function last($key = 'id')
    {
        return $this->builder->last($key);
        return $this;
    }

    public function find($id, $key = 'id')
    {
        return $this->builder->find($id, $key);
        return $this;
    }

    public function column($column)
    {
        return $this->builder->column($column);
        return $this;
    }

    public function count($field = null)
    {
        return $this->builder->count($field);
        return $this;
    }

    public function sum($field)
    {
        return $this->builder->sum($field);
        return $this;
    }

    public function min($field)
    {
        return $this->builder->min($field);
        return $this;
    }

    public function max($field)
    {
        return $this->builder->max($field);
        return $this;
    }

    public function avg($field)
    {
        return $this->builder->avg($field);
        return $this;
    }

    public function exists()
    {
        return $this->builder->exists();
        return $this;
    }

    public function join($table, $localKey, $operator = null, $referenceKey = null, $type = 'left')
    {
        return $this->builder->join($table, $localKey, $operator = null, $referenceKey = null, $type = 'left');
        return $this;
    }

    public function rightJoin($table, $localKey, $operator = null, $referenceKey = null)
    {
        return $this->builder->join($table, $localKey, $operator = null, $referenceKey = null, $type = 'right');
        return $this;
    }

    public function innerJoin($table, $localKey, $operator = null, $referenceKey = null)
    {
        return $this->builder->join($table, $localKey, $operator = null, $referenceKey = null, $type = 'inner');
        return $this;
    }

    public function outerJoin($table, $localKey, $operator = null, $referenceKey = null)
    {
        return $this->builder->join($table, $localKey, $operator = null, $referenceKey = null, $type = 'outer');
        return $this;
    }

    public function delete()
    {
        return $this->builder->delete();
        return $this;
    }

    public function select($column = null)
    {
        return $this->builder->select($column);
        return $this;
    }

    public function addField($field)
    {
        return $this->builder->addField($field);
    }

    public function get()
    {
        return $this->builder->get();
    }

    public function execute()
    {
        return $this->builder->execute();
    }


    public function table($table)
    {
        return $this->builder->table($table);
        return $this;
    }

    public function getRawBuilder()
    {
        return $this->builder;
    }
}
