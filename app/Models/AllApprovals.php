<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllApprovals extends Model
{
    //
    protected $table="all_approvals";
    protected $primaryKey="SNO";
    protected $fillable = 
        [
         'CASEID',
         'PROCESSNAME',
         'CUR_TASK',
         'CUR_STATUS',
         'CUR_USR',
         'PREV_USR',
         'RAISER',
         'LAST_MODIFIED',
         "RAISER_DATE",
          "RECEIVED_DATE"
        ];
}
