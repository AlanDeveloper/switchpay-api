<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::when($request->email, function ($query) use ($request) {
            $query->where("email", "like", "%" . $request->email . "%");
        })
            ->when($request->name, function ($query) use ($request) {
                $query->where("name", "like", "%" . $request->name . "%");
            })
            ->paginate($request->per_page ?? 15);

        return response()->json($users);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        return response()->json($user);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $password = "teste";
        $user = User::create([
            ...$request->validated(),
            "password" => $password,
        ]);

        //send password by email

        return response()->json($user, 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        User::where("id", $id)->update($request->validated());

        return response()->json(null, 204);
    }

    public function destroy(int $id): JsonResponse
    {
        User::where("id", $id)->delete();

        return response()->json(null, 204);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
