<?php

use Sagittaracc\Container\Container;
use Sagittaracc\Model;
use Sagittaracc\Query;
use Sagittaracc\Value\Any;

require 'vendor/autoload.php';

class User extends Model
{
    public function canSmoke()
    {
        return ($this->age > 7 ? 'Hell yeah!' : 'Hah') . "\n";
    }
}

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
    ->query('SELECT * FROM `groups` WHERE `name` = :name')
    ->load([
        'users' => function($group, $db) {
            return
                $db
                ->query('SELECT * FROM users where group_id = :id')
                ->as(User::class)
                ->filter([
                    'age' => new Any
                ])
                ->index(fn($user) => $user->id)
                ->columns([
                    'underage' => fn($user) => $user->age < 18,
                    'canDrink' => fn($user) => $user->underage ? 'no' : 'yes',
                    'parent' => fn($user, $db, $data) => $data[$user->parent_id] ?? null,
                ])
                ->all(['id' => $group->id]);
        }
    ]);

$data = $query->one(['name' => 'admin']);

print_r($data);
print_r($query->rawDumpQueries);
print_r($data->users[4]->canSmoke());
print_r($query->rawDumpQueries);