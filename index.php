<?php

use Sagittaracc\Container\Container;
use Sagittaracc\Query;

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
    $db->prepare(
        'SELECT
            *
        FROM `groups`
        WHERE
            `name` = :name'
    )
    ->columns([
        'users' => function($model, $db) {
            return
                $db
                    ->prepare('SELECT * FROM users where group_id = :id')
                    ->columns([
                        'age' => function($model) {
                            return "$model->age years old";
                        }
                    ])
                    ->all(['id' => $model->id]);
        }
    ]);

$users = $query->one(['name' => 'admin']);

// var_dump($query->rawDumpQueries);
print_r($users);