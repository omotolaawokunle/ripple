<?php

namespace Ripple;


use Exception;

class Database
{
    private $db, $dbClass;

    public function __construct()
    {
        $this->db = new \mysqli('localhost', 'root', 'base', 'test');
        if ($this->db->connect_error) {
            $this->error('Failed to connect to database - ' . $this->db->connect_error);
        }
    }

    public function disconnect()
    {
        if (isset($this->db)) {
            $this->db->close();
        }
    }

    public function db()
    {
        return $this->db;
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $values
     * @return mixed
     */
    public function insert($table, $columns, $values)
    {
        foreach ($values as $k => $v) {
            if ($v == '') {
                unset($values[$k]);
            }
        }

        foreach ($columns as $key => $value) {

            if (!isset($values[$value])) {
                unset($columns[$key]);
            }
        }

        $vals = [];

        foreach ($columns as $column) {
            if (isset($values[$column])) {
                $vals[] = "'" . $values[$column] . "'";
            }
        }
        $columns = implode(',', $columns);
        $values = implode(',', $vals);

        $query = "INSERT INTO `$table` ($columns) VALUES ($values)";
        $result =  $this->db->query($query);
        return $this->db->insert_id ? $this->db->insert_id : $this->error($this->db->error);
    }

    /**
     * @param string $table
     * @param array $conditions structure -> column => (operator, value, logical_operator) e.g id => (>, 5, AND)
     * @param array $columns
     * @param array $values
     * @return mixed
     */
    public function update($table, $columns, $values, $conditions)
    {
        $update = $this->generateUpdateString($columns, $values);
        $whereConditions = $this->generateWhereString($conditions);
        $query = "UPDATE `$table` SET $update WHERE $whereConditions";
        $result = $this->db->query($query);
        if ($this->db->error) {
            $this->error($this->db->error);
        }
        return $result;
    }

    /**
     * @param string $table
     * @param string $columns
     * @param array $conditions
     * @param int $limit
     * @param int $offset
     * @return mixed
     */
    public function select($table, $columns, $conditions, $limit = null, $offset = null)
    {
        $query = "SELECT $columns FROM $table";
        if (!empty($conditions)) {
            $where = $this->generateWhereString($conditions);
            $query .= " WHERE $where";
        }
        if (isset($limit) && isset($offset)) {
            $query .= " LIMIT $limit OFFSET $offset";
        }
        $result = $this->db->query($query);
        $response = [];
        if ($result) {
            $response['fields'] = $this->fetchFields($result);
            $response['values'] = $result->fetch_all(MYSQLI_ASSOC);
        }
        return $response;
    }

    /**
     * @param string $table
     * @param array $conditions
     * @return mixed
     */
    public function delete($table, $conditions)
    {
        $where = $this->generateWhereString($conditions);
        $query = "DELETE FROM $table WHERE $where";
        return $query;
    }


    /**
     * @param array $keys
     * @param array $values
     * @return string
     */
    private function generateUpdateString($keys, $values)
    {

        foreach ($keys as $key => $value) {
            if (!isset($values[$value]) || is_null($values[$value])) {
                unset($keys[$key]);
                unset($values[$value]);
            }
        }
        $buildString =  '';
        $len = count($keys);
        $i = 0;
        foreach ($keys as $key) {
            if ($i !== $len - 1) {
                $buildString .= $key . "='" . $values[$key] . "',";
            } else {
                $buildString .= $key . "='" . $values[$key] . "'";
            }
            $i++;
        }

        return $buildString;
    }

    public function buildQueryString($table, $type, array $conditions = [])
    {
        $where = $this->generateWhereString($conditions) !== '' ? " WHERE " . $this->generateWhereString($conditions) : '';
        switch ($type) {
            case 'select':
                $queryString = "SELECT * FROM `$table` $where";
                break;

            default:
                $queryString = "SELECT * FROM `$table` $where";
                break;
        }
        return $queryString;
    }

    /**
     * @param array $arrayValues
     * @return string
     */
    private function generateWhereString(array $arrayValues)
    {
        $buildString = '';
        $len = count($arrayValues);
        $i = 1;
        foreach ($arrayValues as $key => $value) {
            if ($i == $len) {
                $buildString .= $key . $value[0] . "'" . $value[1] . "'";
            } else {
                $buildString .= $key . $value[0] . "'" . $value[1] . "'" . " " . $value[2];
            }
            $i++;
        }
        return $buildString;
    }
    /**
     * @return int 
     */
    public function count()
    {
        $count = isset($this->db->affected_rows) ? $this->db->affected_rows : 0;
        return $count;
    }

    /**
     * @param string $queryResult
     * @return array
     */
    public function fetchFields($queryResult)
    {
        if ($queryResult) {
            if (is_string($queryResult)) {
                $queryResult = $this->db->query($queryResult);
            }
            $fieldsData = $queryResult->fetch_fields();
            $fields = [];
            foreach ($fieldsData as $field) {
                $fields[] = $field->name;
            }
            return $fields;
        }
        return [];
    }

    public function error($error)
    {
        return new Exception($error, 1);
    }
}
