<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([

    'prefix' => 'v1'

], function () {

    Route::post('login', [AuthController::class,'login']);
    Route::post('register', [AuthController::class,'register']);

    Route::group([
        'prefix' => 'order'
    ],function () {

        Route::post('add',[OrderController::class,'addOrder'])->middleware('auth:api');
        Route::delete('delete',[OrderController::class,'deleteOrder'])->middleware('auth:api');

    });

});

