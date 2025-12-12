<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\BidController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/show', [AuthController::class, 'show']);
    Route::put('/update', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Items
    Route::post('/items', [ItemController::class, 'store']);
    Route::get('/items', [ItemController::class, 'index']);
    Route::put('/items/{item}', [ItemController::class, 'update']);
    Route::delete('/items/{item}', [ItemController::class, 'destroy']);
    Route::post('/items/{item}/close', [ItemController::class, 'close']);

    // Bid
    Route::post('/items/{item}/bid', [BidController::class, 'store']);
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'env'    => app()->environment(),
    ]);
});