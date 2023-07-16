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

$lastCons =
    $db
    ->query(
        'SELECT
            `con`.*
        FROM `consumption` `con`
        JOIN (
            SELECT
                `counter`,
                `tariff_number`,
                MAX(`date`) AS `date`
            FROM `consumption`
            GROUP BY `counter`, `tariff_number`
        ) `last_con`
        ON
            `con`.`counter` = `last_con`.`counter` AND
            `con`.`tariff_number` = `last_con`.`tariff_number` AND
            `con`.`date` = `last_con`.`date`'
    )
    ->group(fn($model) => [$model->counter])
    ->all();

$counters =
    $db
    ->query('SELECT * FROM counter')
    ->columns([
        'last_consumption' => fn($counter) => $lastCons[$counter->id],
    ])
    ->all();

print_r($counters);