<?php

use App\Http\Controllers\resetController;
use App\Http\Controllers\userController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//AUTHENTIFICATION
Route::post('/registre',[userController::class,"registre"]);
Route::post('/login',[userController::class,"login"]);

//RECUPERATION MOT DE PASSE OUBLIE
Route::post('/reset_token',[resetController::class,"reset"]);
Route::post('/valid_token',[resetController::class,"valide"]);
