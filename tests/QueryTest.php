<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Sagittaracc\Model;
use Sagittaracc\Query;

final class QueryTest extends TestCase
{
    private $q;

    public function setUp(): void
    {
        $this->q = Query::use();
    }

    private function stubCounter()
    {
        return [
            ['AI_Counter' => 1, 'Obj_Id_Counter' => 182, 'Obj_Id_User' => 266, 'Obj_Id_Tarif' => 268, 'Id_Resurs' => 1, 'Obj_Id_Home' => 264, 'HomeCounter' => 0, 'Name' => ''],
            ['AI_Counter' => 2, 'Obj_Id_Counter' => 183, 'Obj_Id_User' => 266, 'Obj_Id_Tarif' => 268, 'Id_Resurs' => 1, 'Obj_Id_Home' => 264, 'HomeCounter' => 0, 'Name' => 'Газ'],
            ['AI_Counter' => 3, 'Obj_Id_Counter' => 184, 'Obj_Id_User' => 266, 'Obj_Id_Tarif' => 268, 'Id_Resurs' => 1, 'Obj_Id_Home' => 264, 'HomeCounter' => 0, 'Name' => 'Вода'],
        ];
    }

    private function stubUser()
    {
        return [
            ['AI_User' => 1, 'Obj_Id_User' => 266, 'Obj_Id_Home' => 264, 'FIO' => 'Абонент 1'],
            ['AI_User' => 2, 'Obj_Id_User' => 307, 'Obj_Id_Home' => 264, 'FIO' => 'Абонент 2'],
        ];
    }

    public function testUse(): void
    {
        $this->assertInstanceOf(Query::class, $this->q);
        $this->assertNull($this->q->getSql());
        $this->assertNull($this->q->data);
        $this->assertSame(Model::class, $this->q->getModelClass());
    }

    public function testColumns(): void
    {
        $data =
            $this
                ->q
                ->data($this->stubUser())
                ->index(fn($user) => $user->Obj_Id_User)
                ->load([
                    'counters' => function($user, $q) {
                        $counters =
                            $q
                            ->data($this->stubCounter())
                            ->columns([
                                'Name' => fn($counter) => !empty($counter->Name) ? $counter->Name : "#$counter->Obj_Id_Counter",
                            ])
                            ->group(fn($counter) => $counter->Obj_Id_User)
                            ->all();
                        return $counters[$user->Obj_Id_User] ?? [];
                    }
                ])
                ->all();
        
        $this->assertInstanceOf(Model::class, $data[266]);
        $this->assertInstanceOf(Model::class, $data[307]);

        $this->assertSame('Абонент 1', $data[266]->FIO);
        $this->assertSame('Абонент 2', $data[307]->FIO);

        $this->assertInstanceOf(Model::class, $data[266]->counters[0]);
        $this->assertInstanceOf(Model::class, $data[266]->counters[1]);
        $this->assertInstanceOf(Model::class, $data[266]->counters[2]);

        $this->assertSame('#182', $data[266]->counters[0]->Name);
        $this->assertSame('Газ', $data[266]->counters[1]->Name);
        $this->assertSame('Вода', $data[266]->counters[2]->Name);

        $this->assertSame([], $data[307]->counters);
    }

    public function tearDown(): void
    {
    }
}
