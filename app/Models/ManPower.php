<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManPower extends Model
{
    //
    protected $table="manpower_requests";
    protected $primaryKey="SNO";
    protected $fillable = [
        'SNO',
        'CASEID',
        'CHILD_CASEID',
        'RAISER',
        'PLANT',
        'RAISER_DATE',
        'R_DT',
        'REQ_FOR',
        'REQ_MAIL',
        'DEPT',
        'MANPOWER_DESG',
        'NUM_REQUIRE',
        'POSITION',
        'REQUIRE_BY_DATE',
        'EDUCATION',
        'TECH_SKILLS',
        'SOFT_SKILLS',
        'EXP',
        'RECRUIT_FOR',
        'RECRUIT_CYCLE',
        'REPORTING',
        'REPLACEMENT_NAME',
        'JOB_DESC',
        'STATUS',
        'STATUS_DT',
        'REMARKS',
        'EMPID',
        'EVC_DT',
        'PRJ_HEAD_MAIL',
        'FUNC_HEAD_MAIL',
        'GM_NAME',
        'GM_STATUS',
        'GM_DATE',
        'PRJ_NAME',
        'PRJ_STATUS',
        'FUNC_STATUS',
        'SP_STATUS',
        'EVC_STATUS',
        'PRJ_DATE',
        'FUNC_NAME',
        'FUNC_DATE',
        'SP_NAME',
        'SP_DATE',
        'EVC_NAME',
        'EVC_DATE',
        'CUR_USR',
        'CUR_STATUS',
        'CUR_TASK',
        'CASE_SERIAL',
        'JOB_TIT',
        'REQ_BY_DT',
        'GM_REM',
        'PRJ_REM',
        'FUNC_REM',
        'SP_REM',
        'EVC_REM',
        'REV1_COMM',
        'REV2_COMM',
        'HR',
        'HO_HOD_NAME',
        'HO_HOD_DATE',
        'HO_HOD_STATUS',
        'HO_HOD_REM',
        'DEL',
        'LST_UPDT_DT'
    ];
    
public $timestamps=false;    

}
