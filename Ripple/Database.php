<?php

namespace Ripple;

require_once('./autoload.php');

class Database
{
    private $db;
    private $host, $dbName, $password, $user;

    public function __construct()
    {
        $this->host = 'localhost';
        $this->dbName = 'blog';
        $this->password = 'base';
        $this->user = 'root';


        $this->db = new \mysqli($this->host, $this->user, $this->password, $this->dbName);
        if ($this->db->connect_error) {
            return false;
        }
        return true;
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

        foreach ($columns as $key => $value) {
            if ($value == 'id' || $value == 'created_at') {
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
        return $result;
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
        $len = count($keys);
        $buildString =  '';
        for ($i = 0; $i < $len - 1; $i++) {
            $buildString .= $keys[$i] . '=' . $values[$i] . ',';
        }
        $buildString .= $keys[$len - 1] . '=' . $values[$len - 1];
        return $buildString;
    }

    public function buildQueryString($table, $type, array $conditions = [])
    {
        $where = $this->generateWhereString($conditions);
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
        foreach ($arrayValues as $key => $value) {
            $buildString .= $key . $value[0] . $value[1] . " " . $value[2];
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

    /**
     * @param $parentTable
     * @param $parentId
     * @param $childTable
     * @param $foreignKey
     * @param $childFields
     */
    public function hasMany($parentTable, $parentId, $childTable, $foreignKey, $childFields)
    {
        $asString = [];
        foreach ($childFields as $field) {
            $key = "c." . $field->getName();
            $value = $field->getName();
            $asString[] = $key . ' AS ' . $value;
        }
        $asString = implode(', ', $asString);
        $query = "SELECT $asString FROM $parentTable p INNER JOIN $childTable c ON c.$foreignKey = '$parentId'";
        $result = $this->db->query($query);
        $response = [];
        if ($result) {
            $response['fields'] = $this->fetchFields($result);
            $response['values'] = $result->fetch_all(MYSQLI_ASSOC);
        }
        return $response;
    }
}
