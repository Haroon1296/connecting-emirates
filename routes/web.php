<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('phpinfo', function () {
    phpinfo();
});

Route::controller(HomeController::class)->group( function(){
    Route::get('content/{type}', 'content');
});




Route::get('unauthorize', function () {
    return response()->json([
        'status' => 0, 
        'message' => 'Sorry User is Unauthorize'
    ], 401);
})->name('unauthorize');
