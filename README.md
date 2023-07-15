# query

```php
use Sagittaracc\Container\Container;
use Sagittaracc\Query;
use Sagittaracc\Value\Any;

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
    $db
    ->prepare('SELECT * FROM `groups` WHERE `name` = :name')
    ->columns([
        'users' => function($group, $db) {
            return
                $db
                ->prepare('SELECT * FROM users where group_id = :id')
                ->filter([
                    'age' => new Any
                ])
                ->columns([
                    'age' => function($user) {
                        return "$user->age years old";
                    }
                ])
                ->index(function($user) {
                    return $user->id;
                })
                ->all(['id' => $group->id]);
        }
    ]);

$data = $query->one(['name' => 'admin']);

print_r($data);
var_dump($query->rawDumpQueries);
```

## Result
```php
stdClass Object
(
    [id] => 1
    [name] => admin
    [users] => Array
        (
            [1] => stdClass Object
                (
                    [id] => 1
                    [name] => Alex
                    [age] => 5 years old
                    [group_id] => 1
                )

            [4] => stdClass Object
                (
                    [id] => 4
                    [name] => John
                    [age] => 30 years old
                    [group_id] => 1
                )

        )

)
```

## Query dumps
```sql
SQL: [43] SELECT * FROM `groups` WHERE `name` = :name
Sent SQL: [45] SELECT * FROM `groups` WHERE `name` = 'admin'
Params:  1
Key: Name: [5] :name
paramno=-1
name=[5] ":name"
is_param=1
param_type=2

SQL: [40] SELECT * FROM users where group_id = :id
Sent SQL: [40] SELECT * FROM users where group_id = '1'
Params:  1
Key: Name: [3] :id
paramno=-1
name=[3] ":id"
is_param=1
param_type=2
```