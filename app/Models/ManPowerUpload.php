<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManPowerUpload extends Model
{
    protected $table="man_power_upload";
    protected $primaryKey="mrf_id";
    protected $fillable = [
        "Emp_Name",
        'Plant_code',
        'Designation',
        'Total_Requirement',
        'Availability',
        'Actual_Requirement',
        'Department',
    ];

}
