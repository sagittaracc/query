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
                ->data([
                    ['id' => 0, 'key_col_1' => 'val_col_1', 'key_col_2' => 'val_col_2'],
                    ['id' => 1, 'key_col_1' => 'val_col_1', 'key_col_2' => 'val_col_2'],
                    ['id' => 2, 'key_col_1' => 'val_col_1', 'key_col_2' => 'val_col_2'],
                ])
                ->columns([
                    'key_col_3' => fn($row) => $row->key_col_1 . ' ' . $row->key_col_2,
                ])
                ->all();
        
        foreach ($data as $i => $row) {
            $this->assertInstanceOf(Model::class, $row);
            $this->assertSame($i, $row->id);
            $this->assertSame('val_col_1', $row->key_col_1);
            $this->assertSame('val_col_2', $row->key_col_2);
            $this->assertSame('val_col_1 val_col_2', $row->key_col_3);
        }
    }

    public function tearDown(): void
    {
    }
}
