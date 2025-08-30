<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class EmpDetails extends Authenticatable implements JWTSubject
{
    // protected $table = 'emp_details';
    // protected $primaryKey = 'SNO';
    protected $table="mhc_employees";
    protected $primaryKey="employee_id";

    public $timestamps = false;           // Disable if not using created_at / updated_at
    public $incrementing = true;
    protected $keyType = 'int';  
             // Change to 'string' if primary key is string

    // JWT: Return the unique identifier (e.g. EMP_ID)
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // JWT: Add custom claims (optional)
    public function getJWTCustomClaims()
    {
        return [];
    }
}
