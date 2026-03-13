<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserControllerTest extends TestCase
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

    public function test_it_can_list_users(): void
    {
        User::factory(3)->create();
        $response = $this->get("/api/user");

        $response->assertStatus(200);
        $response->assertJsonCount(4, "data");
    }

    public function test_it_can_show_user(): void
    {
        $user = User::factory()->create();
        $response = $this->get("/api/user/" . $user->id);

        $response->assertStatus(200);
        $response->assertJsonStructure(["name", "email"]);
    }

    public function test_it_can_store_user(): void
    {
        $user = User::factory()->make()->toArray();
        $response = $this->post("/api/user", [...$user, "role" => "user"]);

        $response->assertStatus(201);
        $this->assertDatabaseHas("users", [
            "name" => $user["name"],
        ]);
    }

    public function test_it_can_update_user(): void
    {
        $user = User::factory()->create();

        $response = $this->put("/api/user/" . $user->id, [
            ...$user->toArray(),
            "name" => "username",
            "role" => "user",
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseHas("users", ["name" => "username"]);
    }

    public function test_it_can_destroy_user(): void
    {
        $user = User::factory()->create();

        $response = $this->delete("/api/user/" . $user->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing("users", ["id" => $user->id]);
    }
}
