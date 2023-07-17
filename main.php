<?php

use Sagittaracc\Container\Container;
use Sagittaracc\Query;

require 'vendor/autoload.php';

$container = Container::getInstance();
$container->configure([
    'connections' => [
        'my-db' => [
            'class' => PDO::class,
            'constructor' => ["mysql:dbname=resurs;host=127.0.0.1", 'root', ''],
        ]
    ]
]);

$db = Query::use('my-db');

$query =
    $db
    ->query('SELECT * FROM `counter`')
    ->columns([
        'Name' => function($counter) {
            return !empty($counter->Name) ? $counter->Name : 'not defined';
        }
    ])
    ->load([
        'user' => function($counter, $db) {
            return
                $db
                ->query('SELECT * FROM `users` WHERE `Obj_Id_User` = :id')
                ->one(['id' => $counter->Obj_Id_User]);
        }
    ]);

$data = $query->all();

print_r($data[0]->user);