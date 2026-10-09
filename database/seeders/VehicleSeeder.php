<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => '福祉車両', 'type' => 'care_ev'],
            ['name' => '普通車', 'type' => 'company_car'],
        ];

        foreach ($types as $type) {
            Vehicle::create($type);
        }
    }
}
