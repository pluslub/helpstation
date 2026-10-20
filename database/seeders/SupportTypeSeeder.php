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
            ['name' => '居宅', 'dispatch_priority' => 1],
            ['name' => '通院', 'dispatch_priority' => 2],
            ['name' => '同行', 'dispatch_priority' => 3],
            ['name' => '移動', 'dispatch_priority' => 4],
            ['name' => '重訪', 'dispatch_priority' => 5],
            ['name' => '行動援護', 'dispatch_priority' => 6],
            ['name' => '日中一時', 'dispatch_priority' => 7],
            ['name' => '多胎児支援', 'dispatch_priority' => 8],
            ['name' => '身体', 'dispatch_priority' => 9],
            ['name' => '県福', 'dispatch_priority' => 10],
            ['name' => '居移', 'dispatch_priority' => 11],
        ];

        foreach ($types as $type) {
            SupportType::create($type);
        }
    }
}
