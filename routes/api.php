<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post("/register", [AuthController::class, "register"]);
Route::post("/login", [AuthController::class, "login"]);
Route::post("/forgotPassword", [AuthController::class, "forgotPassword"]);
Route::post("/resetPassword", [AuthController::class, "resetPassword"]);

Route::middleware("auth:sanctum")->group(function () {
    Route::get("/profile", [UserController::class, "profile"]);

    Route::post("/logout", [AuthController::class, "logout"]);
});

Route::get("/gateway", [GatewayController::class, "index"]);
Route::get("/gateway/{id}", [GatewayController::class, "show"]);
Route::patch("/gateway/{id}", [GatewayController::class, "update"]);
Route::get("/gateway/{id}/logs", [GatewayController::class, "get_logs"]);

Route::get("/transaction", [TransactionController::class, "index"]);
Route::get("/transaction/{id}", [TransactionController::class, "show"]);
Route::post("/transaction", [TransactionController::class, "store"]);

Route::get("/user", [UserController::class, "index"]);
Route::get("/user/{id}", [UserController::class, "show"]);
Route::post("/user", [UserController::class, "store"]);
Route::put("/user/{id}", [UserController::class, "update"]);
Route::delete("/user/{id}", [UserController::class, "destroy"]);

Route::get("/client", [ClientController::class, "index"]);
Route::get("/client/{id}", [ClientController::class, "show"]);
Route::post("/client", [ClientController::class, "store"]);
Route::put("/client/{id}", [ClientController::class, "update"]);
Route::delete("/client/{id}", [ClientController::class, "destroy"]);

Route::get("/product", [ProductController::class, "index"]);
Route::get("/product/{id}", [ProductController::class, "show"]);
Route::post("/product", [ProductController::class, "store"]);
Route::put("/product/{id}", [ProductController::class, "update"]);
Route::delete("/product/{id}", [ProductController::class, "destroy"]);
