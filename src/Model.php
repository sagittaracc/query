<?php

namespace Sagittaracc;

use Exception;

class Model
{
    public function __get($name)
    {
        if (property_exists($this, "__$name")) {
            $fn = $this->{"__$name"};
            return $fn();
        }

        throw new Exception("Property $name not defined!");
    }
}