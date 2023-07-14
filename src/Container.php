<?php

namespace Sagittaracc;

class Container
{
    protected static $instance;
    protected $connections;
    protected $config;

    public function __construct()
    {
    }

    public function __clone()
    {
    }

    public function __wakeup()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function addConnection($name)
    {
        $config = $this->config['connections'][$name];
        $this->connections["{$config['driver']}:{$config['host']}:{$config['name']}"]
            = new \PDO(
                "{$config['driver']}:dbname={$config['name']};host={$config['host']}",
                $config['user'],
                $config['pass']
            );
    }

    public function getConnection($name)
    {
        $config = $this->config['connections'][$name];
        return $this->connections["{$config['driver']}:{$config['host']}:{$config['name']}"];
    }

    public function configure($config)
    {
        $this->config = $config;
        foreach ($config['connections'] as $mainConnection => $connections) break;
        $this->addConnection($mainConnection);
    }
}