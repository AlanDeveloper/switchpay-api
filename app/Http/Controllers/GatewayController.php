<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGatewayRequest;
use App\Models\Gateway;
use App\Models\GatewayLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $gateways = Gateway::when($request->name, function ($query) use (
            $request,
        ) {
            $query->where("name", "like", "%" . $request->name . "%");
        })->paginate($request->per_page ?? 15);

        return response()->json($gateways);
    }

    public function show(int $id): JsonResponse
    {
        $gateway = Gateway::findOrFail($id);

        return response()->json($gateway);
    }

    public function update(UpdateGatewayRequest $request, int $id): JsonResponse
    {
        Gateway::where("id", $id)->update($request->validated());

        return response()->json(null, 204);
    }

    public function get_logs(Request $request, int $id): JsonResponse
    {
        $logs = GatewayLog::where("gateway_id", $id)->paginate(
            $request->per_page ?? 15,
        );

        return response()->json($logs);
    }
}
