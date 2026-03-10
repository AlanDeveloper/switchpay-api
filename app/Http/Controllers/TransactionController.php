<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transactions = Transaction::with(["client", "products"])
            ->when($request->client_id, function ($query) use ($request) {
                $query->where("client_id", $request->client_id);
            })
            ->paginate($request->per_page ?? 15);

        return response()->json($transactions);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::with(["products", "client"])->findOrFail(
            $id,
        );

        return response()->json($transaction);
    }

    public function store(Request $request): JsonResponse
    {
        $client = Client::findOrFail($request->client_id);
        $products = collect($request->input("products"));
        $productIds = $products->pluck("id");
        $dbProducts = Product::whereIn("id", $productIds)->get()->keyBy("id");

        foreach ($products as $item) {
            if (!isset($dbProducts[$item["id"]])) {
                return response()->json(
                    [
                        "message" => "Product with id '{$item["id"]}' not found.",
                    ],
                    404,
                );
            }

            $product = $dbProducts[$item["id"]];
            if ($product->available_amount < $item["quantity"]) {
                return response()->json(
                    [
                        "message" => "Product '{$product->name}' only has {$product->available_amount} units available.",
                    ],
                    422,
                );
            }
        }

        $amount = $products->sum(
            fn($item) => $dbProducts[$item["id"]]->price * $item["quantity"],
        );

        $transaction = DB::transaction(function () use (
            $client,
            $amount,
            $products,
            $dbProducts,
        ) {
            $transaction = Transaction::create([
                "client_id" => $client->id,
                "amount" => $amount,
            ]);

            foreach ($products as $item) {
                TransactionProduct::create([
                    "product_id" => $item["id"],
                    "quantity" => $item["quantity"],
                    "transaction_id" => $transaction->id,
                ]);

                $dbProducts[$item["id"]]->decrement(
                    "available_amount",
                    $item["quantity"],
                );
            }

            return $transaction;
        });

        return response()->json(
            $transaction->load(["client", "products"]),
            201,
        );
    }
}
