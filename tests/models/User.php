<?php

namespace Sagittaracc\models;

use Sagittaracc\Model;

class User extends Model
{
    public function hasDebt()
    {
        return $this->Balance < 0;
    }
}