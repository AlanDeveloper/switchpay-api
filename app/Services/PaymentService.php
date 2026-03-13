<?php

namespace App\Services;

use App\Enums\Gateway;
use App\Models\Gateway as GatewayM;
use App\Models\GatewayLog;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function execute(array $data): array
    {
        $gatewayId = null;
        $successfull = false;
        $result = ['id' => null];
        $gateways = GatewayM::where("is_active", true)
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

            $successfull = true;
            break;
        }

        return [
            "status" => $successfull,
            "external_id" => $result["id"],
            "gateway_id" => $successfull ? $gatewayId : null
        ];
    }
}
