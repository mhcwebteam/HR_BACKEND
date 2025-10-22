<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    use HasFactory;

    protected $table = 'verification';
    protected $primaryKey="verification_id";
    protected $fillable = [
        'NAME',
        'EMAIL', 
        'ADDRESS',
        'AADHAR_NUM',
        'AADHAR_PATH',
        'PAN_NUM',
        'CASEID',
        'CHILD_CASEID',
        'PLANT',
        'DEPT',
        'PAN_PATH',
        'SSC_MARKS',
        'INTER_MARKS',
        'BTECH_MARKS',
        '10TH_FILENAME',
        'INTER_FILENAME', 
        'BTECH_FILENAME',
        'PG_MARKS',
         'PG_PATH',
        'PHONE_NUMBER',
        'DOB',
        'CURRENT_CTC',
        'EXP_CTC',
        'OFFER_CTC',
        'NOTICE_PERIOD',
        'PREVIOUS_COMPANY',
        'DURATION',
        'PAYSLIPS',
        'EXP_LETTER',
        'RELIEVING_LETTER',
        'HR',
        'DIRECTOR',
        'EVC',
        'status',
        'remarks',
        'verified_by',
       ];

    protected $casts = [
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}