<?php

namespace App\Services;

use App\Enums\Gateway;
use App\Models\Gateway as GatewayModel;
use App\Models\GatewayLog;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function execute(array $data): array
    {
        $gatewayId = null;
        $successful = false;
        $result = ["id" => null];
        $gateways = GatewayModel::where("is_active", true)
            ->orderBy("priority", "desc")
            ->get();

        foreach ($gateways as $gatewayModel) {
            $gateway = Gateway::tryFrom($gatewayModel->key);

            if (!$gateway) {
                GatewayLog::create([
                    "status" => false,
                    "message" => "Invalid gateway: " . $gatewayModel->name,
                ]);
                continue;
            }
            $gatewayId = $gatewayModel->id;

            try {
                $result = app($gateway->class())->processPayment($data);

                if (!isset($result["id"])) {
                    throw new Exception(json_encode($result["response"]));
                }
            } catch (Exception $e) {
                Log::error($e->getMessage());
                GatewayLog::create([
                    "status" => false,
                    "gateway_id" => $gatewayModel->id,
                    "message" =>
                        "Error processing payment by " .
                        $gatewayModel->name .
                        ": " .
                        $e->getMessage(),
                ]);
                continue;
            }

            GatewayLog::create([
                "status" => true,
                "gateway_id" => $gatewayModel->id,
                "message" =>
                    "Successful processing payment by " . $gatewayModel->name,
            ]);

            $successful = true;
            break;
        }

        return [
            "status" => $successful,
            "external_id" => $result["id"],
            "gateway_id" => $successful ? $gatewayId : null,
        ];
    }

    public function chargeBack(Transaction $transaction): array
    {
        if ($transaction->external_id === null) {
            throw new Exception("Transaction has no external ID to refund.");
        }

        if ($transaction->refunds()->where("status", true)->exists()) {
            throw new Exception("Transaction has already been refunded.");
        }

        $gateway = GatewayModel::where("is_active", true)
            ->where("id", $transaction->gateway_id)
            ->firstOrFail();

        $gatewayEnum = Gateway::tryFrom($gateway->key);

        if (!$gatewayEnum) {
            throw new Exception("Invalid gateway: " . $gateway->name);
        }

        try {
            $result = app($gatewayEnum->class())->refundPayment(
                $transaction->external_id,
            );

            if (!$result["status"]) {
                throw new Exception(json_encode($result["response"]));
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            GatewayLog::create([
                "status" => false,
                "gateway_id" => $gateway->id,
                "message" =>
                    "Error processing payment by " .
                    $gateway->name .
                    ": " .
                    $e->getMessage(),
            ]);

            return [
                "status" => false,
            ];
        }

        GatewayLog::create([
            "status" => true,
            "gateway_id" => $gateway->id,
            "message" => "Successful refunding payment by " . $gateway->name,
        ]);

        return [
            "status" => true,
        ];
    }
}
