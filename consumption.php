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
        ],
    ],
]);

$db = Query::use('my-db');

$from = '2023-07-10';
$to = '2023-07-13';
$counter = 1;

$cons =
    $db
    ->prepare(
        'SELECT
            *
        FROM consumption
        WHERE counter = :counter AND (`date` = :from OR `date` = :to)
        ORDER BY `date` ASC'
    )
    ->group(function($model) {
        return "$model->tariff.$model->tariff_number";
    })
    ->columns([
        'total' => function($model) {
            return
                isset($model[1]->consumption)
                    ? $model[1]->consumption - $model[0]->consumption
                    : null;
        }
    ])
    ->all(['counter' => $counter, 'from' => $from, 'to' => $to]);
print_r($cons);