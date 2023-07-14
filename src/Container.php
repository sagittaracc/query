<?php

namespace Sagittaracc;

class Container
{
    protected static $instance;
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

    public function configure($config)
    {
        $this->config = $config;
    }

    public function get($path)
    {
        $config = $this->config;

        $path = explode('.', $path);
        foreach ($path as $key) {
            $config = $config[$key];
        }

        return new $config['class'](...$config['constructor']);
    }
}