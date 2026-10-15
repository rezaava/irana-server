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
 Route::get('/ttt', [\App\Http\Controllers\Api\HomeController::class, 'ttt']);
 Route::get('/search', [\App\Http\Controllers\Api\HomeController::class, 'search']);



 Route::get('/update', [\App\Http\Controllers\Api\HomeController::class, 'update']);
Route::get('/fcm', [\App\Http\Controllers\Api\AuthController::class, 'fcm']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/check-code', [\App\Http\Controllers\Api\AuthController::class, 'checkCode']);
Route::get('/cities', [\App\Http\Controllers\Api\HomeController::class, 'cities']);
Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/create', [\App\Http\Controllers\Api\HomeController::class, 'create']);
    Route::post('/change-pass', [\App\Http\Controllers\Api\AuthController::class, 'changepass']);
    Route::get('/home', [\App\Http\Controllers\Api\HomeController::class, 'home']);
    Route::get('/users', [\App\Http\Controllers\Api\HomeController::class, 'users']);
    Route::post('/delete', [\App\Http\Controllers\Api\HomeController::class, 'delete']);
    Route::post('/active', [\App\Http\Controllers\Api\HomeController::class, 'active']);
    Route::get('/list', [\App\Http\Controllers\Api\HomeController::class, 'list']);
    Route::get('/my-list', [\App\Http\Controllers\Api\HomeController::class, 'myList']);
    Route::get('/detail', [\App\Http\Controllers\Api\HomeController::class, 'detail']);
    Route::post('/accept', [\App\Http\Controllers\Api\HomeController::class, 'accept']);
    Route::get('/profile', [\App\Http\Controllers\Api\HomeController::class, 'showProfile']);
    Route::post('/profile', [\App\Http\Controllers\Api\HomeController::class, 'editProfile']);
    Route::get('/check', [\App\Http\Controllers\Api\HomeController::class, 'check']);
    Route::get('/delete-request', [\App\Http\Controllers\Api\HomeController::class, 'deleteRequest']);
    Route::get('/refresh-request', [\App\Http\Controllers\Api\HomeController::class, 'refreshRequest']);
    Route::post('/add-type', [\App\Http\Controllers\Api\HomeController::class, 'addType']);
    Route::post('/add-brand', [\App\Http\Controllers\Api\HomeController::class, 'addBrand']);
    Route::post('/delete-type', [\App\Http\Controllers\Api\HomeController::class, 'deleteType']);
    Route::post('/delete-brand', [\App\Http\Controllers\Api\HomeController::class, 'deleteBrand']);
    Route::get('/list-type', [\App\Http\Controllers\Api\HomeController::class, 'listType']);
    Route::get('/user-history', [\App\Http\Controllers\Api\HomeController::class, 'userHistory']);
    Route::get('/clear', [\App\Http\Controllers\Api\HomeController::class, 'clear']);
    Route::get('/clear/{id}', [\App\Http\Controllers\Api\HomeController::class, 'clear2']);
    Route::post('/clear', [\App\Http\Controllers\Api\HomeController::class, 'clearPost']);
    Route::get('/messages', [\App\Http\Controllers\Api\HomeController::class, 'messages']);
    Route::post('/messages', [\App\Http\Controllers\Api\HomeController::class, 'messagesPost']);
    Route::post('/message-delete', [\App\Http\Controllers\Api\HomeController::class, 'messageDelete']);
    Route::post('/end-request', [\App\Http\Controllers\Api\HomeController::class, 'endRequest']);
    Route::post('/ano', [\App\Http\Controllers\Api\HomeController::class, 'anoPost']);
    Route::get('/ano', [\App\Http\Controllers\Api\HomeController::class, 'anoGet']);
    Route::get('/ano/del/{id}', [\App\Http\Controllers\Api\HomeController::class, 'anoDelete']);
    Route::post('/clear-request', [\App\Http\Controllers\Api\HomeController::class, 'clearRequest']);
    
      
      
    
    
    Route::post('/edit-req/{id}', [\App\Http\Controllers\Api\HomeController::class, 'editReqPost']);
    Route::get('/edit/{id}', [\App\Http\Controllers\Api\HomeController::class, 'edit']);
    Route::post('/edit/{id}', [\App\Http\Controllers\Api\HomeController::class, 'editPost']);
    
    
    Route::get('/changes', [\App\Http\Controllers\Api\HomeController::class, 'registerChanges']);
    Route::post('/change', [\App\Http\Controllers\Api\HomeController::class, 'register2']);
    
      Route::post('/change-accept/{id}', [\App\Http\Controllers\Api\HomeController::class, 'registerAccept']);
      
      
      

      
      Route::get('/special', [\App\Http\Controllers\Api\HomeController::class, 'special']);
      
      
      Route::post('/special', [\App\Http\Controllers\Api\HomeController::class, 'specialPost']);
      Route::post('/special-delete', [\App\Http\Controllers\Api\HomeController::class, 'specialDelete']);
      
      
      });

      
      Route::get('/excel', [\App\Http\Controllers\Api\HomeController::class, 'excell']);
      Route::get('/specials', [\App\Http\Controllers\Api\HomeController::class, 'specials']);