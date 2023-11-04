<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GeneralController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\SocialMediaController;
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


Route::controller(AuthController::class)->group( function(){
    Route::prefix('auth')->group(function () {
        Route::post('login', 'login');
        Route::post('register', 'register');
        Route::post('forgot-password', 'forgotPassword');
        Route::post('verification', 'verification');
        Route::post('re-send-code', 'reSendCode');
        Route::post('social-login', 'socialLogin');
    });

    Route::get('content', 'content');

    Route::group(['middleware'=>'auth:sanctum'], function(){
        Route::prefix('auth')->group(function () {
            Route::post('logout', 'logout');   
            Route::post('update-password', 'updatePassword');
            Route::post('complete-profile', 'completeProfile');
        });
    });
});

Route::group(['middleware'=>'auth:sanctum'], function(){

    Route::controller(GeneralController::class)->group( function(){
        Route::prefix('general')->group(function () {
            Route::get('notifications-list', 'notificationList');
            Route::get('venue-type-list', 'venueTypeList');

            Route::prefix('card')->group(function () {
                Route::get('list', 'listCard');
                Route::post('add', 'addCard');
                Route::delete('delete', 'deleteCard');
                Route::post('set-as-default', 'setAsDefaultCard');
            });
        });
    });

    Route::controller(OfferController::class)->group( function(){
        Route::prefix('offer')->group(function () {
            Route::get('/', 'index');
            Route::post('create', 'create');
            Route::post('update', 'update');
            Route::delete('delete', 'delete');
        });
    });

});
