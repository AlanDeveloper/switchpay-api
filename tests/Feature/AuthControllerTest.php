<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_register_user(): void
    {
        $password = "password";
        $data = User::factory()->make()->toArray();

        $response = $this->post("/api/register", [
            ...$data,
            "password" => $password,
            "password_confirmation" => $password,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas("users", ["email" => $data["email"]]);

    }
    public function test_it_can_login_user(): void
    {
        $user = User::factory()->create(["password" => "password"]);

        $response = $this->post("/api/login", [
            "email" => $user->email,
            "password" => "password",
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "user" => ["id", "name", "email", "created_at", "updated_at"],
            "token",
        ]);
    }

    public function test_it_can_logout_user(): void
    {
        $user = User::factory()->create(["password" => "password"]);
        $token = $user->createToken("api")->plainTextToken;

        $response = $this->withToken($token)->post("/api/logout");

        $response->assertStatus(200);
    }
}
