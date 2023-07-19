<?php

namespace Sagittaracc;

use Closure;
use sagittaracc\ArrayHelper;
use Sagittaracc\Container\Container;
use Sagittaracc\Value\Any;

/**
 * Fuck ORM
 * @author Yuriy Arutyunyan <sagittaracc@gmail.com>
 */
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
     * @var string класс в который в итоге будет сериализоваться объекты result set
     */
    private string $classObject;
    /**
     * @var array
     */
    private array $modifiers;
    /**
     * @var array лог выполненных запросов
     */
    public array $log;
    /**
     * @var mixed
     */
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
        $this->modifiers = [];
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

    public function as($classObject)
    {
        $this->classObject = $classObject;
        return $this;
    }

    public function getClassObject()
    {
        return $this->classObject;
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
        $this->modifiers[] = function($data) use ($columns) {
            foreach ($data as &$model) {
                foreach ($columns as $column => $option) {
                    if ($option instanceof Closure) {
                        $model->{$column} = $option($model, $this, $data);
                    }
                }
            }
            unset($model);
    
            return $data;
        };

        return $this;
    }

    public function load($columns)
    {
        $this->modifiers[] = function($data) use ($columns) {
            foreach ($data as &$model) {
                foreach ($columns as $column => $option) {
                    if ($option instanceof Closure) {
                        $model->{"__$column"} = fn() => $option($model, $this, $data);
                    }
                }
            }
            unset($model);
    
            return $data;
        };

        return $this;
    }

    public function index(?Closure $closure, bool $grouping = false)
    {
        $this->modifiers[] = function ($data) use ($closure, $grouping) {
            if ($closure instanceof Closure) {
                $data = ArrayHelper::index($closure, $data, $grouping);
            }
    
            return $data;
        };

        return $this;
    }

    public function group(Closure $closure)
    {
        $this->index($closure, true);
        return $this;
    }

    private function debug($query)
    {
        ob_start();
        $query->debugDumpParams();
        $this->log[] = ob_get_clean();
    }

    public function all($params = [])
    {
        if (!is_null($this->getSql())) {
            $query = $this->getConnection()->prepare($this->getSql());
            $query->execute($params);
            $data = $query->fetchAll(\PDO::FETCH_CLASS, $this->getClassObject());
            $this->debug($query);
        }
        else if (!is_null($this->data)) {
            $data = ArrayHelper::serialize($this->data, $this->getClassObject());
        }
        else {
            $data = [];
        }

        foreach ($this->modifiers as $method) {
            $data = $method($data);
        }

        return $data;
    }

    public function one($params = [])
    {
        $data = $this->all($params);
        return count($data) === 1 ? reset($data): null;
    }
}