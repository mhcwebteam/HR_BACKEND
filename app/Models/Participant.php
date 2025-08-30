<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    //
    protected $table="participates";
    protected $primaryKey="SNO";
    protected $fillable = [
                'CASEID',
                'PROCESSNAME',
                'TASK_NAME',
                'ACTION_UID',
                'ACTION_STATUS',
                "RAISER",
                "TASK_COUNT",
                "CURRENT_USER",
                 "RAISER_DATE",
                 "RECEIVED_DATE"
             ];

}
