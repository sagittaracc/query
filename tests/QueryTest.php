<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Sagittaracc\Model;
use Sagittaracc\models\User;
use Sagittaracc\Query;
use Sagittaracc\stubs\Counters;
use Sagittaracc\stubs\Users;

final class QueryTest extends TestCase
{
    private $q;
    private $stubUsers;
    private $stubCounters;

    public function setUp(): void
    {
        $this->q = Query::use();
        $this->stubUsers = (new Users)->all();
        $this->stubCounters = (new Counters)->all();
    }

    public function testUse(): void
    {
        $this->assertInstanceOf(Query::class, $this->q);
        $this->assertNull($this->q->getSql());
        $this->assertNull($this->q->data);
        $this->assertSame(Model::class, $this->q->getClassObject());
    }

    public function testColumns(): void
    {
        $data =
            $this
                ->q
                ->data($this->stubUsers)
                ->as(User::class)
                ->index(fn($user) => $user->Obj_Id_User)
                ->load([
                    'counters' => function($user, $q) {
                        $counters =
                            $q
                            ->data($this->stubCounters)
                            ->columns([
                                'Name' => fn($counter) => !empty($counter->Name) ? $counter->Name : "#$counter->Obj_Id_Counter",
                            ])
                            ->group(fn($counter) => $counter->Obj_Id_User)
                            ->all();
                        return $counters[$user->Obj_Id_User] ?? [];
                    }
                ])
                ->all();
        
        $this->assertInstanceOf(User::class, $data[266]);
        $this->assertInstanceOf(User::class, $data[307]);
        $this->assertSame($this->stubUsers[0]['Balance'] < 0, $data[266]->hasDebt());
        $this->assertSame($this->stubUsers[1]['Balance'] < 0, $data[307]->hasDebt());

        $this->assertSame($this->stubUsers[0]['FIO'], $data[266]->FIO);
        $this->assertSame($this->stubUsers[1]['FIO'], $data[307]->FIO);

        $this->assertInstanceOf(Model::class, $data[266]->counters[0]);
        $this->assertInstanceOf(Model::class, $data[266]->counters[1]);
        $this->assertInstanceOf(Model::class, $data[266]->counters[2]);

        $this->assertSame('#'.$this->stubCounters[0]['Obj_Id_Counter'], $data[266]->counters[0]->Name);
        $this->assertSame($this->stubCounters[1]['Name'], $data[266]->counters[1]->Name);
        $this->assertSame($this->stubCounters[2]['Name'], $data[266]->counters[2]->Name);

        $this->assertSame([], $data[307]->counters);
    }

    public function tearDown(): void
    {
    }
}
