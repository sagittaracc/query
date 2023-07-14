<?php

use Sagittaracc\AnyValue;
use Sagittaracc\Container;
use Sagittaracc\Query;

require 'vendor/autoload.php';

$container = Container::getInstance();
$container->configure([
    'connections' => [
        'resurs' => [
            'class' => PDO::class,
            'constructor' => ["mysql:dbname=go_db;host=127.0.0.1", 'root', ''],
        ]
    ]
]);

$db = Query::use('resurs');

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
    ]);

$userList = $query->execute(['name' => 'Alex', 'age' => new AnyValue]);

echo $query->rawQuery;
var_dump($userList);