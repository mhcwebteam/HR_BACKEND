<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Verification;
use Illuminate\Support\Facades\Validator;
use App\Mail\EmployeeMail;
use App\Models\getManpowerData;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;






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


public function empVerificationData(Request $request)
{
    try {
        $getVerifyData = Verification::get();
        $responseData = [];
        foreach ($getVerifyData as $verifyData) 
        {
    
            $basePath = '/storage/verification_files/';
            $documents = [
                '10th_certi'       => $verifyData->{"10TH_FILENAME"} ? $basePath . $verifyData->{"10TH_FILENAME"} : null,
                'Inter_certi'      => $verifyData->INTER_FILENAME ? $basePath . $verifyData->INTER_FILENAME : null,
                'Gradu_certi'      => $verifyData->BTECH_FILENAME ? $basePath . $verifyData->BTECH_FILENAME : null,
                'Pg_certi'         => $verifyData->PG_FILEPATH ? $basePath . $verifyData->PG_FILEPATH : null,
                'Aadhar_certi'     => $verifyData->AADHAR_PATH ? $basePath . $verifyData->AADHAR_PATH : null,
                'Pan_certi'        => $verifyData->PAN_PATH ? $basePath . $verifyData->PAN_PATH : null,
                'Payslip'          => $verifyData->PAYSLIPS ? $basePath . $verifyData->PAYSLIPS : null,
                'Exp_Letter'       => $verifyData->EXP_LETTER ? $basePath . $verifyData->EXP_LETTER : null,
                'Relieving_Letter' => $verifyData->RELIEVING_LETTER ? $basePath . $verifyData->RELIEVING_LETTER : null,
            ];
           
            $responseData[] = [
                'name'             => $verifyData->NAME,
                'email'            => $verifyData->EMAIL,
                'caseid'           => $verifyData->CASEID,
                'child_caseid'     => $verifyData->CHILD_CASEID,
                'aadhar_number'    => $verifyData->AADHAR_NUM,
                'pan_number'       => $verifyData->PAN_NUM,
                'ssc_marks'        => $verifyData->SSC_MARKS,
                'inter_marks'      => $verifyData->INTER_MARKS,
                'btech_marks'      => $verifyData->BTECH_MARKS,
                'pg_marks'         => $verifyData->PG_MARKS,
                'phone_number'     => $verifyData->PHONE_NUMBER,
                'dob'              => $verifyData->DOB,
                'current_ctc'      => $verifyData->CURRENT_CTC,
                'expected_ctc'     => $verifyData->EXP_CTC,
                'offer_ctc'        => $verifyData->OFFER_CTC,
                'notice_period'    => $verifyData->NOTICE_PERIOD,
                'previous_company' => $verifyData->PREVIOUS_COMPANY,
                'duration'         => $verifyData->DURATION,
                'hr'               => $verifyData->HR,
                'director'         => $verifyData->DIRECTOR,
                'evc'              => $verifyData->EVC,
                'status'           => $verifyData->status,
                'remarks'          => $verifyData->remarks,
                'verified_by'      => $verifyData->verified_by,
                'verified_at'      => $verifyData->verified_at,
                'created_at'       => $verifyData->created_at,
                'updated_at'       => $verifyData->updated_at,
                'documents'        => $documents,
            ];
        }
        return response()->json([
            "status"  => 200,
            "data"    => $responseData,
            "message" => "Verification Data Fetched Successfully",
            "success" => true
        ]);
    } 
    catch (\Exception $e) 
    {
        return response()->json([
            "status"  =>  500,
            "message" =>  "Internal Server Error",
            "error"   =>  $e->getMessage()
        ]);
    }
}



public function recruitStore(Request $request)
{
    try {
     
        $request->validate([
            '10TH_FILENAME'   => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'INTER_FILENAME'  => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'BTECH_FILENAME'  => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'AADHAR_PATH'     => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'PAN_PATH'        => 'nullable|file|mimes:pdf,jpg,png|max:2048',
             'PG_PATH'        => 'nullable|file|mimes:pdf,jpg,png|max:2048',
             'PAYSLIPS'       => 'nullable|file|mimes:pdf,jpg,png|max:2048',
               'EXP_LETTER' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            'RELIEVING_LETTER' => 'nullable|file|mimes:pdf,jpg,png|max:2048'
        ]);
        // ✅ 2. Validate text inputs
        $validator = Validator::make($request->all(), [
            'CASEID'       => 'nullable|string|max:255',
             'CHILD_CASEID' =>'required|string|max:255',
             'PLANT'       => 'required|string|max:500',
            'NAME'         => 'required|string|max:255',
            'EMAIL'        => 'required|email|max:100',
            'PHONE_NUMBER' => 'nullable|digits_between:10,12', 
            'DOB'          => 'nullable|string|max:100',
            'DEPT'         => 'nullable|string|max:100',
            'ADDRESS'      => 'nullable|string|max:200',
            'AADHAR_NUM' => 'nullable|digits:12', 
            'PAN_NUM' => ['nullable','string','size:10'], 
            'SSC_MARKS' => 'nullable|integer',
            'INTER_MARKS' => 'nullable|integer',
            'BTECH_MARKS' => 'nullable|integer',
            'PG_MARKS' => 'nullable|integer',
            'CURRENT_CTC' => 'nullable|numeric',
            'EXP_CTC' => 'nullable|numeric',
            'OFFER_CTC' => 'nullable|numeric',
            'NOTICE_PERIOD' => 'nullable|integer',
            'PREVIOUS_COMPANY' => 'nullable|string',
            'DURATION' => 'nullable|integer',
            'HR'   => 'nullable|string',
            'DIRECTOR' => 'nullable|string',
            'EVC' => 'nullable|string'
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
        'CASEID', 'CHILD_CASEID', 'PLANT',  'NAME', 'EMAIL', 'PHONE_NUMBER', 'DOB', 'ADDRESS', 'DEPT',
            'AADHAR_NUM', 'PAN_NUM', 'SSC_MARKS', 'INTER_MARKS',
            'BTECH_MARKS', 'PG_MARKS', 'CURRENT_CTC',
            'EXP_CTC', 'OFFER_CTC', 'NOTICE_PERIOD',
            'PREVIOUS_COMPANY', 'DURATION', 'PAYSLIPS',
            'EXP_LETTER', 'RELIEVING_LETTER', 'HR', 'DIRECTOR', 'EVC'
        ]);

          $fileFields = [
            '10TH_FILENAME', 'INTER_FILENAME', 'BTECH_FILENAME', 'PG_PATH', 
            'AADHAR_PATH', 'PAN_PATH', 'PHOTO', 'EXP_LETTER', 'RELIEVING_LETTER','PAYSLIPS'
        ];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) 
            {
                $file         = $request->file($field);
                $filename     = $field . '_' . time() . '_' . $file->getClientOriginalName();
            $file->storeAs('verification_files', $filename, 'public');
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
public function empSendMail(Request $request){
    try
    {

              $emp_email       = $request->input("email");
        $child_caseId    = $request->input("child_caseId");
        $subject         = "Test Mail";
        $body            = "This is a simple test mail using Laravel's default mail() function.";
        $childCaseIdData = getManpowerData::where('CHILD_CASEID',$child_caseId)->first();
        $url = url('http://localhost:5173/RecruitmentForm/' . $child_caseId); 
        Mail::to($emp_email)->send(new EmployeeMail($subject, $childCaseIdData, $url));
        return "Mail Sent Successfully!";
      
    }
    catch(\Exception $e)
    {
        return response()->json(["status"=>500,'message'=>"Internal Server Error","error"=>$e->getMessage()]);
    }
}

public function verificationStatusUpdate(Request $request)
{
    try

    {
        $childCaseId=$request->input('child_caseId');
        $verification = Verification::where('CHILD_CASEID',$childCaseId)->first();
        $verification->verified_by        =  Auth::user()->Emp_Name??"";
        $verification->verification_status =  1 ;
        $verification->status             =  "verified";
        $verification->remarks             =  $request->input('remarks');
        $verification->save();

        return response()->json(['data'=>$verification,"status"=>200,"message"=>"Verification Status Updated","success"=>true]);
    }
    catch(\Exception $e)
    {
        return response()->json(["status"=>500,"message"=>"Internal Server Error","error"=>$e->getMessage()]);
    }
}


  
}
