<?php

namespace App\Services;

use Http;

class Gateway2Service
{
    public function processPayment(array $data): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                "Accept" => "application/json",
                "Content-Type" => "application/json",
                "Gateway-Auth-Token" => config(
                    "services.gateways.gateway_2.token",
                ),
                "Gateway-Auth-Secret" => config(
                    "services.gateways.gateway_2.secret",
                ),
            ])
            ->post(
                config("services.gateways.gateway_2.url") . "/transacoes",
                [
                    "valor" => $data["amount"],
                    "nome" => $data["name"],
                    "email" => $data["email"],
                    "numeroCartao" => $data["card_number"],
                    "cvv" => $data["cvv"]
                ],
            );

        return [
            "id" => $response->json("id"),
            "status" => $response->successful(),
        ];
    }
}
