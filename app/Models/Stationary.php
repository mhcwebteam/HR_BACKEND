<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Stationary extends Model
{
    use HasFactory;
    protected $table="stationarys";
    protected $primaryKey="case_id";
    public $incrementing=false;
    protected $keyType='string';
    protected $fillable = 
    [
        'request_for',
        'case_id',
        'name',
        'email',
        'emp_id',
        'department',
        'raiser_date',
        'current_status',
        'current_user',
        'current_task',
        'hod_name',
        'hod_status',
        'hod_aprvl_date',
        'stationary_name',
        'stationary_status',
        'stationary_aprvl_date',
        'overall_comment'
    ];


    protected static function boot()
    {
        parent::boot();
        static::creating(function ($stationary) 
        {
            $stationary->case_id = self::generateUniqueCaseId();
        });
    }

    private static function generateUniqueCaseId()
    {
        do 
        {
            $raw_uuid = Str::uuid()->toString();                  // e.g., f47ac10b-58cc-4372-a567-0e02b2c3d479
            $uuid_no_hyphens = str_replace('-', '', $raw_uuid);   // e.g., f47ac10b58cc4372a5670e02b2c3d479
            $short_uuid = substr($uuid_no_hyphens, 0, 7);         // e.g., f47ac10
            $case_id = 'CASE' . strtoupper($short_uuid);          // e.g., CASEF47AC10
        } 
        while (self::where('case_id', $case_id)->exists());      // Ensure uniqueness
          return $case_id;
    }
}
