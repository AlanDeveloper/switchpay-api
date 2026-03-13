<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Bus;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mail;
use Password;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    // public function test_it_can_register_user(): void
    // {
    //     $password = "password";
    //     $data = User::factory()->make()->toArray();

    //     $response = $this->post("/api/register", [
    //         ...$data,
    //         "password" => $password,
    //         "password_confirmation" => $password,
    //     ]);

    //     $response->assertStatus(201);
    //     $this->assertDatabaseHas("users", ["email" => $data["email"]]);
    // }

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

    public function test_it_can_forgot_password(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->post("/api/forgotPassword", [
            "email" => $user->email,
        ]);

        $response->assertStatus(200);

        Mail::assertSent(ResetPasswordMail::class, function ($mail) use (
            $user,
        ) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_it_can_reset_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post("/api/resetPassword", [
            "email" => $user->email,
            "password" => "new_password123",
            "password_confirmation" => "new_password123",
            "token" => $token,
        ]);

        $response->assertStatus(200);
        $this->assertTrue(
            Hash::check("new_password123", $user->fresh()->password),
        );
    }
}
