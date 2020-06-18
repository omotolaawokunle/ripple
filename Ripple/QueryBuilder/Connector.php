<?php

namespace Ripple\QueryBuilder;

use Exception;

class Connector
{
    protected $config;
    protected $db = null;

    /**
     * 
     * @return \PDO
     */
    public function __construct()
    {
        $this->db = $this->retrieveConfig()->connect();
    }

    private function retrieveConfig()
    {
        if (file_exists(dirname(__DIR__) . '/config.php')) {
            $fileConfig = include(dirname(__DIR__) . '/config.php');
            $env = $fileConfig['env'];
            if (array_key_exists($env, $fileConfig)) {
                $this->config = $fileConfig[$env];
                return $this;
            }
            throw new Exception("Environment must be either development or production");
        }
        throw new Exception("Config file not found in Ripple directory");
    }

    private function connect()
    {
        $config = $this->config;
        return new \PDO("mysql:host=" . $config['host'] . ";dbname=" . $config['database'] . ";charset=utf8", $config['username'], $config['password']);
    }

    public function getConnection()
    {
        return $this->db;
    }
}
