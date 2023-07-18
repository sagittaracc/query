<?php

use Sagittaracc\Container\Container;
use Sagittaracc\Model;
use Sagittaracc\Query;

require 'vendor/autoload.php';

class User extends Model
{
    public function canSmoke()
    {
        return 'Hell yeah!' . "\n";
    }
}

$container = Container::getInstance();
$container->configure([
    'connections' => [
        'my-db' => [
            'class' => PDO::class,
            'constructor' => ["mysql:dbname=resurs;host=127.0.0.1", 'root', ''],
        ]
    ]
]);

$db = Query::use('my-db');

$query =
    $db
    ->data([
        ['Obj_Id_User' => 266, 'Name' => ''],
        ['Obj_Id_User' => 266, 'Name' => 'Бетар 2'],
    ])
    ->columns([
        'Name' => function($counter) {
            return !empty($counter->Name) ? $counter->Name : 'not defined';
        }
    ])
    ->load([
        'user' => function($counter, $db) {
            return
                $db
                ->query('SELECT * FROM `users` WHERE `Obj_Id_User` = :id')
                ->as(User::class)
                ->one(['id' => $counter->Obj_Id_User]);
        }
    ]);

$data = $query->all();

print_r($data[0]->user->canSmoke());