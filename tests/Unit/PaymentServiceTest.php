<?php

namespace Tests\Unit;

use App\Models\Refund;
use App\Models\Transaction;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
                    "id" => null,
                ]);
        });

        $this->mock(Gateway2Service::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive("processPayment")
                ->once()
                ->andReturn([
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
        ]);
    }

    public function test_it_can_charge_back_successfully(): void
    {
        $g1 = GatewayM::factory()->create([
            "key" => "gateway_1",
            "is_active" => true,
        ]);

        $transaction = Transaction::factory()->create([
            "external_id" => "ref_123456",
            "gateway_id" => $g1->id,
        ]);

        $this->mock(Gateway1Service::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive("refundPayment")
                ->once()
                ->andReturn([
                    "status" => true,
                ]);
        });

        $service = new PaymentService();
        $result = $service->charge_back($transaction);

        $this->assertTrue($result["status"]);
        $this->assertDatabaseHas("gateway_logs", [
            "gateway_id" => $g1->id,
            "status" => true,
        ]);
    }

    public function test_it_fails_charge_back_when_gateway_refuses(): void
    {
        $g1 = GatewayM::factory()->create([
            "key" => "gateway_1",
            "is_active" => true,
        ]);

        $transaction = Transaction::factory()->create([
            "external_id" => "ref_123456",
            "gateway_id" => $g1->id,
        ]);

        $this->mock(Gateway1Service::class, function (MockInterface $mock) {
            $mock
                ->shouldReceive("refundPayment")
                ->once()
                ->andReturn([
                    "status" => false,
                    "response" => ["error" => "Refund refused"],
                ]);
        });

        $service = new PaymentService();
        $result = $service->charge_back($transaction);

        $this->assertFalse($result["status"]);
        $this->assertDatabaseHas("gateway_logs", [
            "gateway_id" => $g1->id,
            "status" => false,
        ]);
    }

    public function test_it_throws_exception_when_transaction_has_no_external_id(): void
    {
        $transaction = Transaction::factory()->create([
            "external_id" => null,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Transaction has no external ID to refund.",
        );

        $service = new PaymentService();
        $service->charge_back($transaction);
    }

    public function test_it_throws_exception_when_transaction_already_refunded(): void
    {
        $transaction = Transaction::factory()->create([
            "external_id" => "ref_123456",
        ]);

        Refund::factory()->create([
            "transaction_id" => $transaction->id,
            "status" => true,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Transaction has already been refunded.");

        $service = new PaymentService();
        $service->charge_back($transaction);
    }

    public function test_it_throws_exception_when_gateway_is_inactive(): void
    {
        $g1 = GatewayM::factory()->create([
            "key" => "gateway_1",
            "is_active" => false,
        ]);

        $transaction = Transaction::factory()->create([
            "external_id" => "ref_123456",
            "gateway_id" => $g1->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        $service = new PaymentService();
        $service->charge_back($transaction);
    }
}
