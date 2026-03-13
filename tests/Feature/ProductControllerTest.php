<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductControllerTest extends TestCase
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

    public function test_it_can_list_products(): void
    {
        Product::factory(3)->create();
        $response = $this->get("/api/product");

        $response->assertStatus(200);
        $response->assertJsonCount(3, "data");
    }

    public function test_it_can_show_product(): void
    {
        $product = Product::factory()->create();
        $response = $this->get("/api/product/" . $product->id);

        $response->assertStatus(200);
        $response->assertJsonStructure(["name", "price", "available_amount"]);
    }

    public function test_it_can_store_product(): void
    {
        $product = Product::factory()->make()->toArray();
        $response = $this->post("/api/product", $product);

        $response->assertStatus(201);
        $this->assertDatabaseHas("products", ["name" => $product["name"]]);
    }

    public function test_it_can_update_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->put("/api/product/" . $product->id, [
            ...$product->toArray(),
            "name" => "teste 2",
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseHas("products", ["name" => "teste 2"]);
    }

    public function test_it_can_destroy_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->delete("/api/product/" . $product->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing("products", ["id" => $product->id]);
    }
}
