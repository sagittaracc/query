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
    private $connection;
    /**
     * Название используемой базы данных
     */
    private string $db;
    /**
     * Выполняемый SQL запрос
     */
    private ?string $sql;
    /**
     * array<key => Closure>
     */
    private array $columns;
    /**
     * Тоже самое что и columns но с ленивой загрузкой
     * array<key => Closure>
     */
    private array $load;
    /**
     * ...
     */
    private $indexClosure;
    /**
     * Нужно группировать данные а не индексировать
     */
    private bool $grouping;
    private $modelClass;
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
        $this->as(Model::class);
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

    public function columns($columns)
    {
        $this->queue[] = '_columns';
        $this->columns = $columns;
        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function load($columns)
    {
        if (!in_array('_columns', $this->queue)) {
            $this->queue[] = '_columns';
        }

        $this->load = $columns;
        return $this;
    }

    public function getLoad()
    {
        return $this->load;
    }

    public function index(?Closure $closure, bool $grouping = false)
    {
        $this->queue[] = '_index';
        $this->indexClosure = $closure;
        $this->grouping = $grouping;
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
            $data = ArrayHelper::index($clone->indexClosure, $data, $clone->grouping);
        }

        return $data;
    }

    private function _columns($data)
    {
        $clone = clone $this;

        foreach ($data as &$model) {
            foreach ($clone->getColumns() as $column => $option) {
                if ($option instanceof Closure) {
                    $model->{$column} = $option($model, $this, $data);
                }
            }

            foreach ($clone->getLoad() as $column => $option) {
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
            $data = $query->fetchAll(\PDO::FETCH_CLASS, $this->getModelClass());
        }
        else if (!is_null($this->data)) {
            $data = ArrayHelper::serialize($this->data, $this->getModelClass());
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