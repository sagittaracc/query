<?php

namespace Sagittaracc;

use Closure;
use sagittaracc\ArrayHelper;
use Sagittaracc\Container\Container;
use Sagittaracc\Value\Any;

class Query
{
    /**
     * Экземпляр подключения к базе данных
     */
    protected $connection;
    /**
     * Название используемой базы данных
     */
    protected string $db;
    /**
     * Выполняемый SQL запрос
     */
    protected ?string $sql;
    /**
     * 
     */
    protected $select;
    protected $lazy;
    protected $indexClosure;
    protected $group;
    protected $modelClass;

    private $queue;

    public $rawDumpQueries;
    public $data;

    public static function use($db = null)
    {
        $instance = new self;
        $instance->flush();

        if ($db !== null) {
            $instance->db = $db;
            $instance->connection = Container::getInstance()->get("connections.$db");
        }

        return $instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getDb()
    {
        return $this->db;
    }

    private function flush()
    {
        $this->sql = null;
        $this->columns([]);
        $this->load([]);
        $this->index(null);
        $this->queue = [];
        $this->data = null;
        $this->modelClass = Model::class;
    }

    public function data($data)
    {
        $this->flush();
        $this->data = $data;
        return $this;
    }

    public function query($sql)
    {
        $this->flush();
        $this->sql = $sql;
        return $this;
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function as($modelClass)
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    public function getModelClass()
    {
        return $this->modelClass;
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

    public function load($select)
    {
        if (!in_array('_columns', $this->queue)) {
            $this->queue[] = '_columns';
        }

        $this->lazy = $select;
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
                }
            }

            foreach ($clone->lazy as $column => $option) {
                if ($option instanceof Closure) {
                    $model->{"__$column"} = fn() => $option($model, $this, $data);
                }
            }
        }
        unset($model);

        return $data;
    }

    public function all($params = [])
    {
        if (!is_null($this->getSql())) {
            $query = $this->getConnection()->prepare($this->getSql());
            $query->execute($params);
            ob_start();
            $query->debugDumpParams();
            $this->rawDumpQueries[] = ob_get_clean();
            $data = $query->fetchAll(\PDO::FETCH_CLASS, $this->modelClass);
        }
        else if (!is_null($this->data)) {
            $data = ArrayHelper::serialize($this->data, $this->modelClass);
        }
        else {
            $data = [];
        }

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