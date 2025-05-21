<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StationaryUpload extends Model
{
    //
    protected $table="stationary_upload";
    protected $primaryKey="Invoice_Id";
    protected $fillable=[
        'Invoice_Number',
        'emp_id',
        'emp_name',
        'stationary_items',
        'quantity',
         'Invoice_Date', 
    ];

}
