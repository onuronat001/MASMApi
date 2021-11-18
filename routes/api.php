<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('basicAuth')->post('/mock/ios', function (Request $request) {
    return App\Models\iOs::validate();
});

Route::middleware('basicAuth')->post('/mock/google', function (Request $request) {
    return App\Models\Google::validate();
});


Route::post('/register', function (Request $request) {
    return App\Models\Device::create();
});

Route::post('/purchase', function (Request $request) {
    return App\Models\Subscription::create();
});

Route::post('/checkSubscription', function (Request $request) {
    return App\Models\Subscription::check();
});
