<?php

namespace Sagittaracc;

use stdClass;

class Serializer
{
    public function serialize($array, $objectClass)
    {
        $list = [];

        foreach ($array as $item) {
            $object = new $objectClass;

            foreach ($item as $key => $value) {
                $object->$key = $value;
            }

            $list[] = $object;
        }

        return $list;
    }
}