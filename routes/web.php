<?php

use Illuminate\Support\Facades\Route;

// //ROUTE POUR API
// Route::prefix("api")->group(function(){
//   Route::middleware("auth:sanctum")->group(function(){
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });
//   });

//   Route::post('/registre',[userController::class,"registre"]);
//   Route::post('/login',[userController::class,"login"]);
// });


//ROUTE POUR LE WEB
Route::get('/', function () {
    return view('welcome');
});
