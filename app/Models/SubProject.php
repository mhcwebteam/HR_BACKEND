<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubProject extends Model
{
    protected $table = "mhc_sub_projects";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $fillable = [
        "sub_project_id",
        "project_id", 
        "project_name",
        "sub_project_name",
        "created_at",
        "updated_at"
    ];

    public function project()
    {
        return $this->belongsTo(mhc_projects::class, 'project_id', 'project_id');
    }
}