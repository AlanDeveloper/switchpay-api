<?php

namespace App\Services;

use App\Enums\Gateway;
use App\Models\Gateway as GatewayM;
use App\Models\GatewayLog;
use Exception;

class PaymentService
{
    public function execute(array $data): array
    {
        $gatewayId = null;
        $successfull = false;
        $gateways = GatewayM::where("active", true)
            ->orderBy("priority", "desc")
            ->get();

        foreach ($gateways as $gatewayModel) {
            $gateway = Gateway::tryFrom($gatewayModel->key);

            if (!$gateway) {
                GatewayLog::create([
                    "status" => false,
                    "message" => "Invalid gateway: " . $gatewayModel->key,
                ]);
                continue;
            }
            $gatewayId = $gatewayModel->id;

            try {
                $result = app($gateway->class())->processPayment($data);
            } catch (Exception $e) {
                GatewayLog::create([
                    "status" => false,
                    "gateway_id" => $gatewayModel->id,
                    "message" =>
                        "Error processing payment by " .
                        $gateway->value .
                        ": " .
                        $e->getMessage(),
                ]);
                continue;
            }

            if (!$result["status"]) {
                GatewayLog::create([
                    "status" => false,
                    "gateway_id" => $gatewayModel->id,
                    "message" =>
                        "Error processing payment by " . $gateway->value,
                ]);
                continue;
            }

            GatewayLog::create([
                "status" => true,
                "gateway_id" => $gatewayModel->id,
                "message" =>
                    "Successful processing payment by " . $gateway->value,
            ]);

            $successfull = true;
            break;
        }

        return [
            "status" => $successfull,
            "external_id" => $result["id"],
            "gateway_id" => $gatewayId
        ];
    }
}
