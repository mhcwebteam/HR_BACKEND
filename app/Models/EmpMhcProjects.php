<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpMhcProjects extends Model
{
    
    protected $table = "emp_mhc_projects";
    protected $primaryKey = "emp_mhc_projects_id";
    public $timestamps = true; 

    protected $fillable = [
        "Legacy_Id",
        "project_name",
        "created_at",
        "updated_at"
    ];

 
    public function project()
    {
        return $this->belongsTo(mhc_projects::class, 'emp_mhc_projects_id', 'project_id');
    }

      public function employee()
    {
        return $this->belongsTo(EmpDetails::class, 'Legacy_Id', 'Legacy_Id');
    }
}