<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clients = Client::when($request->email, function ($query) use (
            $request,
        ) {
            $query->where("email", "like", "%" . $request->email . "%");
        })
            ->when($request->name, function ($query) use ($request) {
                $query->where("name", "like", "%" . $request->name . "%");
            })
            ->paginate($request->per_page ?? 15);

        return response()->json($clients);
    }

    public function show(int $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        return response()->json($client);
    }

    public function store(CreateClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return response()->json($client, 201);
    }

    public function update(UpdateClientRequest $request, int $id): JsonResponse
    {
        Client::where("id", $id)->update($request->validated());

        return response()->json(null, 204);
    }

    public function destroy(int $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json(null, 204);
    }
}
