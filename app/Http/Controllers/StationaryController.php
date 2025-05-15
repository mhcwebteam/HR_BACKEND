<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Stationary;
use App\Models\Test;
use App\Models\StationaryUpload;
use App\Models\SubStationary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;


class StationaryController extends Controller
{
    public $userIsEmp;
    public $user;
    public $userCategory;
    public $UserName;
    public function __construct()
    {
     $this->userIsEmp =  Auth::user()->Is_Employee;
     $this->userCategory=Auth::user()->Emp_Category;
     $this->UserName=Auth::user()->Emp_Name;
    }
    public function empStationaryStore(Request $request)
    {
        $data = $request->all();  // Single object, no foreach here
        $stationaryStore = Stationary::create([
            'request_for' =>  $data['request_for'],
            'name'        =>  $data['name'],
            'email'       =>  $data['email'], 
            'emp_id'      =>  $data['emp_id'],
            'department'  =>  $data['department'],
            //if the hod raise the Stationary Request here 
            //The Flow will HOD->Stores
            //If the Common user 
            'raiser_date' => \Carbon\Carbon::now()->format('Y-m-d h:i:s'),
            'current_user'=>  $data['hod_name'],
            'current_date' => \Carbon\Carbon::now()->format('Y-m-d h:i:s'),
            'current_status'   => 'TO_DO',
            'current_task'     =>($this->userCategory=='HOD'?"Stores":'HOD'), 
            "hod_name"		=>  ($this->userCategory=='HOD'?$this->UserName:null) ,
		    "hod_status"	=>  ($this->userCategory=='HOD'?"Approve":null) ,
            "hod_aprvl_date" => ($this->userCategory == 'HOD' ? \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s') : null),
	        ]);
        foreach ($data['items'] as $item) 
        {
            SubStationary::create([
                'stationary' => $item['stationary'],
                'case_id'    => $stationaryStore->case_id,
                'Quantity'   => $item['quantity'],
                'sub_status' => ($this->userCategory =="HOD"?"Approve":"TO_DO"),
            ]);
        }
        return response()->json(["success" => "Data Stored Successfully", "status" => 201]);
    }
    
    public function getStationaryData()
    {
        $user = Auth::user()->Emp_Name;
        try
        {
            $getData = null;
            if($this->userIsEmp==2)//stores-------------
            {
              $getData = Stationary::where('current_user', $user)->where("hod_status","Approve")->where("current_status","TO_DO")->get();
            }
            else // normally hod-----
            {              
                $getData = Stationary::where('current_user', $user)->where("current_status","TO_DO")->get();
            }
          return response()->json(['status'=> 200 ,"data"=> $getData]);
        }
        catch(\Exception)
        {
           return response()->json(
             [
              'status'  =>500,
              "message"=>"Internal server Error"
             ]
            );
        }
    }

    //Get RaiserData Here Expect the Pending Remaining of the Statuss here----
 public function StatParticipantData(Request $request)
 {
    try
  {
     $user = Auth::user()->Emp_Name;
    // Get requests raised by the logged-in user
    $statParticipantsData = Stationary::where('name', $user)->get();
    // Get requests where the logged-in user is the current handler
    $statUnderEmpsData = Stationary::where('current_user', $user)->get();
    // Merge both collections into one
    $mergedData = $statParticipantsData->merge($statUnderEmpsData);
    return response()->json([
        "status" => 200,
        "message" => "User Data Fetched Successfully",
        "data" => $mergedData
    ]); 
   }    
   catch(\Exception $e)
    {
        return response()->json(["status"=>500,"message"=>"Internal Server Error","error"=>$e->message]);
    }
 }
   public function  getStatUserDataById($case_id)
   {
    try
    {
        $Stationarydata = DB::table('stationarys')->where('stationarys.case_id', $case_id)->get();
        $SubStationaryData=DB::table('substationary')->where('substationary.case_id', $case_id)->get();
        return response()->json(
            ["status"=>200,"message"=>"Employee Data Fetched Successfully",
             "success"=>'success',
             'Stationary_data'=>$Stationarydata,"SubStationary_data"=>$SubStationaryData]
        );
    }
    catch(\Exception $e)
     {
        return response()->json(["status"=>500,"message"=>"Internal Server Error","Fail"=>"Fail"]);
     }
   }
/*---------------------------------Stationary HOD Approve Here------------------------------*/
   public function StatHodApproval(Request $request)
   {
       try 
       {
           $subStat = $request->input('subStat',[]);
           $hod_aprvl_data = $request->input('UserData',[]);
           if (empty($subStat)) 
           {
               return response()->json([
                   "status" => 400,
                   "message" => "No subStat data provided.",
                   "success" => false
               ]);
           }
           $case_id = $subStat[0]['case_id'] ?? null;
           if(!($this->userIsEmp == 2))
           {
                    foreach ($subStat as $sub) 
                    {
                        $subStatUpdate = SubStationary::where('substationary_id', $sub['substationary_id'])->first();
                        if ($subStatUpdate) 
                        {
                            $subStatUpdate->Quantity = $sub['Quantity'] ?? "";
                            $subStatUpdate->hod_comments = $sub['Comments'] ?? "";
                            $subStatUpdate->sub_status="Approve";
                            if (!$subStatUpdate->save()) 
                            {
                                \Log::error("❌ Failed to save SubStationary ID: " . $subStatUpdate->substationary_id);
                            } else 
                            {
                                \Log::info("✅ Updated SubStationary ID: " . $subStatUpdate->substationary_id);
                            }
                        }
                    }
                    // Update main Stationary approval details only once (outside the loop)
                    if ($case_id) 
                    {
                        $statStatus = Stationary::where('case_id', $case_id)->first();
                        if ($statStatus) 
                        {
                            $statStatus->hod_name = $hod_aprvl_data['hod_name'] ?? '';
                            $statStatus->hod_status = "Approve";
                            $statStatus->hod_aprvl_date = now();
                            $statStatus->current_user = "Shiva.J";
                            $statStatus->current_task = "Store";
                            $statStatus->current_status = "TO_DO";
                            $statStatus->save();
                            \Log::info("✅ Stationary updated for case_id: " . $case_id);
                        } 
                        else 
                        {
                            \Log::warning("⚠️ Stationary not found for case_id: " . $case_id);
                        }
                    }
           }
           else
           {
                  foreach ($subStat as $sub) 
                    {
                        $subStatUpdate = SubStationary::where('substationary_id', $sub['substationary_id'])->first();
                        if ($subStatUpdate) 
                        {
                            $subStatUpdate->Quantity = $sub['Quantity'] ?? "";
                             $subStatUpdate->sub_status = "Completed";
                            $subStatUpdate->storeshead_comment = $sub['storeshead_comment'] ?? "";
                            $subStatUpdate->store_status="Approve";
                            if (!$subStatUpdate->save()) 
                            {
                                \Log::error("❌ Failed to save SubStationary ID: " . $subStatUpdate->substationary_id);
                            } else {
                                \Log::info("✅ Updated SubStationary ID: " . $subStatUpdate->substationary_id);
                            }
                        }
                    }
                    // Update main Stationary approval details only once (outside the loop)
                    if ($case_id) 
                    {
                        $statStatus = Stationary::where('case_id', $case_id)->first();
                        if ($statStatus) 
                        {
                            $statStatus->stores_name = $hod_aprvl_data['hod_name']??'';
                            $statStatus->stores_status = "Approve";
                            $statStatus->stores_aprvl_date = now();
                            $statStatus->current_user = "Shiva.J";
                            $statStatus->current_task = "Store";
                            $statStatus->current_status = "Approve";
                            $statStatus->save();
                            \Log::info("✅ Stationary updated for case_id: " . $case_id);
                        } else 
                        {
                            \Log::warning("⚠️ Stationary not found for case_id: " . $case_id);
                        }
                    }
           }
           return response()->json([
               "status" => 200,
               "message" => "SubStatData updated successfully.",
               "success" => true
           ]);
       }
        catch (\Exception $e) 
       {
           \Log::error("SubStatApproval Error: " . $e->getMessage());
   
           return response()->json([
               "status" => 500,
               "message" => "Internal Server Error",
               "success" => false
           ]);
       }
   } 
//<---------------------------Stationary HOD Reject here------------------------------------------->
   public function StatHodReject(Request $request)
   {
       try 
       {
           $subStat = $request->input('subStat', []);
           $hod_aprvl_data = $request->input('UserData', []);
           if (empty($subStat)) 
           {
               return response()->json([
                   "status" => 400,
                   "message" => "No subStat data provided.",
                   "success" => false
               ]);
           }
           $case_id = $subStat[0]['case_id'] ?? null;
           foreach ($subStat as $sub) 
           {
               $subStatUpdate = SubStationary::where('substationary_id', $sub['substationary_id'])->first();
               if ($subStatUpdate) 
               {
                   $subStatUpdate->Quantity = $sub['Quantity'] ?? "";
                   $subStatUpdate->hod_comments = $sub['Comments'] ?? "";
                   $subStatUpdate->sub_status="Reject";
                   if (!$subStatUpdate->save()) 
                   {
                       \Log::error("❌ Failed to save SubStationary ID: " . $subStatUpdate->substationary_id);
                   } 
                   else 
                   {
                       \Log::info("✅ Updated SubStationary ID: " . $subStatUpdate->substationary_id);
                   }
               }
           }
           // Update main Stationary approval details only once (outside the loop)
           if ($case_id) {
               $statStatus = Stationary::where('case_id', $case_id)->first();
               if ($statStatus) {
                   $statStatus->hod_name = $hod_aprvl_data['hod_name'] ?? '';
                   $statStatus->hod_status = "Approve";
                   $statStatus->hod_aprvl_date = now();
                   $statStatus->save();
                   \Log::info("✅ Stationary updated for case_id: " . $case_id);
               } else {
                   \Log::warning("⚠️ Stationary not found for case_id: " . $case_id);
               }
           }
           return response()->json([
               "status" => 200,
               "message" => "SubStatData updated successfully.",
               "success" => true
           ]);
       } catch (\Exception $e) 
       {
           \Log::error("SubStatApproval Error: " . $e->getMessage());
           return response()->json([
               "status" => 500,
               "message" => "Internal Server Error",
               "success" => false
           ]);
       }
   }
public function statUploadData(Request $request)
{
    try
    {
       if (!$request->hasFile('file')) 
        {
            return response()->json(['error' => 'No file uploaded.'], 400);
        }
        $file = $request->file('file');
        try 
        {
            if($file)
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
            }
            else
            {
                foreach($request->stationary_items as $statItem)
                {
                    $statUploadData = new StationaryUpload();
                    $statUploadData->Invoice_Number=$request->Invoice_Number;
                    $statUploadData->emp_id=Auth::user()->Legacy_Id;
                    $statUploadData->emp_name=Auth::user()->Emp_Name;
                    $statUploadData->stationary_items=$statItem[''];
                    $statUploadData->quantity=$statItem['quantity'];
                }

            }
            return response()->json(['message' => 'Upload successful']);
        } 
        catch (\Exception $e) 
        {
            return response()->json(['error' => 'Error processing file: ' . $e->getMessage()], 500);
        }
    }
    catch(\Exception $e)
    {
        return response()->json(["status"=>500,"message"=>"Internal Server Error","error"=>$e]);
    }
}

}

