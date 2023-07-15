<?php

namespace Sagittaracc;

use Closure;
use Sagittaracc\Container\Container;
use Sagittaracc\Value\Any;

class Query
{
    protected $db;
    protected $query;
    protected $sql;
    protected $select = [];
    public $rawDumpQueries = [];

    public static function use($db)
    {
        $instance = new self;
        $instance->db = $db;
        return $instance;
    }

    public function prepare($sql)
    {
        $this->sql = $sql;
        $this->columns([]);
        return $this;
    }

    public function filter($filter)
    {
        foreach ($filter as $param => $value) {
            if ($value instanceof Any) continue;
        }
        return $this;
    }

    public function columns($select)
    {
        $this->select = $select;
        return $this;
    }

    public function all($params = [])
    {
        $this->query = Container::getInstance()->get("connections.$this->db")->prepare($this->sql);
        $this->query->execute($params);
        ob_start();
        $this->query->debugDumpParams();
        $this->rawDumpQueries[] = ob_get_clean();
        $data = $this->query->fetchAll(\PDO::FETCH_CLASS);
        // Может лучше сделать $clone = clone $this?
        $select = $this->select;

        foreach ($data as $model) {
            foreach ($select as $column => $option) {
                if ($option instanceof Closure) {
                    $model->{$column} = $option($model, $this);
                }
            }
        }

        return $data;
    }

    public function one($params = [])
    {
        $data = $this->all($params);
        return count($data) === 1 ? reset($data): null;
    }
}