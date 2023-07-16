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
$to = '2023-07-12';
$counter = 1;

$cons =
    $db
    ->query(
        'SELECT
            `counter`,
            `tariff`,
            `tariff_number`,
            `consumption`,
            `date`
        FROM consumption
        WHERE counter = :counter AND (`date` = :from OR `date` = :to)
        ORDER BY `date`'
    )
    ->group(fn($model) => [$model->tariff, $model->tariff_number])
    ->all(['counter' => $counter, 'from' => $from, 'to' => $to]);

foreach ($cons as $tariff => $tariffData)
{
    foreach ($tariffData as $tariff_number => $consData)
    {
        print_r([
            'counter' => $counter,
            'from' => $from,
            'to' => $to,
            'tariff' => $tariff,
            'tariff_number' => $tariff_number,
            'total' =>
                isset($consData[1])
                    ? $consData[1]->consumption - $consData[0]->consumption
                    : null,
        ]);
    }
}