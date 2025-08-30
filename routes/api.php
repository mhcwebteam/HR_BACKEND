<?php


use App\Http\Controllers\StationaryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ManPowerController;
use App\Http\Controllers\UserController;


    Route::post('register',            [AuthController::class, 'register']);
    Route::post('login'   ,            [AuthController::class, 'login'   ]);
    Route::middleware('auth:api')->group(function ()
   {
    //---------------------------<<<>>>ManPowerDetails<<<>>>------------------------------
    Route::get('profile',              [AuthController::class, 'profile']);
    Route::post('logout',              [AuthController::class, 'logout']);
    Route::get('getData',              [StationaryController::class,'getStationaryData']);
    Route::post('emp-stationary-store',[StationaryController::class,'empStationaryStore']);
    Route::post('post-data',           [StationaryController::class,'storeData']);
    Route::get('employee-data',        [EmployeeController::class,"EmployeeList"]);
    Route::get('get-users',            [UserController::class,"getUsers"]);
    Route::get('gt-stat-userId/{case_id}',[StationaryController::class,"getStatUserDataById"]);
    Route::post('subStat-updt',        [StationaryController::class,"subStatApproval"]);
    Route::post('stat-hod-aprvl',      [StationaryController::class,"StatHodApproval"]);
    Route::post('stat-hod-rejct',      [StationaryController::class,"StatHodReject"]);
    Route::post('stat-store-approval', [StationaryController::class,"statStoreApproval"]);
    Route::get('participants',         [StationaryController::class,'StatParticipantData']);
    Route::get('stationary/{caseId}/{processName}',  [StationaryController::class, 'getStationaryDataByCase']);
    Route::get('statstock',            [StationaryController::class, 'getStatStockQuantity']);

    //Combined route for both file upload and manual data entry
    Route::post('stationary-upload',   [StationaryController::class, 'combinedStationaryUpload']);

    //ManPower Routes here -----------
    Route::post('manpower-upload',     [ManPowerController::class,'manPowerUpload']);
    Route::get('manpower-upload-get',  [ManPowerController::class,'getManPowerUploadData']);
    Route::post('man-power-store',     [ManPowerController::class,"manPowerStore"]);
    Route::get( 'manpower-data/{case_id}',    [ManPowerController::class, 'getManpowerData']);
    Route::post('manpower-gm-data/{case_id}', [ManPowerController::class, 'ManpowerGMData']);
    Route::post('manpower-prj-data/{case_id}',[ManPowerController::class, 'ManpowerPRJData']);
    Route::post('manpower-func-data/{case_id}',[ManPowerController::class, 'ManpowerFUNCData']);
    Route::post('manpower-sp-data/{case_id}',  [ManPowerController::class, 'ManpowerSPData']);
    Route::post('manpower-hod-data/{case_id}',[ManPowerController::class, 'ManpowerHODData']);
    Route::post('manpower-evc-data/{case_id}',[ManPowerController::class, 'ManpowerEVCData']);
    Route::get('dept',                        [ManPowerController::class, 'getDeptAndDesg']);
    Route::post('man-power-store',            [ManPowerController::class,"manPowerStore"]);
    Route::get('hr_requisition_list',[ManPowerController::class,'ManPowerReqData']);
    Route::post('hr_status_update_history',[ManPowerController::class,'hrStatusUpdateHistory']);
    Route::post('mrf_status_history',[ManPowerController::class,'mrfStatusHistory']);
    Route::post('manpowerClose',[ManPowerController::class,'manpowerCloseStatus']);
    
    Route::post('fetch-gpnum', [ManpowerController::class, 'fetchGpnum']);
    Route::post('mrfUploadDataUpdate',[ManpowerController::class, 'mrfUploadDataUpdate']);
    Route::get('overallMrfStatusCount',[ManPowerController::class,"overallMrfStatusCount"]);

    //---------------Get the Plant Againast SubPlants Data------------------------
    Route::get('agingAnalaysisAprvls',[ManPowerController::class,"agingAnalaysisAprlvs"]);
    Route::get('agingHRAnalaysis',[ManPowerController::class,"agingHRAnalaysis"]);
    Route::get('filterOverallCount',action: [ManPowerController::class,'filterOverallCountDesgni']);
  });

