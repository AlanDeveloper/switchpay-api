<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Gateway as GatewayM;
use App\Services\PaymentService;
use App\Services\Gateway1Service;
use App\Services\Gateway2Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_success_on_the_first_gateway_and_breaks_loop(): void
    {
        $g1 = GatewayM::factory()->create([
            "key" => "gateway_1",
            "priority" => 10,
            "is_active" => true,
        ]);
        $g2 = GatewayM::factory()->create([
            "key" => "gateway_2",
            "priority" => 5,
            "is_active" => true,
        ]);

        $this->mock(Gateway1Service::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive("processPayment")
                ->once()
                ->andReturn([
                    "status" => true,
                    "id" => "ref_123456",
                ]);
        });

        $this->mock(Gateway2Service::class, function (MockInterface $mock) {
            $mock->shouldReceive("processPayment")->never();
        });

        $service = new PaymentService();
        $result = $service->execute([
            "amount" => 100,
            "name" => "Alan Santos",
            "email" => "alan@gmail.com",
            "card_number" => "1234123412344321",
            "cvv" => "131",
        ]);

        $this->assertTrue($result["status"]);
        $this->assertEquals($g1->id, $result["gateway_id"]);
        $this->assertEquals("ref_123456", $result["external_id"]);

        $this->assertDatabaseHas("gateway_logs", [
            "gateway_id" => $g1->id,
            "status" => true,
            "message" => "Successful processing payment by " . $g1->name,
        ]);

        $this->assertDatabaseMissing("gateway_logs", ["gateway_id" => $g2->id]);
    }

    public function test_it_returns_success_on_the_second_gateway_and_breaks_loop(): void
    {
        $g1 = GatewayM::factory()->create([
            "key" => "gateway_1",
            "priority" => 10,
            "is_active" => true,
        ]);
        $g2 = GatewayM::factory()->create([
            "key" => "gateway_2",
            "priority" => 5,
            "is_active" => true,
        ]);

        $this->mock(Gateway1Service::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive("processPayment")
                ->once()
                ->andReturn([
                    "status" => false,
                    "id" => null,
                ]);
        });

        $this->mock(Gateway2Service::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive("processPayment")
                ->once()
                ->andReturn([
                    "status" => true,
                    "id" => "ref_123456",
                ]);
        });

        $service = new PaymentService();
        $result = $service->execute([
            "amount" => 100,
            "name" => "Alan Santos",
            "email" => "alan@gmail.com",
            "card_number" => "1234123412344321",
            "cvv" => "131",
        ]);

        $this->assertTrue($result["status"]);
        $this->assertEquals($g2->id, $result["gateway_id"]);
        $this->assertEquals("ref_123456", $result["external_id"]);

        $this->assertDatabaseHas("gateway_logs", [
            "gateway_id" => $g2->id,
            "status" => true,
            "message" => "Successful processing payment by " . $g2->name,
        ]);
    }
}
