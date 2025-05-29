<?php

use App\Http\Controllers\Controller;
use App\Http\Controllers\StationaryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ManPowerController;
// use App\Http\Controllers\StationaryUploadController;
// use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Models\StationaryUpload;

Route::get('emp-stationary-data', function () {
    return response()->json(["StationaryData"=>"Fetching the StationaryData"]);
});

Route::post('register',    [AuthController::class, 'register']);
Route::post('login'   ,    [AuthController::class, 'login'   ]);


   
Route::get('hello',function(){
  return response()->json(["message"=>"message thuis"]);
});

    Route::middleware('auth:api')->group(function ()
  {
    //ManPower Details---
    Route::post('manpower-upload',[ManPowerController::class,'manPowerUpload']);
    Route::get('manpower-upload-get',[ManPowerController::class,'getManPowerUploadData']);

    Route::get('profile', [AuthController::class, 'profile']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('getData',[StationaryController::class,'getStationaryData']);
    Route::post('emp-stationary-store',[StationaryController::class,'empStationaryStore']);

    Route::post('post-data',[StationaryController::class,'storeData']);
    //Route::put('hod_approve',[StationaryController::class,"hodApprove"]);
    //Route::put('hod_reject',[StationaryController::class,"hodReject"]);
    Route::get('employee-data',[EmployeeController::class,"EmployeeList"]);
    Route::post('man-power-store',[ManPowerController::class,"manPowerStore"]);
    Route::get('get-users',[UserController::class,"getUsers"]);
    Route::get('gt-stat-userId/{case_id}',[StationaryController::class,"getStatUserDataById"]);
    Route::post('subStat-updt',[StationaryController::class,"subStatApproval"]);
    Route::post('stat-hod-aprvl',[StationaryController::class,"StatHodApproval"]);
    Route::post('stat-hod-rejct',[StationaryController::class,"StatHodReject"]);
    Route::post('stat-store-approval',[StationaryController::class,"statStoreApproval"]);
    Route::post('stat-upload',[StationaryController::class,'statUploadData']);
    Route::post('store-upload',[StationaryController::class,'StationaryUpload']);
    Route::get('participants',[StationaryController::class,'StatParticipantData']);
    Route::get('stationary/{caseId}', [StationaryController::class, 'getStationaryDataByCase']);
    });


