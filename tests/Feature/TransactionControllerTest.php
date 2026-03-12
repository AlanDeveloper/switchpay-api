<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_list_transactions(): void
    {
        Transaction::factory(3)->create();
        $response = $this->get("/api/transaction");

        $response->assertStatus(200);
        $response->assertJsonCount(3, "data");
    }

    public function test_it_can_show_transaction(): void
    {
        $transaction = Transaction::factory()->create();
        $response = $this->get("/api/transaction/" . $transaction->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "client_id",
            "status",
            "amount",
            "gateway_id",
            "card_last_numbers",
            "external_id",
        ]);
    }

    public function test_it_can_create_transaction_successfully(): void
    {
        $client = Client::factory()->create(["name" => "John Doe"]);
        $product = Product::factory()->create([
            "price" => 100,
            "available_amount" => 10,
        ]);
        $gateway = Gateway::factory()->create();

        $this->mock(PaymentService::class, function (MockInterface $mock) use (
            $gateway,
        ) {
            $mock
                ->shouldReceive("execute")
                ->once()
                ->andReturn([
                    "gateway_id" => $gateway->id,
                    "external_id" => "ref_123456",
                    "status" => true,
                ]);
        });

        $payload = [
            "client_id" => $client->id,
            "card_number" => "1234567812341234",
            "cvv" => "123",
            "products" => [["id" => $product->id, "quantity" => 2]],
        ];

        $response = $this->post("/api/transaction", $payload);

        $response
            ->assertStatus(201)
            ->assertJsonPath("amount", "200.00")
            ->assertJsonPath("status", true);

        $this->assertDatabaseHas("transactions", [
            "client_id" => $client->id,
            "amount" => 200,
            "external_id" => "ref_123456",
        ]);

        $this->assertDatabaseHas("products", [
            "id" => $product->id,
            "available_amount" => 8,
        ]);
    }

    public function test_it_cannot_create_transaction_successfully(): void
    {
        $client = Client::factory()->create(["name" => "John Doe"]);
        $product = Product::factory()->create([
            "price" => 100,
            "available_amount" => 10,
        ]);
        $gateway = Gateway::factory()->create();

        $this->mock(PaymentService::class, function (MockInterface $mock) use (
            $gateway,
        ) {
            $mock
                ->shouldReceive("execute")
                ->once()
                ->andReturn([
                    "gateway_id" => $gateway->id,
                    "external_id" => null,
                    "status" => false,
                ]);
        });

        $payload = [
            "client_id" => $client->id,
            "card_number" => "1234567812341234",
            "cvv" => "123",
            "products" => [["id" => $product->id, "quantity" => 2]],
        ];

        $response = $this->post("/api/transaction", $payload);

        $response
            ->assertStatus(201)
            ->assertJsonPath("amount", "200.00")
            ->assertJsonPath("status", false);

        $this->assertDatabaseHas("transactions", [
            "client_id" => $client->id,
            "amount" => 200,
            "external_id" => null,
        ]);

        $this->assertDatabaseHas("products", [
            "id" => $product->id,
            "available_amount" => 10,
        ]);
    }

    public function test_it_fails_when_product_does_not_exist(): void
    {
        $client = Client::factory()->create();

        $payload = [
            "client_id" => $client->id,
            "card_number" => "1234123412341234",
            "cvv" => "123",
            "products" => [["id" => 9999, "quantity" => 1]],
        ];

        $response = $this->post("/api/transaction", $payload);

        $response->assertStatus(422);
    }

    public function test_it_fails_when_product_does_has_available_amount(): void
    {
        $client = Client::factory()->create();
        $product = Product::factory()->create(["available_amount" => 2]);

        $payload = [
            "client_id" => $client->id,
            "card_number" => "1234123412341234",
            "cvv" => "123",
            "products" => [["id" => $product->id, "quantity" => 10]],
        ];

        $response = $this->post("/api/transaction", $payload);

        $response->assertStatus(422);
    }
}
