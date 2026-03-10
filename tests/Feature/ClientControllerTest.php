<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_list_clients(): void
    {
        Client::factory(3)->create();
        $response = $this->get("/api/client");

        $response->assertStatus(200);
        $response->assertJsonCount(3, "data");
    }

    public function test_it_can_show_client(): void
    {
        $client = Client::factory()->create();
        $response = $this->get("/api/client/" . $client->id);

        $response->assertStatus(200);
        $response->assertJsonStructure(["name", "email"]);
    }

    public function test_it_can_store_client(): void
    {
        $client = Client::factory()->make()->toArray();
        $response = $this->post("/api/client", $client);

        $response->assertStatus(201);
        $this->assertDatabaseHas("clients", ["name" => $client["name"]]);
    }

    public function test_it_can_update_client(): void
    {
        $client = Client::factory()->create();

        $response = $this->put("/api/client/" . $client->id, [
            ...$client->toArray(),
            "name" => "client name",
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseHas("clients", ["name" => "client name"]);
    }

    public function test_it_can_destroy_client(): void
    {
        $client = Client::factory()->create();

        $response = $this->delete("/api/client/" . $client->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing("clients", ["id" => $client->id]);
    }
}
