<?php

use Sagittaracc\Container\Container;
use Sagittaracc\Query;
use Sagittaracc\Value\Any;

require 'vendor/autoload.php';

$container = Container::getInstance();
$container->configure([
    'connections' => [
        'my-db' => [
            'class' => PDO::class,
            'constructor' => ["mysql:dbname=go_db;host=127.0.0.1", 'root', ''],
        ]
    ]
]);

$db = Query::use('my-db');

$query =
    $db
    ->query('SELECT * FROM `groups` WHERE `name` = :name')
    ->columns([
        'users' => function($group, $db) {
            return
                $db
                ->query('SELECT * FROM users where group_id = :id')
                ->filter([
                    'age' => new Any
                ])
                ->index(fn($user) => $user->id)
                ->columns([
                    'underage' => fn($user) => $user->age < 18,
                    'canDrink' => fn($user) => $user->underage ? 'no' : 'yes',
                    'parent' => fn($user, $db, $data) => $data[$user->parent_id] ?? null,
                ])
                ->all(['id' => $group->id]);
        }
    ]);

$data = $query->one(['name' => 'admin']);

var_dump($query->rawDumpQueries);
print_r($data);