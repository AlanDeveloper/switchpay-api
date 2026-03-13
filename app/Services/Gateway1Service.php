<?php

namespace App\Services;

use Exception;
use Http;
use Illuminate\Support\Facades\Log;

class Gateway1Service
{
    protected function generateToken(): string
    {
        $response = Http::timeout(10)
            ->withHeaders([
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ])
            ->post(config("services.gateways.gateway_1.url") . "/login", [
                "email" => config("services.gateways.gateway_1.email"),
                "token" => config("services.gateways.gateway_1.token"),
            ]);

        if ($response->failed() || empty($response->json("token"))) {
            throw new Exception("Authentication failed");
        }

        return $response->json("token");
    }

    public function processPayment(array $data): array
    {
        $token = $this->generateToken();

        $response = Http::timeout(30)
            ->withHeaders([
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ])
            ->withToken($token)
            ->post(
                config("services.gateways.gateway_1.url") . "/transactions",
                [
                    "amount" => (int) $data["amount"],
                    "name" => $data["name"],
                    "email" => $data["email"],
                    "cardNumber" => $data["card_number"],
                    "cvv" => $data["cvv"]
                ],
            );

        return [
            "id" => $response->json("id"),
            "response" => $response->json(),
        ];
    }
}
