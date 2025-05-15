<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubStationary extends Model
{
    protected $table="substationary";
    protected $primaryKey="substationary_id";
    protected $fillable =
    [
        "case_id",
        "stationary",
        "Quantity",
        "Comments",
        "sub_status"
    ];

}
