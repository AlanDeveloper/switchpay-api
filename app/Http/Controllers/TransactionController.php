<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTransactionRequest;
use App\Models\Client;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $transactions = Transaction::with([
            "client",
            "products",
            "gateway",
            "refunds",
        ])
            ->when($request->client_id, function ($query) use ($request) {
                $query->where("client_id", $request->client_id);
            })
            ->paginate($request->per_page ?? 15);

        return response()->json($transactions);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::with([
            "client",
            "products",
            "gateway",
            "refunds",
        ])->findOrFail($id);

        return response()->json($transaction);
    }

    public function store(CreateTransactionRequest $request): JsonResponse
    {
        $client = Client::findOrFail($request->client_id);
        $products = collect($request->input("products"));

        $transaction = $this->transactionService->create(
            $client,
            $products,
            $request->only("card_number", "cvv"),
        );

        return response()->json(
            $transaction->load(["client", "products"]),
            $transaction->status ? 201 : 502,
        );
    }

    public function refund(int $id): JsonResponse
    {
        $transaction = Transaction::where("id", $id)->firstOrFail();

        $refund = $this->transactionService->refund($transaction);

        return response()->json(
            $transaction->load(["client", "products", "gateway", "refunds"]),
            $refund->status ? 201 : 502,
        );
    }
}
