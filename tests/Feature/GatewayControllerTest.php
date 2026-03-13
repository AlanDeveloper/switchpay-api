<?php

namespace Tests\Feature;

use App\Models\Gateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GatewayControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(["name" => "admin"]);
        Role::create(["name" => "user"]);
        $this->admin = User::factory()->create();
        $this->admin->assignRole("admin");
        $this->actingAs($this->admin);
    }

    public function test_it_can_list_gateways(): void
    {
        Gateway::factory(3)->create();
        $response = $this->get("/api/gateway");

        $response->assertStatus(200);
        $response->assertJsonCount(3, "data");
    }

    public function test_it_can_show_gateway(): void
    {
        $gateway = Gateway::factory()->create();
        $response = $this->get("/api/gateway/" . $gateway->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "key",
            "name",
            "is_active",
            "priority",
        ]);
    }

    public function test_it_can_update_priority_of_the_gateway(): void
    {
        $gateway = Gateway::factory()->create(["priority" => 10]);

        $response = $this->patch("/api/gateway/" . $gateway->id, [
            "priority" => 99,
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseHas("gateways", ["priority" => 99]);
    }

    public function test_it_can_update_is_active_of_the_gateway(): void
    {
        $gateway = Gateway::factory()->create(["is_active" => true]);

        $response = $this->patch("/api/gateway/" . $gateway->id, [
            "is_active" => false,
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseHas("gateways", ["is_active" => false]);
    }
}
