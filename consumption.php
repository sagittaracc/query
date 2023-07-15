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
            `counter`,
            `tariff`,
            `tariff_number`,
            `consumption`,
            `date`
        FROM consumption
        WHERE counter = :counter AND (`date` = :from OR `date` = :to)
        ORDER BY `date`, `tariff`, `tariff_number`'
    )
    ->group(function($model) {
        return $model->date;
    })
    ->all(['counter' => $counter, 'from' => $from, 'to' => $to]);

$beginCons = $cons[$from];
$endCons = $cons[$to];

foreach ($beginCons as $i => $row) {
    print_r([
        'counter' => $counter,
        'from' => $from,
        'to' => $to,
        'tarif' => $row->tariff,
        'number' => $row->tariff_number,
        'total' => 
            isset($endCons[$i]->consumption)
                ? $endCons[$i]->consumption - $beginCons[$i]->consumption
                : null,
    ]);
}