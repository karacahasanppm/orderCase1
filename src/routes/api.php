<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DiscountController;

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
        Route::get('list',[OrderController::class,'getOrders'])->middleware('auth:api');
        Route::get('',[OrderController::class,'getOrderById'])->middleware('auth:api');

    });
    Route::group([
        'prefix' => 'campaign'
    ],function () {

        Route::get('',[DiscountController::class,'getDiscountByOrderId'])->middleware('auth:api');
        Route::get('list',[DiscountController::class,'getAllDiscounts'])->middleware('auth:api');

    });

});

