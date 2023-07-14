<?php

use Sagittaracc\AnyValue;
use Sagittaracc\Container;
use Sagittaracc\Query;

require 'vendor/autoload.php';

$container = Container::getInstance();
$container->configure([
    ...require __DIR__ . '/src/config/db.php'
]);

$db = Query::use('resurs');

$query =
    $db->prepare(
        'SELECT
            `c`.`Obj_Id_Counter`,
            `c`.`Name`,
            `c`.`SerialNumber`,
            `c`.`Battery`,
            `c`.`PreviousVerification`,
            `r`.`Name_Resurs`
        FROM counter c
        JOIN resurs r ON r.id_Resurs = c.Id_Resurs
        WHERE
            Obj_Id_User = :user_id
        AND Obj_Id_Counter = :counter_id'
    )
    ->columns([
        'Name' => function($model) {
            if (!is_null($model->Name) && !is_null($model->SerialNumber) && $model->Name !== '' && $model->SerialNumber !== '') {
                return "$model->Name ($model->SerialNumber)";
            }
            else if (!is_null($model->Name) && $model->Name !== '') {
                return $model->Name;
            }
            else {
                return $model->SerialNumber;
            }
        },
        'Battery' => function($model) {
            return is_null($model->Battery) ? '' : "$model->Battery В";
        },
        'PreviousVerification' => function($model) {
            if (!is_null($model->PreviousVerification)) {
                $date = date_create($model->PreviousVerification);
                return date_format($date, 'd.m.Y');
            }
        }
    ]);

$counterList = $query->execute(['user_id' => 266, 'counter_id' => new AnyValue]);

echo $query->rawQuery;
var_dump($counterList);