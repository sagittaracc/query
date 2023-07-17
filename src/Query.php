<?php

namespace Sagittaracc;

use Closure;
use sagittaracc\ArrayHelper;
use Sagittaracc\Container\Container;
use Sagittaracc\Value\Any;

class Query
{
    protected $connection;
    protected $db;
    protected $query;
    protected $sql;
    protected $select;
    protected $indexClosure;
    protected $group;

    private $queue;

    public $rawDumpQueries;

    public static function use($db)
    {
        $instance = new self;
        $instance->flush();
        $instance->db = $db;
        $instance->connection = Container::getInstance()->get("connections.$db");
        return $instance;
    }

    public function flush()
    {
        $this->columns([]);
        $this->index(null);
        $this->rawDumpQueries = [];
        $this->queue = [];
    }

    public function query($sql)
    {
        $this->sql = $sql;
        $this->flush();
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
        $this->queue[] = '_columns';
        $this->select = $select;
        return $this;
    }

    public function index(?Closure $closure, bool $group = false)
    {
        $this->queue[] = '_index';
        $this->indexClosure = $closure;
        $this->group = $group;
        return $this;
    }

    public function group(Closure $closure)
    {
        $this->index($closure, true);
        return $this;
    }

    private function _index($data)
    {
        $clone = clone $this;

        if ($clone->indexClosure instanceof Closure) {
            $data = ArrayHelper::index($clone->indexClosure, $data, $clone->group);
        }

        return $data;
    }

    private function _columns($data)
    {
        $clone = clone $this;

        foreach ($data as &$model) {
            foreach ($clone->select as $column => $option) {
                if ($option instanceof Closure) {
                    $model->{$column} = $option($model, $this, $data);
                    // Lazy loading
                    // $model->{"__$column"} = fn() => $option($model, $this, $data);
                }
            }
        }
        unset($model);

        return $data;
    }

    private function dumpQueries()
    {
        ob_start();
        $this->query->debugDumpParams();
        $this->rawDumpQueries[] = ob_get_clean();
    }

    public function all($params = [])
    {
        $this->query = $this->connection->prepare($this->sql);
        $this->query->execute($params);

        $this->dumpQueries();

        $data = $this->query->fetchAll(\PDO::FETCH_CLASS, Model::class);

        // Методы index, column, ... выполняем в порядке их установки в запросе
        foreach ($this->queue as $method) {
            if (method_exists($this, $method)) {
                $data = $this->$method($data);
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