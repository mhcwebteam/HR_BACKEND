<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Stationary;
use App\Models\AllApprovals;
use App\Models\getManpowerData;
use App\Models\StationaryUpload;
use App\Models\SubStationary;
use App\Models\Participant;
use App\Models\StatStockQuan;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Facades\Validator;
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
        $this->userCategory = Auth::user()->Emp_Category;
        $this->UserName = Auth::user()->Emp_Name;
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
            'current_user' =>  $data['hod_name'],
            'current_date' => \Carbon\Carbon::now()->format('Y-m-d h:i:s'),
            'current_status' => 'TO_DO',
            'current_task'  => ($this->userCategory == 'HOD'  ? "Stores" : 'HOD'),
            "hod_name"      => ($this->userCategory == 'HOD'  ? $this->UserName : null),
            "hod_status"    => ($this->userCategory == 'HOD'  ? "Approve" : null),
            "hod_aprvl_date" => ($this->userCategory == 'HOD' ? \Carbon\Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s') : null),
        ]);
        //AllApproval table---
        $aprvlsUpdate = new  AllApprovals();
        $aprvlsUpdate->create([
            "CASEID" => $stationaryStore->case_id,
            "PROCESSNAME" => "Stationary",
            'CUR_TASK' =>    'HOD',
            'CUR_STATUS' =>  'TO_DO',
            "RAISER"=>Auth::user()->Emp_Name,
            "RAISER_DATE"=>    $stationaryStore->raiser_date,
            "RECEIVED_DATE"=>  $stationaryStore->raiser_date,
            'CUR_USR' =>     $data['hod_name'],
            'PREV_USR' =>     Auth::user()->Emp_Name, //Raiser or Approval One here 
            'LAST_MODIFIED' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        //Particpants table here 
        $participants =  new Participant();
        $participants->create([
            "CASEID"        =>  $stationaryStore->case_id,
            "PROCESSNAME"   =>  "Stationary",
            "TASK_NAME"     =>  "Raiser",
            "RAISER_DATE"=>    $stationaryStore->raiser_date,
            "RECEIVED_DATE"=>  $stationaryStore->raiser_date,
            "ACTION_UID"    =>  Auth::user()->Emp_Name,
            "ACTION_STATUS" => "TO_DO",
            "RAISER"        =>  Auth::user()->Emp_Name,
            "created_at"    => "",
            "CURRENT_USER" => $data['hod_name'],
            "TASK_COUNT"    => 1
        ]);
        foreach ($data['items'] as $item) {
            SubStationary::create(
                [
                    'stationary' => $item['stationary'],
                    'case_id'    => $stationaryStore->case_id,
                    'Quantity'   => $item['quantity'],
                    'sub_status' => ($this->userCategory == "HOD" ? "Approve" : "TO_DO"),
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
            $allApprovalData = AllApprovals::where('CUR_USR', $user)->where('CUR_STATUS', "TO_DO")->orderBy('created_at', 'desc')->get();
            if ($userIsEmp == 2) 
            {
                $getData = Stationary::where('current_user', $user)
                    ->where("hod_status", "Approve")
                    ->where("current_status", "TO_DO")
                    ->orderBy('created_at', 'desc')
                    ->get();
            } 
            else 
            {
                $getData = Stationary::where('current_user', $user)
                    ->where("current_status", "TO_DO")
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
            // Add raiser field and received_date to each AllApprovals record by matching with Stationary data
            $allApprovalData->transform(function ($item) use ($getData, $userIsEmp) 
            {
                // Find matching stationary record by CASEID
                $stationaryRecord = $getData->where('case_id', $item->CASEID)->first();
                if ($stationaryRecord) {
                    $item->raiser      = $stationaryRecord->name;
                    $item->raiser_date = $stationaryRecord->raiser_date;
                    // Set received_date based on current task/user level
                    if ($userIsEmp == 2) {
                        // For Stores level - received date is when HOD approved
                        $item->received_date = $stationaryRecord->hod_aprvl_date;
                    } else {
                        // For HOD level - received date is when user raised the request
                        $item->received_date = $stationaryRecord->raiser_date;
                    }
                }
                 else 
                {
                    $item->raiser = '';
                    $item->raiser_date = null;
                    $item->received_date = null;
                }
                return $item;
            });
            return response()->json([
                'status' => 200,
                "data" => $getData,
                'allAprvls' => $allApprovalData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                "message" => "Internal server Error: " . $e->getMessage()
            ]);
        }
    }
    //Get RaiserData Here Expect the Pending Remaining of the Statuss here----
    public function StatParticipantData(Request $request)
    {
        try 
        {
            $user       = Auth::user()->Emp_Name;
            $userIsEmp  = Auth::user()->Is_Employee;
            $caseIdsAry = [];
            $statParticipantsData = Participant::where('ACTION_UID', $user)->orderBy('created_at', 'desc')->get();
            foreach ($statParticipantsData as $st) 
            {
                $caseIdsAry[] = $st->CASEID;
            }
            $arrayList = [];
            foreach (array_unique($caseIdsAry) as $CASEID) 
            {
                $lastRecord = Participant::where('CASEID', $CASEID)->orderByDesc('TASK_COUNT')->first();
                $lastRecord->created_at=\Carbon\Carbon::now()->format('Y-m-d H:i:s');
                $arrayList[] = $lastRecord;
            }
            return response()->json([
                "status" => 200,
                "message" => "Participant Data Fetched Successfully",
                "participantData" => $arrayList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => 500,
                "message" => "Internal Server Error: " . $e->getMessage()
            ]);
        }
    }
    public function  getStatUserDataById($case_id)
    {
        try {
            $Stationarydata    = DB::table('stationarys')->where('stationarys.case_id', $case_id)->get();
            $SubStationaryData = DB::table('substationary')->where('substationary.case_id', $case_id)->get();
            return response()->json(
                [
                    "status" => 200,
                    "message" => "Employee Data Fetched Successfully",
                    "success" => 'success',
                    'Stationary_data' => $Stationarydata,
                    "SubStationary_data" => $SubStationaryData
                ]
            );
        } 
        catch (\Exception $e) 
        {
            return response()->json(["status" => 500,"message"=>"Internal Server Error", "Fail" => "Fail"]);
        }
    }
    /*----------------------------Stationary HOD Approve Here-----------------------------*/
    public function StatHodApproval(Request $request)
    {
        try {
            $subStat = $request->input('subStat', []);
            $hod_aprvl_data = $request->input('UserData', []);
            $over_comments = $request->input('statComment', []);
            $unselectedData = $request->input('UnselectedData', []);
            if (empty($subStat)) {
                return response()->json([
                    "status" => 400,
                    "message" => "No subStat data provided.",
                    "success" => false
                ]);
            }
            $case_id = $subStat[0]['case_id'] ?? null;
            //Unselected Items Rejected
            foreach ($unselectedData as $unselect) 
            {
                $unselectedSubStat = SubStationary::where('substationary_id', $unselect['substationary_id'])->first();
                if ($unselectedSubStat) {
                    $unselectedSubStat->Quantity = $unselect['Quantity'] ?? "";
                    $unselectedSubStat->hod_comments = $unselect['hod_comments'] ?? "";
                    $unselectedSubStat->sub_status = "Reject";

                    if (!$unselectedSubStat->save()) 
                    {
                        \Log::error("❌ Failed to save SubStationary ID: " . $unselectedSubStat->substationary_id);
                    } 
                    else 
                    {
                        \Log::info("✅ Updated SubStationary ID: " . $unselectedSubStat->substationary_id);
                    }
                }
            }
            foreach ($subStat as $sub) 
            {
                $subStatUpdate = SubStationary::where('substationary_id', $sub['substationary_id'])->first();
                if ($subStatUpdate) 
                {
                    $subStatUpdate->Quantity = $sub['Quantity'] ?? "";
                    $subStatUpdate->hod_comments = $sub['hod_comments'] ?? "";
                    $subStatUpdate->sub_status = "Approve";
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
                // Check the status of all items for this case
                $totalItems = SubStationary::where('case_id', $case_id)->count();
                $approvedItems = SubStationary::where('case_id', $case_id)
                    ->where('sub_status', 'Approve')
                    ->count();
                $rejectedItems = SubStationary::where('case_id', $case_id)
                    ->where('sub_status', 'Reject')
                    ->count();
                // Determine the overall status
                $overallStatus = "Approve";
                if ($rejectedItems > 0 && $approvedItems > 0) {
                    $overallStatus = "Approve";
                } elseif ($rejectedItems === $totalItems) {
                    $overallStatus = "Reject";
                }
                $aprvlsUpdate = AllApprovals::where('CASEID', $case_id)->first();
                $aprvlsUpdate->update([
                    'CUR_TASK' => 'Store',
                    'CUR_STATUS' => 'TO_DO',
                    'CUR_USR' => "Shiva.J",
                     "RAISER" => $statStatus->name,
                    "RAISER_DATE"=>    $statStatus->raiser_date,
                    "RECEIVED_DATE"=>  $statStatus->hod_aprvl_date,
                    'PREV_USR' => Auth::user()->Emp_Name,
                    'LAST_MODIFIED' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                if ($statStatus) {
                    $statStatus->hod_name = $hod_aprvl_data['hod_name'] ?? '';
                    $statStatus->hod_status = $overallStatus; // ✅ Set appropriate status
                    $statStatus->hod_aprvl_date = now();
                    $statStatus->hod_comments = $over_comments['overall_comment'] ?? "";
                    $statStatus->current_user = "Shiva.J";
                    $statStatus->current_task =  "Store";
                    $statStatus->current_status = "TO_DO";
                    $statStatus->save();
                }
                $participants = new Participant();
                $participants->create([
                    "CASEID" => $case_id,
                    "PROCESSNAME" => "Stationary",
                    "TASK_NAME" =>   "HOD",
                    "ACTION_UID" =>   Auth::user()->Emp_Name,
                    "RAISER_DATE"=>    $statStatus->raiser_date,
                    "RECEIVED_DATE"=>  $statStatus->hod_aprvl_date,
                    "ACTION_STATUS" => "TO_DO",
                    "RAISER" =>         $statStatus->name,
                    "CURRENT_USER" => "Shiva.J",
                    "TASK_COUNT" => 2
                ]);
            }
            return response()->json([
                "status" => 200,
                "message" => "SubStatData updated successfully.",
                "success" => true
            ]);
        } catch (\Exception $e) {
            \Log::error("SubStatApproval Error: " . $e->getMessage());
            return response()->json(
            [
                "status" => 500,
                "message" => "Internal Server Error",
                "success" => false
            ]);
        }
    }
    //<----------------Stationary HOD Reject here-------------------------->
    // Fixed StatHodReject method
    public function StatHodReject(Request $request)
    {
        try {
            $subStat = $request->input('subStat', []);
            $hod_aprvl_data = $request->input('UserData', []);
            if (empty($subStat)) {
                return response()->json([
                    "status" => 400,
                    "message" => "No subStat data provided.",
                    "success" => false
                ]);
            }
            $case_id = $subStat[0]['case_id'] ?? null;
            foreach ($subStat as $sub) {
                $subStatUpdate = SubStationary::where('substationary_id', $sub['substationary_id'])->first();
                if ($subStatUpdate) {
                    $subStatUpdate->Quantity = $sub['Quantity'] ?? "";
                    $subStatUpdate->hod_comments = $sub['Comments'] ?? "";
                    $subStatUpdate->sub_status = "Reject"; // ✅ Fixed: Set to "Reject"
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
                $statStatus = Stationary::where('case_id', $case_id)->first();
                // Check if there are any approved items left
                $approvedItems = SubStationary::where('case_id', $case_id)
                    ->where('sub_status', 'Approve')
                    ->count();
                // Only proceed to store if there are approved items
                if ($approvedItems > 0) 
                {
                    $aprvlsUpdate = AllApprovals::where('CASEID', $case_id)->first();
                    $aprvlsUpdate->update([
                         'CUR_TASK' => 'Store',
                         'CUR_STATUS' => 'TO_DO',
                         'CUR_USR' => "Shiva.J",
                         "RAISER_DATE"=>    $statStatus->raiser_date,
                         "RECEIVED_DATE"=>  $statStatus->hod_aprvl_date,
                          "RAISER" => $statStatus->name,
                         'PREV_USR' => Auth::user()->Emp_Name,
                         'LAST_MODIFIED' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                    if ($statStatus) 
                    {
                        $statStatus->hod_name = $hod_aprvl_data['hod_name'] ?? '';
                        $statStatus->hod_status = "Approve"; // ✅ Set to "Partial" for mixed approval
                        $statStatus->hod_aprvl_date = now();
                        $statStatus->current_user = "Shiva.J";
                        $statStatus->current_task = "Store";
                        $statStatus->current_status = "TO_DO";
                        $statStatus->save();
                    }
                    $participants = new Participant();
                    $participants->create([
                        "CASEID" => $case_id,
                        "PROCESSNAME" => "Stationary",
                        "TASK_NAME" => "HOD",
                        "RAISER_DATE"=>    $statStatus->raiser_date,
                        "RECEIVED_DATE"=>  $statStatus->hod_aprvl_date,
                        "ACTION_UID" => Auth::user()->Emp_Name,
                        "ACTION_STATUS" => "TO_DO",
                        "RAISER" => $statStatus->name,
                        "CURRENT_USER" => "Shiva.J",
                        "TASK_COUNT" => 2
                    ]);
                } 
                else 
                {
                    // All items rejected - mark as completed
                    $aprvlsUpdate = AllApprovals::where('CASEID', $case_id)->first();
                    $aprvlsUpdate->update([
                        'CUR_TASK' => 'Store',
                        'CUR_STATUS' => 'Reject',
                        "RAISER_DATE"=>    $statStatus->raiser_date,
                        "RECEIVED_DATE"=>  $statStatus->hod_aprvl_date,
                        "RAISER" => $statStatus->name,
                        'CUR_USR' => Auth::user()->Emp_Name,
                        'PREV_USR' => Auth::user()->Emp_Name,
                        'LAST_MODIFIED' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                    $participants = new Participant();
                    $participants->create([
                        "CASEID" => $case_id,
                        "PROCESSNAME" => "Stationary",
                        "TASK_NAME" => "HOD",
                        "ACTION_UID" => Auth::user()->Emp_Name,
                        "ACTION_STATUS" => "Reject",
                        "RAISER_DATE"=>    $statStatus->raiser_date,
                          "RECEIVED_DATE"=>  $statStatus->hod_aprvl_date,
                        "RAISER" => $statStatus->name,
                        "CURRENT_USER" => "Shiva.J",
                        "TASK_COUNT" => 2
                    ]);
                    if ($statStatus) 
                    {
                        $statStatus->hod_name = $hod_aprvl_data['hod_name'] ?? '';
                        $statStatus->hod_status = "Reject"; // ✅ Set to "Reject"
                        $statStatus->hod_aprvl_date = now();
                        $statStatus->current_status = "Reject";
                        $statStatus->save();
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
            \Log::error("SubStatReject Error: " . $e->getMessage());
            return response()->json([
                "status" => 500,
                "message" => "Internal Server Error",
                "success" => false
            ]);
        }
    }
    /*------------------------------------------------StatStoreApproval--------------------------------------**/
    public function statStoreApproval(Request $request)
    {
        try 
        {
            $subStoreStat     = $request->input('subStat', []);
            $store_aprvl_data = $request->input('UserData', []);
            if (empty($subStoreStat)) {
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
                if ($subStatStoreUpdate) {
                    $subStatStoreUpdate->Quantity = $subStore['Quantity'] ?? "";
                    $subStatStoreUpdate->storeshead_comment = $subStore['Comments'] ?? "";
                    $subStatStoreUpdate->storeshead_status = "Approve";
                    if (!$subStatStoreUpdate->save()) 
                    {
                        \Log::error("❌ Failed to save SubStationary ID: " . $subStatStoreUpdate->substationary_id);
                    } 
                    else 
                    {
                        if ($subStatStoreUpdate->storeshead_status) 
                        {
                            $issuedQuan =  StatStockQuan::where('MAT', $subStore['stationary'])->first();
                            //-----------Issued Quantity-------------------------
                            $issuedQuan->ISSUED_QUAN = (int)$issuedQuan->ISSUED_QUAN + (int)$subStore['Quantity'];
                            //---------------Balance Quantity-------------------------
                            $balanceUpdate = (int)$issuedQuan->NEW_INWARD_QUAN - (int)$issuedQuan->ISSUED_QUAN;
                            $issuedQuan->update([
                                "BAL_QUAN" => $balanceUpdate,
                                "ISSUED_QUAN" => $issuedQuan->ISSUED_QUAN
                            ]);
                            \Log::info("✅ Updated SubStationary ID: " . $issuedQuan);
                        }
                        \Log::info("✅ Updated SubStationary ID: " . $subStatStoreUpdate->substationary_id);
                    }
                }
            }
            //-------------Update main Stationary approval details only once (outside the loop)
            if ($case_id) {
                $statStoreStatus = Stationary::where('case_id', $case_id)->first();
                $aprvlsUpdate =   AllApprovals::where('CASEID', $case_id)->first();
                $aprvlsUpdate->update([
                    'CUR_TASK' =>     'Store',
                    'CUR_STATUS' =>   'Approve',
                    'CUR_USR' =>      "Shiva.J",
                    'PREV_USR' =>      Auth::user()->Emp_Name, //Raiser or Approval One here 
                    'LAST_MODIFIED' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                if ($statStoreStatus) 
                {
                    $statStoreStatus->current_user     = "Shiva.J";
                    $statStoreStatus->current_task     = "Store";
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
            //------Participats Update--
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
            $updateParticipants = Participant::where('CASEID', $case_id)->get();
            foreach ($updateParticipants as $partics) 
            {
                $partics->update([
                    "ACTION_STATUS" => "Completed"
                ]);
            }
            return response()->json([
                "status" => 200,
                "message" => "SubStatData updated successfully.",
                "success" => true
            ]);
        } 
        catch (\Exception $e) 
        {
            return  response()->json(['status' => 500, "message" => "Internal Server Error", "Fail" => $e->getMessage()]);
        }
    }

    //-----------------------------------Combined--Stationery--Upload--------------------------------------------------------//
    public function combinedStationaryUpload(Request $request)
    {
        try 
        {
            $isFileUpload = $request->hasFile('file');
            if ($isFileUpload) 
            {
                $validator = Validator::make($request->all(), [
                    'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
                    'invoicedate' => 'required|date',
                ]);
                // if ($validator->fails()) {
                //     return response()->json([
                //         'status' => false,
                //         'message' => 'Validation Error',
                //         'errors' => $validator->errors(),
                //         'success' => false
                //     ], 422);
                // }
                $spreadsheet = IOFactory::load($request->file('file'));
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(null, true, true, true);
                $headerSkipped = false;
                foreach ($rows as $row) 
                {
                    if (!$headerSkipped) 
                    {
                        $headerSkipped = true;
                        continue;
                    }
                    $stationaryItem = $row['D'] ?? null;
                    $quantity = $row['E'] ?? null; 
                    if ($stationaryItem && $quantity) 
                    {
                        // Insert upload record
                        StationaryUpload::create([
                            'Invoice_Number' => $row['A'] ?? null,
                            'emp_id' => $row['B'] ?? null,
                            'emp_name' => $row['C'] ?? null,
                            'stationary_items' => $stationaryItem,
                            'quantity' => $quantity,
                            'Invoice_Date' => $request->invoicedate ?? Carbon::now()->toDateString(),
                        ]);
                        // Update stock quantity
                        StatStockQuan::where('MAT', $stationaryItem)
                            ->increment('NEW_INWARD_QUAN', $quantity);
                    }
                }
                return response()->json([
                    'status' => 200,
                    'message' => 'File uploaded and data saved successfully',
                    'success' => true
                ], 200);
            } 
            else 
            {
                // $request->validate([
                //     'invoice_number' => 'required',
                //     'invoicedate' => 'required|date',
                //     'stationary_items' => 'required|array',
                //     'stationary_items.*.name' => 'required|string',
                //     'stationary_items.*.quantity' => 'required|numeric',
                // ]);
                //  dd($request->all());
                $emp_id = Auth::user()->Legacy_Id;
                $emp_name = Auth::user()->Emp_Name;
                foreach ($request->stationary_items as $item) 
                {
                    // Insert upload record
                    StationaryUpload::create([
                        'Invoice_Number' => $request->invoice_number,
                        'emp_id' => $emp_id,
                        'emp_name' => $emp_name,
                        'stationary_items' => $item['name'],
                        'quantity' => $item['quantity'],
                        'Invoice_Date' => $request->invoicedate,
                    ]);
                    //  \Log::info("✅ Stationary updated for case_id: " , $item);

                    $issuedQuan =  StatStockQuan::where('MAT', $item['name'])->first();
                    //Issued Quantity-------
                    $issuedQuan->NEW_INWARD_QUAN = (int)$issuedQuan->NEW_INWARD_QUAN + (int)$item['quantity'];
                    //Balance Quantity--------
                    $balanceUpdate = (int)$issuedQuan->NEW_INWARD_QUAN - (int)$issuedQuan->ISSUED_QUAN;
                    $issuedQuan->update([
                        "BAL_QUAN" => $balanceUpdate,
                        "NEW_INWARD_QUAN" => $issuedQuan->NEW_INWARD_QUAN
                    ]);
                    \Log::info("✅ Updated SubStationary ID: " . $issuedQuan);
                }
                return response()->json([
                    "status" => 200,
                    "message" => "Store data saved successfully",
                    "success" => true
                ]);
            }
        } 
        catch (\Exception $e) 
        {
            \Log::error("Combined Stationary Upload Error: " . $e->getMessage());
            return response()->json([
                "status" => 500,
                "message" => "Internal Server Error: " . $e->getMessage(),
                "success" => false,
                "error" => $e->getMessage()
            ], 500);
        }
    }
    public function getStationaryDataByCase($caseId,$ProcessName)
    {
        $formattedData = null;
        try 
        {
            if($ProcessName=='Stationary')
            {
               $stationaryData = Stationary::with('subStationaryItems')
                ->where('case_id', $caseId)
                ->first(); 
                 $statFlowData = [
                'case_id'      => $stationaryData->case_id,
                'name'         => $stationaryData->name,
                'email'        => $stationaryData->email,
                'emp_id'       => $stationaryData->emp_id,
                'department' => $stationaryData->department,
                'request_for' => $stationaryData->request_for,
                'raiser_date' => $stationaryData->raiser_date,
                'hod_name' => $stationaryData->hod_name,
                'hod_status' => $stationaryData->hod_status,
                'hod_aprvl_date' => $stationaryData->hod_aprvl_date,
                'stores_name' => $stationaryData->stores_name,
                'stores_status' => $stationaryData->stores_status,
                'stores_aprvl_date' => $stationaryData->stores_aprvl_date,
                'current_user'      => $stationaryData->current_user,
                'current_task'      => $stationaryData->current_task,
                'current_status'     => $stationaryData->current_status,
                'overall_comment'    => $stationaryData->overall_comment,
                'stationary_items'   => $stationaryData->subStationaryItems->map(function ($item) {
                    return [
                            'substationary_id'   => $item->substationary_id,
                            'stationary'         => $item->stationary,
                            'quantity'           => $item->Quantity,
                            'sub_status'         => $item->sub_status,
                            'hod_comments'       => $item->hod_comments,
                            'storeshead_comment' => $item->storeshead_comment,
                            'storeshead_status'  => $item->storeshead_status
                          ];
                })->toArray()
            ];
            $formattedData = $statFlowData;
            }
            else if($ProcessName == 'Manpower')
            {
                $manPowerData = getManpowerData::where('CHILD_CASEID',$caseId)->first();
                $formattedData=$manPowerData;
            }
            else 
            {
                return response()->json([
                    'status'  => 404,
                    'message' => 'No data found for the specified case_id',
                    'data'    => null,
                    'success' => false
                ]);
            }
            //dd($formattedData);
            return response()->json([
                'status'   => 200,
                'message'  => "Stationary data for case_id {$caseId} fetched successfully",
                'data'     => $formattedData,
                'success'  => true
            ]);
        } 
        catch (\Exception $e) 
        {
            \Log::error("getStationaryDataByCase Error: " . $e->getMessage());
             return response()->json([
                'status'  => 500,
                'message' => 'Internal Server Error',
                'error'   => config('app.debug')?$e->getMessage():'Something went wrong',
                'success' => false
            ]);
        }
    }
    public function getStatStockQuantity(Request $request)
    {
        try {
            // Get all records from StatStockQuan table
            $stockItems = StatStockQuan::all();
            // Check if data exists
            if ($stockItems->isEmpty()) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'No stock quantity data found',
                    'data'    => [],
                    'success' => false
                ]);
            }
            return response()->json([
                'status' => 200,
                'message' => 'Stock quantity data fetched successfully',
                'data' => $stockItems,
                'success' => true,
                'total_records' => $stockItems->count()
            ]);
        } catch (\Exception $e) {
            \Log::error("getStatStockQuantity Error: " . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Internal Server Error',
                'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong',
                'success' => false
            ]);
        }
    }
}
