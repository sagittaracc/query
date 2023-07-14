<?php

use Sagittaracc\AnyValue;
use Sagittaracc\Container;
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
        FROM `users`
        WHERE
            `name` = :name
        AND `age` = :age'
    )
    ->columns([
        'age' => function($model) {
            return $model->age . ' ' . ($model->age === 1 ? 'year old' : 'years old');
        },
        'parent' => function($model, $db) {
            $query = $db->prepare('SELECT * FROM users WHERE id = :id');
            $parent = $query->one(['id' => $model->parent_id]);
            return $parent?->name;
        }
    ]);

$users = $query->all(['name' => 'Alex', 'age' => new AnyValue]);

var_dump($query->rawDumpQueries);
var_dump($users);