<?php
use App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $table = "emp_details";
    protected $primaryKey  = "EMP_ID";

    public function getJWTIdentifier() 
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims() 
    {
        return [];
    }
}
