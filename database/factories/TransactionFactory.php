<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Gateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "client_id" => Client::factory(),
            "status" => fake()->boolean(),
            "amount" => fake()->randomFloat(2, 10, 1000),
            "gateway_id" => Gateway::factory(),
            "card_last_numbers" => (string) fake()->numberBetween(1111, 9999),
            "external_id" => "ref_" . fake()->uuid(),
        ];
    }
}
