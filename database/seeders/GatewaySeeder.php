<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $list = [
            [
                "key" => "gateway_1",
                "name" => "Gateway 1",
                "is_active" => true,
                "priority" => 0,
            ],
            [
                "key" => "gateway_2",
                "name" => "Gateway 2",
                "is_active" => true,
                "priority" => 10,
            ],
        ];
        foreach ($list as $item) {
            Gateway::create([
                "key" => $item["key"],
                "name" => $item["name"],
                "is_active" => $item["is_active"],
                "priority" => $item["priority"],
            ]);
        }
    }
}
