<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Route::post("/register", [AuthController::class, "register"]);
Route::post("/login", [AuthController::class, "login"]);
Route::post("/forgotPassword", [AuthController::class, "forgotPassword"]);
Route::post("/resetPassword", [AuthController::class, "resetPassword"]);
Route::post("/transaction", [TransactionController::class, "store"]);

Route::middleware("auth:sanctum")->group(function () {

    Route::post("/logout", [AuthController::class, "logout"]);

    Route::middleware("role:admin|manager|finance|user")->group(function () {
        Route::get("/profile", [UserController::class, "profile"]);
        Route::get("/product", [ProductController::class, "index"]);
        Route::get("/product/{id}", [ProductController::class, "show"]);
        Route::get("/client", [ClientController::class, "index"]);
        Route::get("/client/{id}", [ClientController::class, "show"]);
    });

    Route::middleware("role:admin|finance")->group(function () {
        Route::post("/transaction/{id}/refund", [TransactionController::class, "refund"]);
    });

    Route::middleware("role:admin|manager")->group(function () {
        Route::get("/user", [UserController::class, "index"]);
        Route::get("/user/{id}", [UserController::class, "show"]);
        Route::post("/user", [UserController::class, "store"]);
        Route::put("/user/{id}", [UserController::class, "update"]);
        Route::delete("/user/{id}", [UserController::class, "destroy"]);
    });

    Route::middleware("role:admin|manager|finance")->group(function () {
        Route::get("/transaction", [TransactionController::class, "index"]);
        Route::get("/transaction/{id}", [TransactionController::class, "show"]);
        Route::post("/product", [ProductController::class, "store"]);
        Route::put("/product/{id}", [ProductController::class, "update"]);
        Route::delete("/product/{id}", [ProductController::class, "destroy"]);
        Route::post("/client", [ClientController::class, "store"]);
        Route::put("/client/{id}", [ClientController::class, "update"]);
        Route::delete("/client/{id}", [ClientController::class, "destroy"]);
    });

    Route::middleware("role:admin")->group(function () {
        Route::get("/gateway", [GatewayController::class, "index"]);
        Route::get("/gateway/{id}", [GatewayController::class, "show"]);
        Route::patch("/gateway/{id}", [GatewayController::class, "update"]);
        Route::get("/gateway/{id}/logs", [GatewayController::class, "get_logs"]);
    });
});
