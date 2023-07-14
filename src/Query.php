<?php

namespace Sagittaracc;

use Closure;

class Query
{
    protected $db;
    protected $query;
    protected $sql;
    protected $select;
    public $rawQuery;

    public static function use($db)
    {
        $instance = new self;
        $instance->db = $db;
        return $instance;
    }

    public function prepare($sql)
    {
        $this->sql = $sql;
        return $this;
    }

    public function columns($select)
    {
        $this->select = $select;
        return $this;
    }

    public function execute($params = [])
    {
        foreach ($params as $param => $value) {
            if ($value instanceof AnyValue) {
                $this->sql = preg_replace('/\w+\s*=\s*:'.$param.'/', true, $this->sql);
                unset($params[$param]);
            }
        }

        $this->query = Container::getInstance()->getConnection($this->db)->prepare($this->sql);
        $this->query->execute($params);
        $this->rawQuery = $this->query->debugDumpParams();
        $data = $this->query->fetchAll(\PDO::FETCH_CLASS);

        foreach ($data as $model) {
            foreach ($this->select as $column => $option) {
                if ($option instanceof Closure) {
                    $model->{$column} = $option($model);
                }
            }
        }

        return $data;
    }
}