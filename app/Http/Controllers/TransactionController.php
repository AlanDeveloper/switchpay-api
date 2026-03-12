<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\Client;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(protected PaymentService $payment_service) {}

    public function index(Request $request): JsonResponse
    {
        $transactions = Transaction::with(["client", "products", "gateway"])
            ->when($request->client_id, function ($query) use ($request) {
                $query->where("client_id", $request->client_id);
            })
            ->paginate($request->per_page ?? 15);

        return response()->json($transactions);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::with(["client", "products", "gateway"])->findOrFail(
            $id,
        );

        return response()->json($transaction);
    }

    public function store(CreateTransactionRequest $request): JsonResponse
    {
        $client = Client::findOrFail($request->client_id);
        $products = collect($request->input("products"));
        $productIds = $products->pluck("id");
        $dbProducts = Product::whereIn("id", $productIds)->get()->keyBy("id");

        $amount = $products->sum(
            fn($item) => $dbProducts[$item["id"]]->price * $item["quantity"],
        );

        $result = $this->payment_service->execute([
            "amount" => $amount,
            "name" => $client->name,
            "email" => $client->email,
            "card_number" => $request->card_number,
            "cvv" => $request->cvv,
        ]);

        $transaction = Transaction::create([
            "client_id" => $client->id,
            "amount" => $amount,
            "card_last_numbers" => substr($request->card_number, -4),
            "gateway_id" => $result["gateway_id"],
            "external_id" => $result["external_id"],
            "status" => $result["status"],
        ]);

        foreach ($products as $item) {
            TransactionProduct::create([
                "product_id" => $item["id"],
                "quantity" => $item["quantity"],
                "transaction_id" => $transaction->id,
            ]);

            if ($result["status"]) {
                $dbProducts[$item["id"]]->decrement(
                    "available_amount",
                    $item["quantity"],
                );
            }
        }

        return response()->json(
            $transaction->load(["client", "products"]),
            201,
        );
    }
}
