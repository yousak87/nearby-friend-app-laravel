<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/users', [\App\Http\Controllers\UserController::class, 'register']);
Route::post('/users/login', [\App\Http\Controllers\UserController::class, 'login']);

Route::middleware(\App\Http\Middleware\ApiAuthMiddleware::class)->group(function () {
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'getAllUser']);
    Route::get('/users/detail/{id}', [\App\Http\Controllers\UserController::class, 'getUserDetail']);
    Route::get('/users/current', [\App\Http\Controllers\UserController::class, 'getCurrentUser']);
    Route::get('/users/followers', [\App\Http\Controllers\UserController::class, 'getFollowers']);
    Route::get('/users/find-by-username', [\App\Http\Controllers\UserController::class, 'findUserByUserName']);
    Route::get('/users/find-nearby-friends', [\App\Http\Controllers\UserController::class, 'findNearbyFriends']);
    Route::post('/users/follow/{id}', [\App\Http\Controllers\UserController::class, 'follow']);
    Route::delete('/users/unfollow/{id}', [\App\Http\Controllers\UserController::class, 'unfollow']);
    Route::put('/users/{id}', [\App\Http\Controllers\UserController::class, 'updateUser']);
    Route::delete('/users/{id}', [\App\Http\Controllers\UserController::class, 'deleteUser']);
    
});
