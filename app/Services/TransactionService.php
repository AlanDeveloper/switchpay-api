<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(protected PaymentService $paymentService) {}

    public function create(
        Client $client,
        Collection $products,
        array $data,
    ): Transaction {
        $dbProducts = $this->fetchProducts($products);
        $amount = $this->calculateAmount($products, $dbProducts);
        $result = $this->processPayment($client, $amount, $data);

        return DB::transaction(function () use (
            $result,
            $products,
            $client,
            $amount,
            $data,
            $dbProducts,
        ) {
            $transaction = $this->persistTransaction(
                $client,
                $amount,
                $result,
                $data,
            );
            $this->persistProducts($transaction, $products);

            if ($result["status"]) {
                $this->decrementStock($products, $dbProducts);
            }

            return $transaction;
        });
    }

    private function fetchProducts(Collection $products): Collection
    {
        return Product::whereIn("id", $products->pluck("id"))
            ->get()
            ->keyBy("id");
    }

    private function calculateAmount(
        Collection $products,
        Collection $dbProducts,
    ): float {
        return $products->sum(
            fn($item) => $dbProducts[$item["id"]]->price * $item["quantity"],
        );
    }

    private function processPayment(
        Client $client,
        float $amount,
        array $data,
    ): array {
        return $this->paymentService->execute([
            "amount" => $amount,
            "name" => $client->name,
            "email" => $client->email,
            "card_number" => $data["card_number"],
            "cvv" => $data["cvv"],
        ]);
    }

    private function persistTransaction(
        Client $client,
        float $amount,
        array $result,
        array $data,
    ): Transaction {
        return Transaction::create([
            "client_id" => $client->id,
            "amount" => $amount,
            "card_last_numbers" => substr($data["card_number"], -4),
            "gateway_id" => $result["gateway_id"],
            "external_id" => $result["external_id"],
            "status" => $result["status"],
        ]);
    }

    private function persistProducts(
        Transaction $transaction,
        Collection $products,
    ): void {
        TransactionProduct::insert(
            $products
                ->map(
                    fn($item) => [
                        "product_id" => $item["id"],
                        "quantity" => $item["quantity"],
                        "transaction_id" => $transaction->id,
                    ],
                )
                ->toArray(),
        );
    }

    private function decrementStock(
        Collection $products,
        Collection $dbProducts,
    ): void {
        foreach ($products as $item) {
            $dbProducts[$item["id"]]->decrement(
                "available_amount",
                $item["quantity"],
            );
        }
    }

    public function refund(Transaction $transaction): Refund
    {
        $result = $this->paymentService->chargeBack($transaction);

        return Refund::create([
            "status" => $result["status"],
            "transaction_id" => $transaction->id,
        ]);
    }
}
