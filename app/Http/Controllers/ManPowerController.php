<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManPower;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\manPowerUpload;

class ManPowerController extends Controller
{
    public function manPowerStore( Request $request)
    {
        if (is_array($request->jobDetails)) 
        {
            foreach($request->jobDetails as $job) 
            {
                    $manPower                = new ManPower();  
                    $manPower->PLANT =        $request['loc'];
                    $manPower->RAISER=        $request['requestor'];
                    $manPower->CASEID =       $request['caseid'];
                    $manPower->RAISER_DATE =  $request['tdate'];
                  //$manPower->REQ_FOR     =  $request['requestor'];
                    $manPower->REQ_MAIL    =  $request['rmail_id'];
                    $manPower->DEPT =         $request['department'];
                    $manPower->MANPOWER_DESG =$request['jobtype'];
                    $manPower->RECRUIT_CYCLE =$request['recruitmentcycle'];
                    $manPower->POSITION    =  $request['Position'];
                    $manPower->EDUCATION   =  $request['qualf'];
                    $manPower->EXP         =  $request['exyear'];
                    $manPower->RECRUIT_FOR =  $request['hiringfor'];
                    $manPower->REPORTING =    $request['reportingto'];
                    $manPower->NUM_REQUIRE =  $request['req_pers'];
                    $manPower->TECH_SKILLS =  $request['tecskill'];
                    $manPower->SOFT_SKILLS =  $request['soft_skill'];
                    $manPower->JOB_DESC =     $request['job_descp'];
                    $manPower->REMARKS =      $request['remarks'];
                    
                    //Adding Specific rows here ----
                    $manPower->JOB_TIT      = $job['jobTitle'];
                    $manPower->REQ_BY_DT    = $job['requiredDate'];
                    $manPower->CHILD_CASEID = $job['childcaseid'];
                    $manPower->save();
                }
        }  
        return response()->json
        (
            [
             "success"=>"success",
             "status"=> 201,
             "message"=>"ManPower Data is successfully Created"
            ]
        );
    }
   
    public function manPowerUpload(Request $request)
    {
        if (!$request->hasFile('file')) 
        {
            return response()->json(['error' => 'No file uploaded.'], 400);
        }
        $file = $request->file('file');
        try 
        {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            $insertData = [];
            foreach ($rows as $index => $row) 
            {
                // Skip empty and header rows
                if ($index < 4) continue;
                // Designation is in column 3 (index 3)
                if (empty($row[3])) continue;
                $insertData[] = 
                [
                    'Plant_code'         => $request->PlantCode ?? '',
                    'Designation'        => $row[3] ?? '',
                    'Department'         => '', // You can map from column 4 if needed
                    'Total_Requirement'  => $row[5] ?? '',
                    'Availability'       => $row[6] ?? '',
                    'Actual_Requirement' => $row[7] ?? '',
                ];
            }
            if (empty($insertData)) 
            {
                return response()->json(['error' => 'No valid data found in the file.'], 400);
            }
            DB::table('man_power_upload')->insert($insertData);
            return response()->json(['message' => 'Upload successful']);
        } 
        catch (\Exception $e) 
        {
            return response()->json(['error' => 'Error processing file: ' . $e->getMessage()], 500);
        }
    } 
    public function getManPowerUploadData()
    {
        try
        {
            $getuploadData = ManPowerUpload::get();
            return response()->json([
            'status'  => 200,
            "message" => "Man Power Upload Data Fetched Successfully",
            "data"    => $getuploadData
            ]);
        }
        catch(\Exception $e)
        {
            return response()->json(
                [  "status"=>500,
                "message"=>"Internal Server Error"
                ]
                );
        }
    }



}
