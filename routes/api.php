<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GeneralController;
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

            Route::prefix('favourite')->group(function () {
                Route::get('list', 'favouriteList');
                Route::post('create-remove', 'addToFavourite');
            });

            Route::prefix('card')->group(function () {
                Route::get('list', 'listCard');
                Route::post('add', 'addCard');
                Route::delete('delete', 'deleteCard');
                Route::post('set-as-default', 'setAsDefaultCard');
            });
        });
    });


    Route::controller(SocialMediaController::class)->group( function(){
        Route::prefix('event')->group(function () {
            Route::get('list', 'eventList');
            Route::get('type-list', 'eventTypeList');
            Route::get('detail', 'eventDetail');
            Route::post('create-comment', 'eventCreateComment');
            Route::post('join', 'eventJoin');
            Route::post('interested', 'eventInterested');

            Route::prefix('request')->group(function () {
                // Route::get('my-request', 'myRequest'); // working
                Route::post('u-request', 'createURequest');
                Route::post('shout-out-request', 'createShoutOutRequest');
            });

            Route::group(['middleware'=>'is_hat'], function(){
                Route::post('create', 'eventCreate');
                Route::post('update', 'eventUpdate');
                Route::delete('delete', 'eventDelete');

                Route::post('accept-reject-request', 'acceptRejectEventRequest');
            });
        });
    });
});
