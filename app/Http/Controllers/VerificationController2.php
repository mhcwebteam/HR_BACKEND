<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Verification;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    public function index()
    {
        try {
            $verifications = Verification::orderBy('created_at', 'asc')->get();
            
          $transformedData = $verifications->values()->map(function ($verification, $index) {
    return [
        'id' => $index + 1,
        'SNO' => $index + 1,
        'NAME' => $verification->NAME,
        'EMAIL' => $verification->EMAIL,
        'ADDRESS' => $verification->ADDRESS,
        'PHONE_NUMBER' => $verification->PHONE_NUMBER,
        'DOB' => $verification->DOB,
        'AADHAR_NUM' => $verification->AADHAR_NUM,
        'PAN_NUM' => $verification->PAN_NUM,
        'SSC_MARKS' => $verification->SSC_MARKS,
        'INTER_MARKS' => $verification->INTER_MARKS,
        'BTECH_MARKS' => $verification->BTECH_MARKS,
        'PG_MARKS' => $verification->PG_MARKS,
        'CURRENT_CTC' => $verification->CURRENT_CTC,
        'EXP_CTC' => $verification->EXP_CTC,
        'OFFER_CTC' => $verification->OFFER_CTC,
        'NOTICE_PERIOD' => $verification->NOTICE_PERIOD,
        'PREVIOUS_COMPANY' => $verification->PREVIOUS_COMPANY,
        'DURATION' => $verification->DURATION,
        'TENTH_MARKSHEET' => $verification->TENTH_MARKSHEET,
        'INTER_MARKSHEET' => $verification->INTER_MARKSHEET,
        'BTECH_MARKSHEET' => $verification->BTECH_MARKSHEET,
        'PAYSLIPS' => $verification->PAYSLIPS,
        'EXP_LETTER' => $verification->EXP_LETTER,
        'RELIEVING_LETTER' => $verification->RELIEVING_LETTER,
        'HR'   => $verification-> HR,
        'DIRECTOR' => $verification-> DIRECTOR,
        'EVC' => $verification-> EVC,
        'status' => $verification->status ?? 'pending',
        'remarks' => $verification->remarks,
        'submitted_date' => $verification->created_at->format('Y-m-d H:i:s'),
        'verified_at' => $verification->verified_at?->format('Y-m-d H:i:s'),
    ];
});


            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'count' => $transformedData->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch verifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function store(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'NAME' => 'required|string|max:255',
            'EMAIL' => 'required|email|max:100',
            'PHONE_NUMBER' => 'nullable|digits_between:10,12', 
            'DOB' => 'nullable|string|max:100',
             'ADDRESS' => 'nullable|string|max:200',
            'AADHAR_NUM' => 'nullable|digits:12', 
            'PAN_NUM' => ['nullable','string','size:10'], 
            'SSC_MARKS' => 'nullable|integer|max:100',
            'INTER_MARKS' => 'nullable|integer|max:100',
            'BTECH_MARKS' => 'nullable|integer|max:100',
            'PG_MARKS' => 'nullable|integer',
            'CURRENT_CTC' => 'nullable|numeric',
            'EXP_CTC' => 'nullable|numeric',
            'OFFER_CTC' => 'nullable|numeric',
            'NOTICE_PERIOD' => 'nullable|integer',
            'PREVIOUS_COMPANY' => 'nullable|string|max:30',
            'DURATION' => 'nullable|integer',
            'TENTH_MARKSHEET' => 'nullable|string|max:200',
            'INTER_MARKSHEET' => 'nullable|string|max:200',
            'BTECH_MARKSHEET' => 'nullable|string|max:200',
            'PAYSLIPS' => 'nullable|string|max:200',
            'EXP_LETTER' => 'nullable|string|max:200',
            'RELIEVING_LETTER' => 'nullable|string|max:200',
               'HR'   => 'nullable|string|max:200',
        'DIRECTOR' => 'nullable|string|max:200',
        'EVC' => 'nullable|string|max:200'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $verification = Verification::create($request->all() + ['status' => 'pending']);

        return response()->json([
            'success' => true,
            'message' => 'Verification submitted successfully',
            'data' => $verification
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create verification',
            'error' => $e->getMessage()
        ], 500);
    }
}






  
}