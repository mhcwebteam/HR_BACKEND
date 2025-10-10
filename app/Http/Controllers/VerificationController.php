<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Verification;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    public function verificationGetData(Request $request)
    {
        try 
       {
            $verifications = Verification::orderBy('created_at', 'asc')->get();
            return response()->json([
                'success' => true,
                'data'  => $verifications,
                'count' => $verifications->count(),
                "message"=>"Verfication Data Fetched Successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch verifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function recruitStore(Request $request)
{
    try {
        // ✅ 1. Validate file inputs
        $request->validate([
            '10TH_FILENAME'   => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'INTER_FILENAME'  => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'BTECH_FILENAME'  => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'AADHAR_PATH'     => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'PAN_PATH'        => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);
        // ✅ 2. Validate text inputs
        $validator = Validator::make($request->all(), [
            'NAME'         => 'required|string|max:255',
            'EMAIL'        => 'required|email|max:100',
            'PHONE_NUMBER' => 'nullable|digits_between:10,12', 
            'DOB'          => 'nullable|string|max:100',
            'ADDRESS'      => 'nullable|string|max:200',
            'AADHAR_NUM' => 'nullable|digits:12', 
            'PAN_NUM' => ['nullable','string','size:10'], 
            'SSC_SCORE' => 'nullable|integer|max:100',
            'INTER_SCORE' => 'nullable|integer|max:100',
            'BTECH_SCORE' => 'nullable|integer|max:100',
            'POST_GRADUCTION' => 'nullable|integer',
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
        // ✅ 3. Prepare data to insert
        $data = $request->only([
            'NAME', 'EMAIL', 'PHONE_NUMBER', 'DOB', 'ADDRESS',
            'AADHAR_NUM', 'PAN_NUM', 'SSC_MARKS', 'INTER_MARKS',
            'BTECH_MARKS', 'PG_MARKS', 'CURRENT_CTC',
            'EXP_CTC', 'OFFER_CTC', 'NOTICE_PERIOD',
            'PREVIOUS_COMPANY', 'DURATION', 'PAYSLIPS',
            'EXP_LETTER', 'RELIEVING_LETTER', 'HR', 'DIRECTOR', 'EVC'
        ]);
        // ✅ 4. Upload each file and store path in data array
        $fileFields = ['10TH_FILENAME', 'INTER_FILENAME', 'BTECH_FILENAME', 'AADHAR_PATH', 'PAN_PATH'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) 
            {
                $file         = $request->file($field);
                $filename     = $field . '_' . time() . '_' . $file->getClientOriginalName();
                $path         = $file->store('verification_files', 'public');
                $data[$field] = $filename; // Save filename to DB
            }
        }
        // ✅ 5. Create ONE record per candidate
        $data['status'] = 'pending';
        $verification = Verification::create($data);
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
