<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrManPowerStatusHistory extends Model
{
    //
    protected $table="hrmanpowerstatushistory";
    protected $primaryKey="status_id";
  protected $fillable = [
    'status',
    'child_caseid',
    'EMPID',
    'updated_by',
    'updated_date',
    "Remarks"
];

}
