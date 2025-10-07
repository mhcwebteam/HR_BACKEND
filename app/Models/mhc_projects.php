<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class mhc_projects extends Model
{
    protected $table = "mhc_projects";
    protected $primaryKey = "SNO";
    public $timestamps = true; 

    protected $fillable = [
        "project_id",
        "project_name",
        "created_at",
        "updated_at"
    ];

    // Relationship to employee projects (many-to-many through emp_mhc_projects)
    public function employeeProjects()
    {
        return $this->hasMany(emp_mhc_projects::class, 'emp_mhc_projects_id', 'project_id');
    }

    // Relationship to sub-projects
    public function subProjects()
    {
        return $this->hasMany(SubProject::class, 'project_id', 'project_id');
    }
}
