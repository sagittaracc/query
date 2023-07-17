<?php

namespace Sagittaracc;

class Model
{
    public function __get($name)
    {
        $fn = $this->{"__$name"};
        return $fn();
    }
}