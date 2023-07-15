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
    ->prepare('SELECT * FROM `groups` WHERE `name` = :name')
    ->columns([
        'users' => function($group, $db) {
            return
                $db
                ->prepare('SELECT * FROM users where group_id = :id')
                ->filter([
                    'age' => new Any
                ])
                ->index(function($user) {
                    return $user->id;
                })
                ->columns([
                    'underage' => function($user) {
                        return $user->age < 18;
                    },
                    'canDrink' => function($user) {
                        return $user->underage ? 'no' : 'yes';
                    },
                    'parent' => function($user, $db, $data) {
                        return $data[$user->parent_id] ?? null;
                    },
                ])
                ->all(['id' => $group->id]);
        }
    ]);

$data = $query->one(['name' => 'admin']);

var_dump($query->rawDumpQueries);
print_r($data);