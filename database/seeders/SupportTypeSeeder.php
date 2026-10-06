<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SupportType;

class SupportTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => '通院（病院）', 'dispatch_priority' => 1],
            ['name' => '通院（クリニック）', 'dispatch_priority' => 2],
            ['name' => '固定', 'dispatch_priority' => 3],
            ['name' => '重要A', 'dispatch_priority' => 4],
            ['name' => '重要B', 'dispatch_priority' => 5],
            ['name' => '重要C', 'dispatch_priority' => 6],
            ['name' => '居宅支援', 'dispatch_priority' => 7],
        ];

        foreach ($types as $type) {
            SupportType::create($type);
        }
    }
}
