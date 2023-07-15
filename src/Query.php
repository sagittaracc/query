<?php

namespace Sagittaracc;

use Closure;
use Sagittaracc\Container\Container;
use Sagittaracc\Value\Any;
use stdClass;

class Query
{
    protected $connection;
    protected $db;
    protected $query;
    protected $sql;
    protected $select = [];
    protected $indexClosure = null;
    protected $group;
    public $rawDumpQueries = [];

    public static function use($db)
    {
        $instance = new self;
        $instance->db = $db;
        $instance->connection = Container::getInstance()->get("connections.$db");
        return $instance;
    }

    public function prepare($sql)
    {
        $this->sql = $sql;
        $this->columns([]);
        $this->index(null);
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

    public function index(?Closure $closure, bool $group = false)
    {
        $this->indexClosure = $closure;
        $this->group = $group;
        return $this;
    }

    public function group(Closure $closure)
    {
        $this->index($closure, true);
        return $this;
    }

    public function all($params = [])
    {
        $this->query = $this->connection->prepare($this->sql);
        $this->query->execute($params);
        ob_start();
        $this->query->debugDumpParams();
        $this->rawDumpQueries[] = ob_get_clean();
        $data = $this->query->fetchAll(\PDO::FETCH_CLASS);
        $clone = clone $this;

        if ($clone->indexClosure instanceof Closure) {
            $indexed = [];
            foreach ($data as $index => $model) {
                $idx = $clone->indexClosure;
                $newIdx = $idx($model);
                if ($clone->group) {
                    if (!isset($indexed[$newIdx])) {
                        $indexed[$newIdx] = [];
                    }
                    $indexed[$newIdx][] = $model;
                }
                else {
                    $indexed[$newIdx] = $model;
                }
            }
            $data = $indexed;
        }

        foreach ($data as &$model) {
            foreach ($clone->select as $column => $option) {
                if ($option instanceof Closure) {
                    if ($model instanceof stdClass) {
                        $model->{$column} = $option($model, $this, $data);
                    }
                    else if (is_array($model)) {
                        $model[$column] = $option($model, $this, $data);
                    }
                    else {
                    }
                }
            }
        }
        unset($model);

        return $data;
    }

    public function one($params = [])
    {
        $data = $this->all($params);
        return count($data) === 1 ? reset($data): null;
    }
}