<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $type = [
            ['name' => '車いす', 'type' => 'care_ev'],
            ['name' => '普通車', 'type' => 'company_car'],
        ];
    }
}
