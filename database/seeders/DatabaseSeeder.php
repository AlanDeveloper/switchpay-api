<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RoleSeeder::class, GatewaySeeder::class]);

        $users = [
            [
                "name" => "Admin",
                "email" => "admin@admin.com",
                "role" => "admin",
            ],
            [
                "name" => "Manager",
                "email" => "manager@admin.com",
                "role" => "manager",
            ],
            [
                "name" => "Finance",
                "email" => "finance@admin.com",
                "role" => "finance",
            ],
            ["name" => "User", "email" => "user@admin.com", "role" => "user"],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ["email" => $userData["email"]],
                [
                    "name" => $userData["name"],
                    "password" => Hash::make("password"),
                ],
            );

            $user->assignRole(Role::where("name", $userData["role"])->first());
        }
    }
}
