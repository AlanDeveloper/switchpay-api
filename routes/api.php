<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post("/register", [AuthController::class, "register"]);
Route::post("/login", [AuthController::class, "login"]);

Route::middleware("auth:sanctum")->group(function () {
    Route::get("/user", function (Request $request) {
        return $request->user();
    });

    Route::post("/logout", [AuthController::class, "logout"]);
});

Route::get("/product", [ProductController::class, "index"]);
Route::get("/product/{id}", [ProductController::class, "show"]);
Route::post("/product", [ProductController::class, "store"]);
Route::put("/product/{id}", [ProductController::class, "update"]);
Route::delete("/product/{id}", [ProductController::class, "destroy"]);
