<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Stationary;
use App\Models\Test;
use App\Models\AllApprovals;
use App\Models\StationaryUpload;
use App\Models\SubStationary;
use App\Models\Participant;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Log;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
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
            'current_status' => 'TO_DO',
            'current_task'  =>  ($this->userCategory=='HOD'?"Stores":'HOD'), 
            "hod_name"      =>  ($this->userCategory=='HOD'?$this->UserName:null) ,
            "hod_status"    =>  ($this->userCategory=='HOD'?"Approve":null) ,
            "hod_aprvl_date" => ($this->userCategory == 'HOD' ? \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s') : null),
            ]);
            //AllApproval table---
                $aprvlsUpdate = new  AllApprovals();
                $aprvlsUpdate->create([
                "CASEID"=>$stationaryStore->case_id,
                "PROCESSNAME"=>"Stationary",
                'CUR_TASK'=>    'HOD',
                'CUR_STATUS'=>  'TO_DO',
                'CUR_USR'=>     $data['hod_name'],
                'PREV_USR'=>     Auth::user()->Emp_Name,//Raiser or Approval One here 
                'LAST_MODIFIED'=> \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                ]);
        //Particpants table here 
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $stationaryStore->case_id,
                  "PROCESSNAME"   =>  "Stationary", 
                  "TASK_NAME"     =>  "Raiser",
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" => "TO_DO",
                  "RAISER"        =>  Auth::user()->Emp_Name,
                  "CURRENT_USER"=>$data['hod_name'],
                  "TASK_COUNT"    => 1
            ]);
        foreach ($data['items'] as $item) 
        {
            SubStationary::create(
            [  
                'stationary' => $item['stationary'],
                'case_id'    => $stationaryStore->case_id,
                'Quantity'   => $item['quantity'],
                'sub_status' => ($this->userCategory =="HOD"?"Approve":"TO_DO"),
            ]
        );
        }
        return response()->json(["success" => "Data Stored Successfully", "status" => 201]);
    }
    public function getStationaryData(Request $request)
    {
        $user      =  Auth::user()->Emp_Name;
        $userIsEmp =  Auth::user()->Is_Employee;
        try
        {
            $getData = null;
            $allApprovalData = AllApprovals::where('CUR_USR',$user)->where('CUR_STATUS',"TO_DO")->get();
            if($userIsEmp==2)
            {
              $getData = Stationary::where('current_user', $user)->where("hod_status","Approve")->where("current_status","TO_DO")->get(); 
            }
            else
            {              
                $getData = Stationary::where('current_user', $user)->where("current_status","TO_DO")->get();
            }
          return response()->json(['status'=> 200 ,"data"=> $getData,'allAprvls'=>$allApprovalData]);
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
     $caseIdsAry=[];
     $statParticipantsData = Participant::where('ACTION_UID', $user)->get();
     foreach($statParticipantsData as $st)
     {
       $caseIdsAry[]=$st->CASEID;
     }
     $arrayList=[];
     foreach($caseIdsAry as $CASEID)
     {
        $lastRecord = Participant::where('CASEID', $CASEID)->orderByDesc('TASK_COUNT')->first();
        $arrayList[] = $lastRecord;
     }
    $data = $arrayList;
    return response()->json([
        "status" => 200,
        "message" => "Participant Data Fetched Successfully",
        "participantData" => $data
    ]);
  
   }    
   catch(\Exception $e)
    {
        return response()->json(["status"=>500,"message"=>"Internal Server Error"]);
    }
 }
   public function  getStatUserDataById($case_id)
   {
    try
    {
        $Stationarydata    = DB::table('stationarys')->where('stationarys.case_id', $case_id)->get();
        $SubStationaryData = DB::table('substationary')->where('substationary.case_id', $case_id)->get();
        return response()->json(
            [ 
               "status"=>200 , "message"=> "Employee Data Fetched Successfully",
               "success"=>'success',
               'Stationary_data'=>$Stationarydata,"SubStationary_data"=>$SubStationaryData
            ]
        );
    }
    catch(\Exception $e)
     {
        return response()->json(["status"=>500,"message"=>"Internal Server Error","Fail"=>"Fail"]);
     }
   }
   /*----------------------------Stationary HOD Approve Here-----------------------------*/
   public function StatHodApproval(Request $request)
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
                   $subStatUpdate->sub_status="Approve";
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
                $aprvlsUpdate = AllApprovals::where('CASEID',$case_id)->first();
                $aprvlsUpdate->update([
                'CUR_TASK'=>    'Store',
                'CUR_STATUS'=>  'TO_DO',
                'CUR_USR'=>      "Shiva.J",
                'PREV_USR'=>     Auth::user()->Emp_Name,//Raiser or Approval One here 
                'LAST_MODIFIED'=> \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                ]);
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
               else {
                   \Log::warning("⚠️ Stationary not found for case_id: " . $case_id);
               }
           }
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Stationary", 
                  "TASK_NAME"     =>  "HOD",
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" => "TO_DO",
                  "RAISER"        =>  $statStatus->name,
                  "CURRENT_USER"=> "Shiva.J",
                  "TASK_COUNT"    => 2
            ]);
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
//<----------------Stationary HOD Reject here-------------------------->
   public function StatHodReject(Request $request)
   {
       try {
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
           if ($case_id) 
           {
                $statStatus   =   Stationary::where('case_id', $case_id)->first();
                $aprvlsUpdate =   AllApprovals::where('CASEID',$case_id)->first();
                $aprvlsUpdate->update([
                     'CUR_TASK'=>     'Store',
                     'CUR_STATUS'=>   'Reject',
                     'CUR_USR'=>      "Shiva.J",
                     'PREV_USR'=>      Auth::user()->Emp_Name,//Raiser or Approval One here 
                     'LAST_MODIFIED'=> \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                ]);
               if ($statStatus) 
               {
                   $statStatus->hod_name = $hod_aprvl_data['hod_name'] ?? '';
                   $statStatus->hod_status = "Approve";
                   $statStatus->hod_aprvl_date = now();
                   $statStatus->save();
                   \Log::info("✅ Stationary updated for case_id: " . $case_id);
               } 
               else 
               {
                   \Log::warning("⚠️ Stationary not found for case_id: " . $case_id);
               }
           }

            $participants =  new Participant();
            $participants->create([
                  "CASEID"         =>  $case_id,
                  "PROCESSNAME"    =>  "Stationary", 
                  "TASK_NAME"      =>  "HOD",
                  "ACTION_UID"     =>  Auth::user()->Emp_Name,
                   "ACTION_STATUS" => "TO_DO",
                   "RAISER"        =>  $statStatus->name,
                   "CURRENT_USER"  => "Shiva.J",
                   "TASK_COUNT"    => 2
            ]);
           return response()->json([
               "status" => 200,
               "message" => "SubStatData updated successfully.",
               "success" => true
           ]);
       } catch (\Exception $e) {
           \Log::error("SubStatApproval Error: " . $e->getMessage());
           return response()->json([
               "status" => 500,
               "message" => "Internal Server Error",
               "success" => false
           ]);
       }
   }
/*-------------------------------------StatStoreApproval--------------------------------**/
public function statStoreApproval(Request $request)
{
    try 
       {
           $subStoreStat     = $request->input('subStat', []);
           $store_aprvl_data = $request->input('UserData', []);
           if (empty($subStoreStat)) 
           {
               return response()->json([
                   "status" => 400,
                   "message" => "No subStat data provided.",
                   "success" => false
               ]);
           }
           $case_id = $subStoreStat[0]['case_id'] ?? null;
           foreach ($subStoreStat as $subStore) 
           {
               $subStatStoreUpdate = SubStationary::where('substationary_id', $subStore['substationary_id'])->first();
               if ($subStatStoreUpdate)
               {
                   $subStatStoreUpdate->Quantity = $sub['Quantity'] ?? "";
                   $subStatStoreUpdate->storeshead_comment = $sub['storeshead_comment'] ?? "";
                   $subStatStoreUpdate->storeshead_status="Approve";
                   if (!$subStatStoreUpdate->save()) 
                   {
                       \Log::error("❌ Failed to save SubStationary ID: " . $subStatStoreUpdate->substationary_id);
                   } else {
                       \Log::info("✅ Updated SubStationary ID: " . $subStatStoreUpdate->substationary_id);
                   }
                }
           }
          
           // Update main Stationary approval details only once (outside the loop)
           if ($case_id) 
           {
               $statStoreStatus = Stationary::where('case_id', $case_id)->first();
                $aprvlsUpdate =   AllApprovals::where('CASEID',$case_id)->first();
                $aprvlsUpdate->update([
                'CUR_TASK'=>     'Store',
                'CUR_STATUS'=>   'Approve',
                'CUR_USR'=>      "Shiva.J",
                'PREV_USR'=>      Auth::user()->Emp_Name,//Raiser or Approval One here 
                'LAST_MODIFIED'=> \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                ]);
               if ($statStoreStatus) 
               {
                //    $statStoreStatus->hod_name = $store_aprvl_data['hod_name'] ?? '';
                //    $statStoreStatus->hod_status = "Approve";
                //    $statStoreStatus->hod_aprvl_date = now();
                   $statStoreStatus->current_user = "Shiva.J";
                   $statStoreStatus->current_task = "Store";
                   $statStoreStatus->current_status   = "Approve";
                   $statStoreStatus->stores_name      = "Shiva.J";
                   $statStoreStatus->stores_aprvl_date = now();
                   $statStoreStatus->stores_status     = "Approve";
                   $statStoreStatus->save();
                   \Log::info("✅ Stationary updated for case_id: " . $case_id);
               } 
               else 
               {
                   \Log::warning("⚠️ Stationary not found for case_id: " . $case_id);
               }
           }

           //Participats Update--
           $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Stationary", 
                  "TASK_NAME"     =>  "Store",
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" => "Completed",
                  "RAISER"        =>  $statStoreStatus->name,
                  "CURRENT_USER"  => "Shiva.J",
                  "TASK_COUNT"    => 3
            ]);
            $updateParticipants=Participant::where('CASEID',$case_id)->get();
            foreach($updateParticipants as $partics)
            {
                $partics->update([
                    "ACTION_STATUS"=>"Completed"
                ]);
            }

           return response()->json([
               "status" => 200,
               "message" => "SubStatData updated successfully.",
               "success" => true
           ]);
       } 
    catch(\Exception $e)
    {
        return  response()->json(['status'=>500,"message"=>"Internal Server Error","Fail"=>"Fail"]);
    }
}

public function StationaryUpload(Request $request)
    {
        try {
            $request->validate([
                'invoice_number' => 'required',
                'invoicedate' => 'required|date', // Changed to match frontend field name
                'stationary_items' => 'required|array',
                'stationary_items.*.name' => 'required|string',
                'stationary_items.*.quantity' => 'required|numeric',
            ]);

            $emp_id = Auth::user()->Legacy_Id ?? Auth::user()->Emp_Id ?? 'EMP001'; // fallback for testing
            $emp_name = Auth::user()->Emp_Name ?? Auth::user()->employee ?? 'Test User';

            foreach ($request->stationary_items as $item) {
                StationaryUpload::create([
                    'Invoice_Number' => $request->invoice_number,
                    'emp_id' => $emp_id,
                    'emp_name' => $emp_name,
                    'stationary_items' => $item['name'],
                    'quantity' => $item['quantity'],
                    'Invoice_Date' => $request->invoicedate, // Changed to match frontend field name
                    // You can also store remarks if your model supports it
                    // 'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return response()->json([
                "status" => 200,
                "message" => "Store data saved successfully",
                "success" => true
            ]);
        } catch (\Exception $e) {
            Log::error("Push to Store Error: " . $e->getMessage());

            return response()->json([
                "status" => 500,
                "message" => "Internal Server Error: " . $e->getMessage(),
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }
    public function empStationaryUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'invoicedate' => 'required|date', // Changed to match frontend field name
        ]);

        if ($validator->fails()) 
        {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $user = Auth::user(); // Optional: use this if needed
            // Load spreadsheet
            $spreadsheet = IOFactory::load($request->file('file'));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $headerSkipped = false;
            foreach ($rows as $row) {
                if (!$headerSkipped) {
                    $headerSkipped = true;
                    continue;
                }
                // Insert into DB
                StationaryUpload::create([
                    'Invoice_Number' => $row['A'] ?? null,
                    'emp_id' => $row['B'] ?? null,
                    'emp_name' => $row['C'] ?? null,
                    'stationary_items' => $row['D'] ?? null,
                    'quantity' => $row['E'] ?? null,
                    'Invoice_Date' => $request->invoicedate ?? Carbon::now()->toDateString(), // Changed to match frontend field name
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'File uploaded and data saved successfully',
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            Log::error('Stationery upload error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'status' => 500,
                'message' => 'File upload failed',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
    
    public function getStationaryDataByCase($caseId)
    {
      //  dd($caseId);
        try {
            $stationaryData = Stationary::with('subStationaryItems')
                ->where('case_id', $caseId)
                ->first();

            if (!$stationaryData) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No data found for the specified case_id',
                    'data' => null,
                    'success' => false
                ]);
            }

            $formattedData = [
                'case_id' => $stationaryData->case_id,
                'name' => $stationaryData->name,
                'email' => $stationaryData->email,
                'emp_id' => $stationaryData->emp_id,
                'department' => $stationaryData->department,
                'request_for' => $stationaryData->request_for,
                'raiser_date' => $stationaryData->raiser_date,
                'hod_name' => $stationaryData->hod_name,
                'hod_status' => $stationaryData->hod_status,
                'hod_aprvl_date' => $stationaryData->hod_aprvl_date,
                'stores_name' => $stationaryData->stores_name,
                'stores_status' => $stationaryData->stores_status,
                'stores_aprvl_date' => $stationaryData->stores_aprvl_date,
                'current_user' => $stationaryData->current_user,
                'current_task' => $stationaryData->current_task,
                'current_status' => $stationaryData->current_status,
                'overall_comment' => $stationaryData->overall_comment,
                'stationary_items' => $stationaryData->subStationaryItems->map(function ($item) {
                    return [
                        'substationary_id' => $item->substationary_id,
                        'stationary' => $item->stationary,
                        'quantity' => $item->Quantity,
                        'sub_status' => $item->sub_status,
                        'hod_comments' => $item->hod_comments,
                        'storeshead_comment' => $item->storeshead_comment,
                        'storeshead_status' => $item->storeshead_status
                    ];
                })->toArray()
            ];

            return response()->json([
                'status' => 200,
                'message' => "Stationary data for case_id {$caseId} fetched successfully",
                'data' => $formattedData,
                'success' => true
            ]);
        } catch (\Exception $e) {
            \Log::error("getStationaryDataByCase Error: " . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Internal Server Error',
                'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong',
                'success' => false
            ]);
        }
    }
}





