<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mail;
use Password;

class AuthController extends Controller
{
    // public function register(RegisterRequest $request): JsonResponse
    // {
    //     $user = User::create([
    //         "name" => $request->name,
    //         "email" => $request->email,
    //         "password" => Hash::make($request->password),
    //     ]);

    //     $token = $user->createToken("api")->plainTextToken;

    //     return response()->json(
    //         [
    //             "user" => $user,
    //             "token" => $token,
    //         ],
    //         201,
    //     );
    // }

    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only("email", "password"))) {
            throw ValidationException::withMessages([
                "email" => ["Invalid credentials"],
            ]);
        }

        $token = $request->user()->createToken("api")->plainTextToken;

        return response()->json([
            "user" => $request->user(),
            "token" => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(["message" => "Logout successful"]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $user = User::where("email", $request->email)->firstOrFail();

        $token = Password::createToken($user);

        Mail::to($user->email)->send(
            new ResetPasswordMail($token, $user->email),
        );

        return response()->json(["message" => "Password reset sent by email"]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only(
                "email",
                "password",
                "password_confirmation",
                "token",
            ),
            function (User $user, string $password) {
                $user->forceFill(["password" => bcrypt($password)])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(["message" => __($status)], 400);
        }

        return response()->json(["message" => __($status)]);
    }
}
