<?php

namespace App\Http\Controllers;

use App\Models\AllApprovals;
use App\Models\getManpowerData;
use App\Models\HrManPowerStatusHistory;
use Exception;
use Illuminate\Http\Request;
use App\Models\ManPower;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\manPowerUpload;
use App\Models\Participant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ManPowerController extends Controller
{
     public function fetchGpnum(Request $request)
    {
        $selectedPlant = trim($request->input('plant'));
        if (empty($selectedPlant)) {
            return response()->json('');
        }
        $inward = "1000001";
        $plantCode = substr($selectedPlant, 0, 4);
        $result = DB::table('manpower_requests')
            ->selectRaw('MAX(CASEID) as max')
            ->where('CASEID', 'like', $plantCode . '%')
            ->first();
        if (!empty($result->max)) {
            $gpnum = intval($result->max) + 1;
        } else {
            $gpnum = $plantCode . $inward;
        }
        return response()->json($gpnum);
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
                    "Emp_Name"           => Auth::user()->Emp_Name,
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
          $getuploadData = ManPowerUpload::orderBy('Plant_code', 'desc')->get();
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
    public function getManpowerData($case_id){
        try 
        {
        $data = getManpowerData::where('CHILD_CASEID', $case_id)->first();
        if (!$data) 
        {
            return response()->json(['message' => 'Data not found'], 404);
        }
           return response()->json($data);
        } 
        catch (\Exception $e) 
        {
            return response()->json(['message' => 'Server Error', 'error' => $e->getMessage()], 500);
        }
    }
    public function getDeptAndDesg()
    {
        $departments = DB::table('pmt_manpower_fields')
            ->select('DEPT')
            ->distinct()
            ->orderBy('DEPT')
            ->get();
        $designations = DB::table('pmt_manpower_fields')
            ->select('DESIG')
            ->distinct()
            ->orderBy('DESIG')
            ->get();
        return response()->json([
            'departments' => $departments??null,
            'designations' => $designations??null
        ]);
    }
    public function manPowerStore( Request $request)
    {
         $cur_usr = '';
         $cur_task = '';
         $loc = (string) $request->loc;
            $plant_code = substr($loc, 0, 4);
            \Log::info("✅ Updated LOC: " . $plant_code);

        if($plant_code == '2000' || $plant_code == '2150' || $plant_code == '2250')
        {
            $dept = $request->department;
            $dept_data = DB::table('pmt_MANPOWER_FIELDS')
            ->where('DEPT', $dept)
            ->first();
            if($dept_data)
            {
                $HO_HOD_USR = $dept_data->HO_HOD_USR ?? '';
            }
            $cur_usr = $HO_HOD_USR;
            $cur_task = 'HOD';
        }
        else
        {
            // Step 1: Fetch SP_USR and PRJ_USR based on plant (loc)
            $usersData = DB::table('pmt_MANPOWER_PLANT_USERS')
            ->where('PLANT', $plant_code)
            ->first();
            // Step 2: Handle null fallback safely
            $SP_USR  = $usersData->SP_USR ?? 'default_user';
            $PRJ_USR = $usersData->PRJ_USR ?? null;
            if($SP_USR ==  "Manisha.N" )
            {
                $cur_usr = 'siddardha.n';
                $cur_task = 'GM';
            }
            else if($SP_USR=="Siddardha.N")
            {
                $cur_usr = $PRJ_USR;
                $cur_task = 'PRJ_HEAD';
            }
        }
        if (is_array($request->jobDetails))
        {
            foreach($request->jobDetails as $job)
            {
                \Log::info("requestData:".json_encode($job));
                    $manPower               = new ManPower();
                    $manPower->PLANT        = $request['loc'];
                    $manPower->RAISER       = Auth::user()->Emp_Name;
                    $manPower->Replacing_Emp= $request['replaced_emp'];
                    $manPower->CASEID =       $request['caseid'];
                    $manPower->RAISER_DATE =  $request['tdate'];
                    $manPower->REQ_MAIL    =  $request['rmail_id'];
                    $manPower->DEPT =         $request['department'];
                    $manPower->MANPOWER_DESG =$request['jobtype'];
                    $manPower->RECRUIT_CYCLE =$request['recruitmentcycle'];
                    $manPower->POSITION    =  $request['Position'];
                    $manPower->EDUCATION   =  $request['qualf'];
                    $manPower->EXP         =  $request['exyear'];
                    $manPower->RECRUIT_FOR =  $request['hiringfor'];
                    $manPower->REPORTING   =  $request['reportingto'];
                    $manPower->NUM_REQUIRE =  $request['req_pers'];
                    $manPower->TECH_SKILLS =  $request['tecskill'];
                    $manPower->SOFT_SKILLS =  $request['soft_skill'];
                    $manPower->JOB_DESC    =  $request['job_descp'];
                    $manPower->REMARKS     =  $request['remarks'];
                    $manPower->CUR_TASK    =  $cur_task;
                    $manPower->CUR_STATUS  =  'TO_DO';
                    $manPower->STATUS      =  'TO_DO';
                    $manPower->CUR_USR     =  $cur_usr;
                    //Adding Specific rows here ----
                    $manPower->JOB_TIT      = $job['jobTitle'];
                    $manPower->REQ_BY_DT    = $job['requiredDate'];
                    $manPower->CHILD_CASEID = $job['childcaseid'];
                    $manPower->save();
                    //all_approvals
                    AllApprovals::create([
                    'CASEID'         =>      $job['childcaseid'],
                    'PROCESSNAME'    =>      "Manpower",
                    'CUR_TASK'       =>       $cur_task,
                    "RAISER"         =>       $manPower->RAISER,
                    'CUR_STATUS'     =>       'TO_DO',
                    "RAISER_DATE"    =>       \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    "RECEIVED_DATE"  =>       \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'CUR_USR'        =>        $cur_usr,
                    'PREV_USR'       =>        Auth::user()->Emp_Name,//Raiser or Approval One here
                    'LAST_MODIFIED'  =>        \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                    //Particpants table here
                $participants =  new Participant();
                $participants->create([
                    "CASEID"        =>  $job['childcaseid'],
                    "PROCESSNAME"   =>  "Manpower",
                    "TASK_NAME"     =>  "Raiser",
                     "RAISER_DATE"  =>  \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    "RECEIVED_DATE" =>   \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    "ACTION_UID"    =>  Auth::user()->Emp_Name,
                    "ACTION_STATUS" => "TO_DO",
                    "RAISER"        =>  $manPower->RAISER,
                    "CURRENT_USER"  =>  $cur_usr,
                    "TASK_COUNT"    =>  1
                ]);
                }
        }
        return response()->json
        (
            [
             "success"=>true,
             "status"=> 201,
             "message"=>"ManPower Created Successfully"
            ]
        );
    }

    public function ManpowerGMData(Request $request, $case_id)
    {
        // Validate incoming data
        $data = $request->validate([
            'approve'   => 'required|string',
            'cur_tas'   => 'required|string',
            'department'=> 'required|string', //✅Needed for your logic
        ]);
        if ($data['approve'] === 'Approve') 
        {
            $cur_tas = $data['cur_tas'];
            $loc = (string) $request->loc;
            $plant_code = substr($loc, 0, 4);
            $usersData = DB::table('pmt_MANPOWER_PLANT_USERS')
                ->where('PLANT', $plant_code)
                ->first();
            $PRJ_USR = $usersData->PRJ_USR ?? null;
            //Find the record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            //Update approval status + remarks
            $mp->GM_STATUS = $data['approve'];
            $mp->GM_DATE = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->GM_REM = $request->input('gmremarks');
            $mp->CUR_TASK = 'PRJ_HEAD';
            $mp->CUR_USR = $PRJ_USR;
            $mp->CUR_STATUS = 'TO_DO';
            $mp->save();
            // ✅ Update ALL_APPROVALS
            DB::table('ALL_APPROVALS')
                ->where('CASEID', $case_id)
                ->update([
                     'CUR_TASK'        => 'PRJ_HEAD',
                     'CUR_STATUS'      => 'TO_DO',
                     "RAISER_DATE"     =>    $mp->RAISER_DATE,
                     "RAISER"          =>    $mp->RAISER,
                     "RECEIVED_DATE"   =>   $mp->RAISER_DATE,
                     'CUR_USR'         =>   $PRJ_USR,
                     'LAST_MODIFIED'   =>   \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                     'PREV_USR'        =>   Auth::user()->Emp_Name,
                ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  "PRJ_HEAD",
                   "RAISER_DATE"  =>   $mp->RAISER_DATE,
                   "RECEIVED_DATE"=>   $mp->RAISER_DATE,
                  "ACTION_UID"    =>   Auth::user()->Emp_Name,
                  "ACTION_STATUS" =>   "TO_DO",
                  "RAISER"        =>   $mp->RAISER,
                  "CURRENT_USER"  =>   $PRJ_USR,
                  "TASK_COUNT"    =>   $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval submitted successfully.',
                'case_id' => $case_id,
            ], 200);
        }
        elseif($data['approve'] === 'Reject')
        {
             // Find the record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update approval status + remarks
            $mp->GM_STATUS  = $data['approve'];
            $mp->GM_DATE    = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->GM_REM     = $request->input('gmremarks');
            $mp->CUR_TASK   = 'GM';
            $mp->CUR_USR    = Auth::user()->Emp_Name;
            $mp->CUR_STATUS = 'COMPLETED';
            $mp->save();
            // ✅ Update ALL_APPROVALS
            DB::table('ALL_APPROVALS')
                ->where('CASEID', $case_id)
                ->update([
                    'CUR_TASK'      => 'GM',
                    'CUR_STATUS'    => 'COMPLETED',
                     "RAISER"       =>  $mp->RAISER,
                    "RAISER_DATE"   =>  $mp->RAISER_DATE,
                    "RECEIVED_DATE" =>  $mp->RAISER_DATE,
                    'CUR_USR'       =>  Auth::user()->Emp_Name,
                    'LAST_MODIFIED' =>  \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'PREV_USR'      =>  Auth::user()->Emp_Name,
                ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  "GM",
                   "RAISER_DATE"  => $mp->RAISER_DATE,
                   "RECEIVED_DATE"=>  $mp->RAISER_DATE,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" => "COMPLETED",
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  => Auth::user()->Emp_Name,
                  "TASK_COUNT"    => 2
            ]);
            return response()->json(
            [
                'message' => 'Approval was not YES; no further action taken.',
                'case_id' => $case_id,
            ], 200);
        }
    }
    public function ManpowerPRJData(Request $request, $case_id)
    {
        // Validate incoming data
        $data = $request->validate([
            'approve'   => 'required|string',
            'cur_tas'   => 'required|string',
            'department'=> 'required|string', // ✅ Needed for your logic
        ]);
        if ($data['approve'] === 'Approve') 
            {
            $cur_tas = $data['cur_tas'];
            $dept_data = DB::table('pmt_MANPOWER_FIELDS')
                    ->where('DEPT', $request->department)
                    ->first();
                $FUNC_USR = $dept_data->FUNC_USR ?? '';
                if($FUNC_USR !='')
                {
                    $cur_user = $FUNC_USR;
                    $cur_task = 'FUNC_HEAD';
                }
                else
                {
                    $plantCode = substr($request->loc, 0, 4);
                    $usersData = DB::table('pmt_MANPOWER_PLANT_USERS')
                       ->where('PLANT', $plantCode)
                       ->first();
                        $SP_USR = $usersData->SP_USR ?? null;
                        $cur_user = $SP_USR;
                        \Log::info('loc-'.$plantCode);
                        \Log::info('cur_user-'.$cur_user);
                        if( $SP_USR=='jsrao')
                        {
                            $cur_task = 'DIRECTOR';
                        }
                        else
                        {
                            $cur_task = 'SP';
                        }   
                }
            // Find the record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update approval status + remarks
            $mp->PRJ_STATUS = $data['approve'];
            $mp->PRJ_DATE = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->PRJ_REM = $request->input('prj_headremarks');
            $mp->CUR_TASK = $cur_task;
            $mp->CUR_USR = $cur_user;
            $mp->CUR_STATUS = 'TO_DO';
            $mp->save();
            // ✅ Update ALL_APPROVALS
            DB::table('ALL_APPROVALS')
                ->where('CASEID', $case_id)
                ->update([
                    'CUR_TASK'        => $cur_task,
                    'CUR_STATUS'      => 'TO_DO',
                    'CUR_USR'         => $cur_user,
                    "RAISER_DATE"     => $mp->RAISER_DATE,
                    "RECEIVED_DATE"  => $mp->GM_DATE,
                    "RAISER"        => $mp->RAISER,
                    'LAST_MODIFIED'   => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'PREV_USR'        => Auth::user()->Emp_Name,
                ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  $cur_task,
                   "RAISER_DATE"  =>   $mp->RAISER_DATE,
                   "RECEIVED_DATE"=>  $mp->GM_DATE,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" => "TO_DO",
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  => $cur_user,
                  "TASK_COUNT"    => $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval submitted successfully.',
                'case_id' => $case_id,
            ], 200);
        }
        elseif($data['approve'] === 'Reject')
        {
             //------======= Find the record---------------------------
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update approval status + remarks
            $mp->GM_STATUS = $data['approve'];
            $mp->GM_DATE = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->GM_REM = $request->input('prj_headremarks');
            $mp->CUR_TASK = 'PRJ_HEAD';
            $mp->CUR_USR = Auth::user()->Emp_Name;
            $mp->CUR_STATUS = 'COMPLETED';
            $mp->save();
            // ✅ Update ALL_APPROVALS
                DB::table('ALL_APPROVALS')
                    ->where('CASEID', $case_id)
                    ->update([
                        'CUR_TASK'       => 'PRJ_HEAD',
                        'CUR_STATUS'      => 'COMPLETED', 
                        "RAISER_DATE"=>    $mp->RAISER_DATE,
                        "RECEIVED_DATE"=>  $mp->GM_DATE,
                         "RAISER"        =>   $mp->RAISER,
                        'CUR_USR'     => Auth::user()->Emp_Name,
                        'LAST_MODIFIED' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                        'PREV_USR'   => Auth::user()->Emp_Name,
                    ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  "PRJ_HEAD",
                  "RAISER_DATE"=>    $mp->RAISER_DATE,
                  "RECEIVED_DATE"=>  $mp->GM_DATE,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" => "COMPLETED",
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  =>  Auth::user()->Emp_Name,
                  "TASK_COUNT"    =>  $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval was not YES; no further action taken.',
                'case_id' => $case_id,
            ], 200);
        }
    }
    //--------------------------------FUNC DATA--------------------------------------->
    public function ManpowerFUNCData(Request $request, $case_id)
    {
        // Validate incoming data
        $data = $request->validate([
            'approve'   => 'required|string',
            'cur_tas'   => 'required|string',
            'department'=> 'required|string', // ✅ Needed for your logic
        ]);
        if ($data['approve'] === 'Approve') {
            $cur_tas = $data['cur_tas'];
            // $dept_data = DB::table('pmt_MANPOWER_FIELDS')
            //         ->where('DEPT', $request->department)
            //         ->first();
            //     $FUNC_USR = $dept_data->FUNC_USR ?? '';
            //     if($FUNC_USR !='')
            //     {
            //         $cur_user = $FUNC_USR;
            //         $cur_task = 'FUNC_HEAD';
            //     }
            //     else
            //     {
            
            $loc = (string) $request->loc;
            $plant_code = substr($loc, 0, 4);
                    $usersData = DB::table('pmt_MANPOWER_PLANT_USERS')
                       ->where('PLANT', $plant_code)
                       ->first();
                       $SP_USR = $usersData->SP_USR ?? null;
                        $cur_user = $SP_USR;
                        if( $SP_USR=='jsrao')
                        {
                            $cur_task = 'DIRECTOR';
                        }
                        else
                        {
                            $cur_task = 'SP';
                        }   
                // }
            // Find the record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update approval status + remarks
            $mp->FUNC_STATUS = $data['approve'];
            $mp->FUNC_DATE = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->FUNC_REM = $request->input('func_headremarks');
            $mp->CUR_TASK = $cur_task;
            $mp->CUR_USR = $cur_user;
            $mp->CUR_STATUS = 'TO_DO';
            $mp->save();
            // ✅ Update ALL_APPROVALS
            DB::table('ALL_APPROVALS')
                ->where('CASEID', $case_id)
                ->update([
                    'CUR_TASK' => $cur_task,
                    'CUR_STATUS'      => 'TO_DO',
                    'CUR_USR'     => $cur_user,
                    "RAISER_DATE"=>    $mp->RAISER_DATE,
                     "RECEIVED_DATE"=>  $mp->PRJ_DATE,
                      "RAISER"        =>   $mp->RAISER,
                    'LAST_MODIFIED' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'PREV_USR'   => Auth::user()->Emp_Name,
                ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  $cur_task,
                   "RAISER_DATE"=>    $mp->RAISER_DATE,
                   "RECEIVED_DATE"=>  $mp->PRJ_DATE,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" => "TO_DO",
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  => $cur_user,
                  "TASK_COUNT"    => $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval submitted successfully.',
                'case_id' => $case_id,
            ], 200);
        }
        elseif($data['approve'] === 'Reject')
        {
             //------======= Find the record---------------------------
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update approval status + remarks
            $mp->FUNC_STATUS = $data['approve'];
            $mp->FUNC_DATE = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->FUNC_REM = $request->input('func_headremarks');
            $mp->CUR_TASK = 'FUNC_HEAD';
            $mp->CUR_USR = Auth::user()->Emp_Name;
            $mp->CUR_STATUS = 'COMPLETED';
            $mp->save();
            // ✅ Update ALL_APPROVALS
                DB::table('ALL_APPROVALS')
                    ->where('CASEID', $case_id)
                    ->update([
                        'CUR_TASK'       => 'FUNC_HEAD',
                        'CUR_STATUS'      => 'COMPLETED', 
                        "RAISER_DATE"=>    $mp->RAISER_DATE,
                        "RECEIVED_DATE"=>  $mp->PRJ_DATE,
                         "RAISER"        =>   $mp->RAISER,
                        'CUR_USR'     => Auth::user()->Emp_Name,
                        'LAST_MODIFIED' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                        'PREV_USR'   => Auth::user()->Emp_Name,
                    ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  "FUNC_HEAD",
                  "RAISER_DATE"=>    $mp->RAISER_DATE,
                  "RECEIVED_DATE"=>  $mp->PRJ_DATE,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" => "COMPLETED",
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  =>  Auth::user()->Emp_Name,
                  "TASK_COUNT"    =>  $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval was not YES; no further action taken.',
                'case_id' => $case_id,
            ], 200);
        }
    }
    //-------------------------------------------SP DATA--------------------------------------->
    public function ManpowerSPData(Request $request, $case_id)
    {
        // Validate incoming data
        $data = $request->validate([
            'approve'   => 'required|string',
            'cur_tas'   => 'required|string',
            'department'=> 'required|string', // ✅ Needed for your logic
        ]);
        if ($data['approve'] === 'Approve') 
        {
            // $cur_tas = $data['cur_tas'];
            $loc = (string) $request->loc;
            $plant_code = substr($loc, 0, 4);
            $usersData = DB::table('pmt_MANPOWER_PLANT_USERS')
                    ->where('PLANT', $plant_code)
                    ->first();
            $EVC_USR = $usersData->EVC_USR ?? null;
            $cur_user = $EVC_USR;
            if( $EVC_USR=='jsrao')
            {
                $cur_task = 'DIRECTOR';
            }
            else
            {
                $cur_task = 'EVC';
            }
            // Find the record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update approval status + remarks
            $mp->SP_STATUS  = $data['approve'];
            $mp->SP_DATE    = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->SP_REM     = $request->input('spremarks');
            $mp->CUR_TASK   = $cur_task;
            $mp->CUR_USR    = $cur_user;
            $mp->CUR_STATUS = 'TO_DO';
            $mp->save();
            // ✅ Update ALL_APPROVALS
            DB::table('ALL_APPROVALS')
                ->where('CASEID', $case_id)
                ->update([
                    'CUR_TASK'        => $cur_task,
                    'CUR_STATUS'      => 'TO_DO',
                     "RAISER_DATE"    =>    $mp->RAISER_DATE,
                     "RECEIVED_DATE"  =>  $mp->PRJ_DATE,
                     "RAISER"         =>   $mp->RAISER,
                    'CUR_USR'         => $cur_user,
                    'LAST_MODIFIED'   => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'PREV_USR'        => Auth::user()->Emp_Name,
                ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT   =  $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  $cur_task,
                  "RAISER_DATE"   =>  $mp->RAISER_DATE,
                  "RECEIVED_DATE" =>  $mp->PRJ_DATE,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" =>  "TO_DO",
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  =>  $cur_user,
                  "TASK_COUNT"    =>  $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval submitted successfully.',
                'case_id' => $case_id,
            ], 200);
        }
         // Optional: handle case when NOT approved
         if ($data['approve'] === 'Reject') 
        {
            // $cur_tas = $data['cur_tas'];
            $loc = (string) $request->loc;
            $plant_code = substr($loc, 0, 4);
            $usersData = DB::table('pmt_MANPOWER_PLANT_USERS')
                    ->where('PLANT', $plant_code)
                    ->first();
            $EVC_USR = $usersData->EVC_USR ?? null;
            $cur_user = Auth::user()->Emp_Name;
            $cur_status = 'COMPLETED';
            $cur_task = 'SP';
            // Find the record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update approval status + remarks
            $mp->SP_STATUS = $data['approve'];
            $mp->SP_DATE = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->SP_REM = $request->input('spremarks');
            $mp->CUR_TASK = $cur_task;
            $mp->CUR_USR = $cur_user;
            $mp->CUR_STATUS = $cur_status;
            $mp->save();
            //✅Update ALL_APPROVALS---
            DB::table('ALL_APPROVALS')
                ->where('CASEID', $case_id)
                ->update([
                    'CUR_TASK'        => $cur_task,
                    'CUR_STATUS'      => $cur_status,
                    "RAISER_DATE"     => $mp->RAISER_DATE,
                    "RECEIVED_DATE"   => $mp->PRJ_DATE,
                    "RAISER"          => $mp->RAISER,
                    'CUR_USR'         => $cur_user,
                    'LAST_MODIFIED'   => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'PREV_USR'        => Auth::user()->Emp_Name,
            ]);
            //Particpants table here-------
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  $cur_task,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" =>  "COMPLETED",
                  "RAISER_DATE"   =>  $mp->RAISER_DATE,
                  "RECEIVED_DATE" =>  $mp->PRJ_DATE,
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  =>  $cur_user,
                  "TASK_COUNT"    =>  $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval was not YES; no further action taken.',
                'case_id' => $case_id,
            ], 200);
        }
        // Optional: handle case when Reverted
         if ($data['approve'] === 'REVERT') 
         {
            // $cur_tas = $data['cur_tas'];
            $usersData = DB::table('manpower_requests')
                    ->where('CHILD_CASEID', $case_id)
                    ->first();
            $RAISER     = $usersData->RAISER ?? null;
            $cur_user   = $RAISER;
            $cur_status = 'TO_DO';
            $cur_task   = 'RAISER';
            // Find the record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update approval status + remarks
            $mp->SP_STATUS = $data['approve'];
            $mp->SP_DATE = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->SP_REM = $request->input('spremarks');
            $mp->CUR_TASK = $cur_task;
            $mp->CUR_USR = $cur_user;
            $mp->CUR_STATUS = $cur_status;
            $mp->save();
            // ✅ Update ALL_APPROVALS
            DB::table('ALL_APPROVALS')
                ->where('CASEID', $case_id)
                ->update([
                    'CUR_TASK'       => $cur_task,
                    'CUR_STATUS'     => $cur_status,
                    'CUR_USR'        => $cur_user,
                    "RAISER"         => $mp->RAISER,
                    'LAST_MODIFIED'  => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'PREV_USR'       => Auth::user()->Emp_Name,
                ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
             ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
             ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  $cur_task,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" =>  "TO_DO",
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  =>  $cur_user,
                  "TASK_COUNT"    =>  $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval was not YES; no further action taken.',
                'case_id' => $case_id,
            ], 200);
        }
    }
    public function ManpowerHODData(Request $request, $case_id)
    {
        // Validate incoming data
        $data = $request->validate([
            'approve'   => 'required|string',
            'cur_tas'   => 'required|string',
            'department'=> 'required|string', // ✅ Needed for your logic
            'hodremarks'=> 'nullable|string',
        ]);
        // Proceed only if approved
        if ($data['approve'] === 'Approve') 
        {
            // Fetch HO_MD_USR based on department
            $dept_data = DB::table('pmt_MANPOWER_FIELDS')
                ->where('DEPT', $request->department)
                ->first();
            $HO_MD_USR = $dept_data->HO_MD_USR ?? '';
            // Determine next task based on HO_MD_USR
            if ($HO_MD_USR === 'jsrao') 
            {
                $cur_tas = 'MD';
            } 
            else if($HO_MD_USR === 'asrao') 
            {
                $cur_tas = 'CFO';
            } 
            else 
            {
                $cur_tas = 'EVC';
            }
            // Find the manpower record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update record fields
            $mp->HO_HOD_STATUS   = $data['approve'];
            $mp->HO_HOD_REM      = $request->input('hodremarks');
            $mp->CUR_STATUS      = 'TO_DO'; // 🛠️ Typically, next step is TO_DO
            $mp->CUR_TASK        = $cur_tas;
            $mp->CUR_USR         = $HO_MD_USR;
            $mp->HO_HOD_DATE     = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->save();
            // ✅ Update ALL_APPROVALS
            DB::table('ALL_APPROVALS')
                ->where('CASEID', $case_id)
                ->update([
                    'CUR_TASK'        => $cur_tas,
                    'CUR_STATUS'      => 'TO_DO',
                    "RAISER_DATE"     => $mp->RAISER_DATE,
                    "RECEIVED_DATE"   => $mp->RAISER_DATE,
                    'CUR_USR'         => $HO_MD_USR,
                    'LAST_MODIFIED'   => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'PREV_USR'        => Auth::user()->Emp_Name,
                ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  $cur_tas,
                  "RAISER_DATE"   =>  $mp->RAISER_DATE,
                  "RECEIVED_DATE" =>  $mp->RAISER_DATE,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" => "TO_DO",
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  =>  $HO_MD_USR,
                  "TASK_COUNT"    =>  $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval submitted successfully.',
                'case_id' => $case_id,
            ], 200);
        }
        // Optional: handle case when NOT approved
        return response()->json([
            'message' => 'Approval was not YES; no further action taken.',
            'case_id' => $case_id,
        ], 200);
    }
    public function ManpowerEVCData(Request $request, $case_id)
    {
        // Validate incoming data
        $data = $request->validate([
            'approve'   => 'required|string',
            'cur_tas'   => 'required|string',
            'department'=> 'required|string',  // ✅ Needed for your logic
            'hodremarks'=> 'nullable|string',
        ]);
        // Proceed only if approved
        if ($data['approve'] === 'Approve') 
        {
            $cur_tas = $data['cur_tas'];
            if($cur_tas == 'EVC')
            {
                $rem = $request->input('evcremarks');
            }
            // Find the manpower record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update record fields
            $mp->EVC_STATUS   = $data['approve'];
            $mp->EVC_DATE     = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->EVC_REM      = $rem;
            $mp->CUR_STATUS   = 'COMPLETED'; // 🛠️ Typically, next step is TO_DO
            $mp->CUR_TASK     = $cur_tas;
            $mp->STATUS       ='COMPLETED';
            $mp->CUR_USR      = Auth::user()->Emp_Name;
            $mp->EVC_DATE     = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->save();
             // ✅ Update ALL_APPROVALS
            DB::table('ALL_APPROVALS')
                ->where('CASEID', $case_id)
                ->update([
                    'CUR_TASK'        =>  $cur_tas,
                    'CUR_STATUS'      =>  'COMPLETED',
                    "RAISER_DATE"     =>  $mp->RAISER_DATE,
                    "RECEIVED_DATE"   =>  $mp->SP_DATE,
                     "RAISER"         =>  $mp->RAISER,
                    'CUR_USR'         =>  Auth::user()->Emp_Name,
                    'LAST_MODIFIED'   =>  \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    // 'PREV_USR'   => Auth::user()->Emp_Name,
                ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  $cur_tas,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" =>  "COMPLETED",
                  "RAISER_DATE"   =>  $mp->RAISER_DATE,
                  "RECEIVED_DATE" =>  $mp->SP_DATE,
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  =>  Auth::user()->Emp_Name,
                  "TASK_COUNT"    =>  $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval submitted successfully.',
                'case_id' => $case_id,
            ], 200);
        }
        // Optional: handle case when NOT approved
        if ($data['approve'] === 'Reject') 
        {
            // $cur_tas = $data['cur_tas'];
            $loc = (string) $request->loc;
            $plant_code = substr($loc, 0, 4);
            $usersData = DB::table('pmt_MANPOWER_PLANT_USERS')
                    ->where('PLANT', $plant_code)
                    ->first();
            $EVC_USR = $usersData->EVC_USR ?? null;
            $cur_user = Auth::user()->Emp_Name;
            $cur_status = 'COMPLETED';
            $cur_task = 'SP';
            // Find the record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update approval status + remarks
            $mp->SP_STATUS  = $data['approve'];
            $mp->EVC_DATE   = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->SP_REM     = $request->input('spremarks');
            $mp->CUR_TASK   = $cur_task;
            $mp->CUR_USR    = $cur_user;
            $mp->CUR_STATUS = $cur_status;
            $mp->save();
            // ✅ Update ALL_APPROVALS
                DB::table('ALL_APPROVALS')
                    ->where('CASEID', $case_id)
                    ->update([
                        'CUR_TASK'        =>  $cur_task,
                        'CUR_STATUS'      =>  $cur_status,
                        'CUR_USR'         =>  $cur_user,
                        "RAISER_DATE"     =>  $mp->RAISER_DATE,
                        "RECEIVED_DATE"   =>  $mp->SP_DATE,
                        "RAISER"          =>  $mp->RAISER,
                        'LAST_MODIFIED'   =>  \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                        'PREV_USR'        =>  Auth::user()->Emp_Name,
                    ]);
                    //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  $cur_task,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" =>  $cur_status,
                  "RAISER_DATE"   =>  $mp->RAISER_DATE,
                  "RECEIVED_DATE" =>  $mp->SP_DATE,
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  =>  $cur_user,
                  "TASK_COUNT"    =>  $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval was not YES; no further action taken.',
                'case_id' => $case_id,
            ], 200);
        }
          // Optional: handle case when Reverted
         if ($data['approve'] === 'REVERT') 
         {
            // $cur_tas = $data['cur_tas'];
            $usersData = DB::table('manpower_requests')
                    ->where('CHILD_CASEID', $case_id)
                    ->first();
            $RAISER     =  $usersData->RAISER ?? null;
            $cur_user   =  $RAISER;
            $cur_status = 'TO_DO';
            $cur_task   = 'RAISER';
            // Find the record
            $mp = getManpowerData::where('CHILD_CASEID', $case_id)->firstOrFail();
            // Update approval status + remarks
            $mp->SP_STATUS  = $data['approve'];
            $mp->SP_DATE    = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $mp->SP_REM     = $request->input('spremarks');
            $mp->CUR_TASK   = $cur_task;
            $mp->CUR_USR    = $cur_user;
            $mp->CUR_STATUS = $cur_status;
            $mp->save();
            // ✅ Update ALL_APPROVALS
            DB::table('ALL_APPROVALS')
                ->where('CASEID', $case_id)
                ->update([
                    'CUR_TASK'        =>  $cur_task,
                    'CUR_STATUS'      =>  $cur_status,
                    'CUR_USR'         =>  $cur_user,
                    "RAISER_DATE"     =>  $mp->RAISER_DATE,
                    "RECEIVED_DATE"   =>  $mp->SP_DATE,
                     "RAISER"         =>  $mp->RAISER,
                    'LAST_MODIFIED'   =>  \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'PREV_USR'        =>  Auth::user()->Emp_Name,
            ]);
            //Particpants table here
             $old_tas_count = DB::table('participates')
            ->where('CASEID', $case_id)
             ->orderBy('TASK_COUNT', 'desc')
            ->first();
            $TASK_COUNT = $old_tas_count ? $old_tas_count->TASK_COUNT + 1 : '';
            $participants =  new Participant();
            $participants->create([
                  "CASEID"        =>  $case_id  ,
                  "PROCESSNAME"   =>  "Manpower",
                  "TASK_NAME"     =>  $cur_task ,
                  "ACTION_UID"    =>  Auth::user()->Emp_Name,
                  "ACTION_STATUS" =>  $cur_status,
                  "RAISER_DATE"   =>  $mp->RAISER_DATE,
                  "RECEIVED_DATE" =>  $mp->SP_DATE,
                  "RAISER"        =>  $mp->RAISER,
                  "CURRENT_USER"  =>  $cur_user,
                  "TASK_COUNT"    =>  $TASK_COUNT
            ]);
            return response()->json([
                'message' => 'Approval was not YES; no further action taken.',
                'case_id' => $case_id,
            ], 200);
        }
    }
    public function ManPowerReqData()
    {
        try
        {
            $manPowerData = getManpowerData::where('EVC_STATUS',"Approve")->get();
            return response()->json(["status" => 200,"data" => $manPowerData,"message"=>"ManPower Data Fetched Successfully"]);
        }
        catch(\Exception $e)
        {
            return response()->json(["status"=>"500","message"=>"Internal Server Error","error"=>$e->getMessage()]);
        }
    }
    public function hrStatusUpdateHistory(Request $request)
    {
        try
        {
           //-----------------------------------manPowerStationery------------------------------------------
           $statusHistory                      =  new HrManPowerStatusHistory();
           $statusHistory->status              =  $request->STATUS;  
           $statusHistory->EMPID               =  $request->EMPID;
           $statusHistory->current_designation =  $request->Current_Designation;
           $statusHistory->Current_Plant       =  $request->Current_Plant;
           $statusHistory->Current_dept        =  $request->Current_dept;
           $statusHistory->depart              =  $request->Department;
           $statusHistory->Design              =  $request->Designation;
           $statusHistory->Plant               =  $request->Plant;
           $statusHistory->child_caseid        =  $request->CASEID;
           $statusHistory->Transfer_Date       =  $request->Transfer_Date;
           $statusHistory->Remarks             =  $request->Remarks;
           $statusHistory->updated_by          =  Auth::user()->Emp_Name;
        //    $statusHistory->Wip_Date            =  \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        //    $statusHistory->Joining_Date        =  $request->JoinDate;
        //    $statusHistory->Reverted_Date       =  \Carbon\Carbon::now()->format('Y-m-d H:i:s'); 
        //    $statusHistory->Transfer_Date       =  $request->Transfer_Date; 
           $statusHistory->updated_date        =  \Carbon\Carbon::now()->format('Y-m-d H:i:s'); 
          if ($statusHistory->save()) 
         {
            $plant_code = substr($statusHistory->Current_Plant, 0, 4);
            \Log::info("Plant_code :".$plant_code);
            if (in_array($statusHistory->status, ['Transfer', 'Joined'])) 
            {
                 $updateMNP = ManPowerUpload::where('Plant_code', $plant_code)
                    ->where('Designation', $statusHistory->current_designation)
                    ->first();
                \Log::info("updateMNP :",[$updateMNP]);
                if($statusHistory->status == "Transfer")
                {
                        // This is Old designation  Transferring From ---------
                        $plant_code1 = substr($statusHistory->Plant, 0, 4);
                        $ActualDesignation = ManPowerUpload::where('Plant_code', $plant_code1)
                            ->where('Designation', $statusHistory->Design)
                            ->first();
                        if ($ActualDesignation) 
                        {
                            $ActualDesignation->update(
                [
                                "Availability" => $ActualDesignation->Availability-1,
                                "Actual_Requirement" => $ActualDesignation->Total_Requirement-($ActualDesignation->Availability - 1)
                            ]);
                        } 
                        else 
                        {
                            \Log::error("ActualDesignation not found for Transfer. Plant: {$plant_code1}, Designation: {$statusHistory->Design}");
                        }
                        if ($updateMNP) 
                        {
                            $updateMNP->update(
                    [
                                  "Availability" => $updateMNP->Availability + 1,
                                  "Actual_Requirement" => $updateMNP->Total_Requirement - ($updateMNP->Availability + 1)
                                ]);
                        } 
                        else 
                        {
                            \Log::error("updateMNP not found for Transfer. Plant: {$plant_code}, Designation: {$statusHistory->current_designation}");
                        }
                    }
                    else if ($statusHistory->status == "Joined")
                    {
                        if ($updateMNP) 
                        {
                            $updateMNP->update([
                                "Availability" => $updateMNP->Availability + 1,
                                "Actual_Requirement" => $updateMNP->Total_Requirement - ($updateMNP->Availability + 1)
                            ]);
                        } 
                        else 
                        {
                            \Log::error("updateMNP not found for Joined. Plant: {$plant_code}, Designation: {$statusHistory->current_designation}");
                        }
                    }
            }
        }
           //ManPower Update the ManPower here ------
           $maPowerUpdated         = getManpowerData::where('CHILD_CASEID',$statusHistory->child_caseid)->first();
           $maPowerUpdated->STATUS = $request->STATUS;  
           $maPowerUpdated->EMPID  = $request->EMPID;
           $maPowerUpdated->HR     = Auth::user()->Emp_Name; 
           $maPowerUpdated->Wip_Date            =  \Carbon\Carbon::now()->format('Y-m-d H:i:s');
           $maPowerUpdated->Joining_Date        =  $request->JoinDate;
           $maPowerUpdated->Reverted_Date       =  \Carbon\Carbon::now()->format('Y-m-d H:i:s'); 
           $maPowerUpdated->Transfer_Date       =  $request->Transfer_Date; 
           if($maPowerUpdated->save())
           {
             if( $maPowerUpdated->STATUS === "Reverted") 
             {
                DB::table('ALL_APPROVALS')
                ->where('CASEID', $maPowerUpdated->CHILD_CASEID)
                ->update([
                    'CUR_TASK'        =>  "Raiser",
                    'CUR_STATUS'      =>  "TO_DO",
                    'CUR_USR'         =>  $maPowerUpdated->RAISER,
                    'PREV_USR'        =>  $maPowerUpdated->HR??"",
                          ]);
             }
           }
           return response()->json(["status"=>201,"message"=>"ManPower Status History Successfully","success"=>true]);
        }
        catch(\Exception $e)
        {
            return response()->json(["status" => 500, 
            "message" => "Internal Server Error", 
            "error" => $e->getMessage()]);
        }
    }

    public function mrfStatusHistory(Request $request){
        try
        {   
           $manPowerStatusHistory = HrManPowerStatusHistory::where('child_caseid',$request->childCaseId)->get();
           return response()->json(
        [
               "status"=>200,
               "message" => "Mrf Status History Successfully",
               "success" => true,
               'Mrfdata' => $manPowerStatusHistory
               ]);
        }
        catch(\Exception $e)
        {
            return response()->json([
            "status"  => 500,
            "message" => "Internal Server Error",
            "success" => false]);
        }
    }
    public function manpowerCloseStatus(Request $request){
    try
      {
         DB::table('ALL_APPROVALS')
                ->where('CASEID', $request->case_id)
                ->update([
                    'CUR_TASK'        =>  "Raiser",
                    'CUR_STATUS'      =>  "CLOSED"
                ]);
        }
        catch(\Exception $e)
        {
            return response()->json(["status"=>500,"message"=>"Internal Server Error",'error'=>$e->getMessage()]);
        }
    }
    //---------->>>>>--------ManPower Upload Data Update------------------>>>>>>>>>>>>>>>>>>>>>>>>
public function mrfUploadDataUpdate(Request $request)
   {
    \Log::info("REquest Data:".$request); 
    try 
    {
        $manPowerUploadUpdate = ManPowerUpload::where('mrf_id', $request->mrf_id)->first();
        if (!$manPowerUploadUpdate) 
        {
            return response()->json([
                "status" => 404,
                "message" => "MRF record not found"
            ]);
        }
        $totalRequirement = $request->Total_Requirement;
        $availability = $manPowerUploadUpdate->Availability ?? 0;
        $actualRequirement = $totalRequirement - $availability;
        $manPowerUploadUpdate->update([
            "Total_Requirement" => $totalRequirement,
            "Actual_Requirement" => $actualRequirement, // ✅ Ensure DB field is correct
            "Emp_Name" => Auth::user()->Emp_Name
        ]);
        return response()->json([
            "status" => 200,
            "message" => "MRF data updated successfully"
        ]);
    } 
    catch (\Exception $e) 
    {
        return response()->json([
            "status" => 500,
            "message" => "Internal Server Error",
            "error" => $e->getMessage()
        ]);
    }
}
//------------------------------------OverallMrfStatusData Here-----------------------------------------
public function overallMrfStatusCount(Request $request)
{
    try
    {
     $results = DB::table('manpower_requests')
        ->select(
            'PLANT',
            DB::raw("COUNT(CASE WHEN status = 'Transfer' THEN 1 END) AS Transferred"),
            DB::raw("COUNT(CASE WHEN status = 'Reverted' THEN 1 END) AS Reverted"),
            DB::raw("COUNT(CASE WHEN status = 'Joined' THEN 1 END) AS Joined"),
            DB::raw("COUNT(CASE WHEN status = 'WIP' THEN 1 END) AS WIP"),
            DB::raw("COUNT(CASE WHEN status = 'TO_DO' THEN 1 END) as Pending"),
            DB::raw("COUNT(*) as RaiserCount"),
           )
    ->groupBy('PLANT')
    ->orderBy('PLANT')
    ->get();
    $overallCounts = DB::table('manpower_requests')
    ->select(
        DB::raw("COUNT(CASE WHEN status = 'Transfer' THEN 1 END) AS Transfer"),
        DB::raw("COUNT(CASE WHEN status = 'Reverted' THEN 1 END) AS Reverted"),
        DB::raw("COUNT(CASE WHEN status = 'WIP' THEN 1 END) AS WIP"),
        DB::raw("COUNT(CASE WHEN status = 'Joined' THEN 1 END) AS Joined"),
        DB::raw("COUNT(CASE WHEN status = 'TO_DO' THEN 1 END) AS Pending"),
        DB::raw("COUNT(CASE WHEN EVC_STATUS = 'Approve' THEN 1 END) AS Approval"),
        DB::raw("COUNT(CASEID) AS RaiserCount"),
    )
    ->first();
    return response()->json([
         "status"                => 200,
         "message"              => "Mrf Status Fetched Successfully",
         'PlantWiseStatusCount' => $results,
         "OverStatusCount"      => $overallCounts
    ]);
    }
    catch(\Exception $e)
    {
        return response()->json(["status"=>500,"error"=>"Internal Server Error","message"=>$e->getMessage()]);
    }

}
private function diffInRoundedDays(?Carbon $start, ?Carbon $end): ?int
{
    if (!$start || !$end) {
        return null;
    }
    // If both dates are the same calendar day → return 0
    if ($start->isSameDay($end)) {
        return 0;
    }
    // Round to nearest day
    $seconds = abs($end->diffInSeconds($start, false));
    return (int) round($seconds / 86400);
}



public function agingAnalaysisAprlvs(Request $request)
{
try 
 {
    $records = DB::table('manpower_requests')->get();
    $grouped = $records->groupBy('PLANT')->map(function ($items, $plant) 
    {
     $children = $items->map(function ($row) 
     {
        $dates = [
    'RAISER_DATE' => $row->RAISER_DATE ? Carbon::parse($row->RAISER_DATE) : null,
    'GM_DATE'     => $row->GM_DATE     ? Carbon::parse($row->GM_DATE)     : null,
    'PRJ_DATE'    => $row->PRJ_DATE    ? Carbon::parse($row->PRJ_DATE)    : null,
    'FUNC_DATE'   => $row->FUNC_DATE   ? Carbon::parse($row->FUNC_DATE)   : null,
    'SP_DATE'     => $row->SP_DATE     ? Carbon::parse($row->SP_DATE)     : null,
    'EVC_DATE'    => $row->EVC_DATE    ? Carbon::parse($row->EVC_DATE)    : null,
    'HO_HOD_DATE' => $row->HO_HOD_DATE ? Carbon::parse($row->HO_HOD_DATE) : null,
];

$delays = [];

if ($dates['HO_HOD_DATE']) {
    $delays['RAISER_to_HO_HOD'] = $this->diffInRoundedDays($dates['RAISER_DATE'], $dates['HO_HOD_DATE']);
    $delays['HO_HOD_to_EVC']    = $this->diffInRoundedDays($dates['HO_HOD_DATE'], $dates['EVC_DATE']);
} elseif ($dates['FUNC_DATE']) {
    $delays['RAISER_to_GM']  = $this->diffInRoundedDays($dates['RAISER_DATE'], $dates['GM_DATE']);
    $delays['GM_to_PRJ']     = $this->diffInRoundedDays($dates['GM_DATE'], $dates['PRJ_DATE']);
    $delays['PRJ_to_FUNC']   = $this->diffInRoundedDays($dates['PRJ_DATE'], $dates['FUNC_DATE']);
    $delays['FUNC_to_SP']    = $this->diffInRoundedDays($dates['FUNC_DATE'], $dates['SP_DATE']);
    $delays['SP_to_EVC']     = $this->diffInRoundedDays($dates['SP_DATE'], $dates['EVC_DATE']);
} else {
    $delays['RAISER_to_GM']  = $this->diffInRoundedDays($dates['RAISER_DATE'], $dates['GM_DATE']);
    $delays['GM_to_PRJ']     = $this->diffInRoundedDays($dates['GM_DATE'], $dates['PRJ_DATE']);
    $delays['PRJ_to_SP']     = $this->diffInRoundedDays($dates['PRJ_DATE'], $dates['SP_DATE']);
    $delays['SP_to_EVC']     = $this->diffInRoundedDays($dates['SP_DATE'], $dates['EVC_DATE']);
}

        //Average per child
        $valid = array_filter($delays, fn($v) => $v !== null);
        $delays['Average'] = count($valid) ? ceil(array_sum($valid) / count($valid)) : null;
        return 
        [
             "CASEID"       => $row->CASEID ,
            'CHILD_CASEID'  => $row->CHILD_CASEID,
            'RAISER'        => $row->RAISER,
            'MANPOWER_DESG' => $row->MANPOWER_DESG,
            'STATUS'        => $row->STATUS,
            'delays'        => $delays,
        ];
    })->values();
    // Calculate Plant-level averages
    $plantDelays = [];
    if ($children->count()) 
    {
        $allDelayKeys = $children->flatMap(fn($c) => array_keys($c['delays']))->unique();
            foreach ($allDelayKeys as $key) 
                {
                $values = $children->map(fn($c) => $c['delays'][$key] ?? null)
                                ->filter(fn($v) => $v !== null)
                                ->toArray();
                $plantDelays[$key] = count($values) ? ceil(array_sum($values) / count($values)) : null;
            }
    }
    return [
       'PLANT'                => $plant,
       'plant_average_delays' => $plantDelays, 
       'children'             => $children,
    ];
   })->values();
        return response()->json([
            "status"  => 200,
            "message" => "Data Fetched Successfully",
            "data"    => $grouped
        ]);
    } 
    catch (\Exception $e) 
    {
        return response()->json([
            "status" => 500,
            "message" => "Internal Server Error",
            "error" => $e->getMessage()
        ]);
    }
}


public function agingHRAnalaysis()
{
    try 
    {
        $getHRAnalaysisData = DB::table('manpower_requests')->get();
        $grouped = $getHRAnalaysisData->groupBy('PLANT')->map(function ($plantGroup, $plantName) {
            $children = $plantGroup->map(function ($record) 
            {
                $wipDays = $record->EVC_DATE && $record->Wip_Date
                    ? Carbon::parse($record->EVC_DATE)->diffInDays(Carbon::parse($record->Wip_Date))
                    : null;
                $joinedDays = $record->EVC_DATE && $record->Joining_Date
                    ? Carbon::parse($record->EVC_DATE)->diffInDays(Carbon::parse($record->Joining_Date))
                    : null;
                $transferDays = $record->EVC_DATE && $record->Transfer_Date
                    ? Carbon::parse($record->EVC_DATE)->diffInDays(Carbon::parse($record->Transfer_Date))
                    : null;
                $revertedDays = $record->EVC_DATE && $record->Reverted_Date
                    ? Carbon::parse($record->EVC_DATE)->diffInDays(Carbon::parse($record->Reverted_Date))
                    : null;
                $avg = collect([$wipDays, $joinedDays, $transferDays, $revertedDays])
                    ->filter()
                    ->avg();
                return [
                        "CASEID"         => $record->CASEID ,
                        "CHILD_CASEID"   => $record->CHILD_CASEID  ,
                        "RAISER"         => $record->RAISER        ,
                        "MANPOWER_DESG"  => $record->MANPOWER_DESG ,
                        "STATUS"         => $record->STATUS        ,
                        "delays"         => 
                    [
                        "WIP"      => $wipDays      !== null ? round($wipDays) : null,
                        "Joined"   => $joinedDays   !== null ? round($joinedDays) : null,
                        "Transfer" => $transferDays !== null ? round($transferDays) : null,
                        "Reverted" => $revertedDays !== null ? round($revertedDays) : null,
                        "Average"  => $avg          !== null ? round($avg) : null,
                    ]
                ];
            });
            // plant level averages
          $plantAverages = [
                                "WIP"      => round($children->pluck('delays.WIP')->filter()->avg()),
                                "Joined"   => round($children->pluck('delays.Joined')->filter()->avg()),
                                "Transfer" => round($children->pluck('delays.Transfer')->filter()->avg()),
                                "Reverted" => round($children->pluck('delays.Reverted')->filter()->avg()),
                           ];
       // Calculate overall average of the above 4
            $values = array_filter([
                $plantAverages["WIP"],
                $plantAverages["Joined"],
                $plantAverages["Transfer"],
                $plantAverages["Reverted"],
            ]);
         $plantAverages["Average"] = count($values) ? round(array_sum($values) / count($values)) : 0;
            return 
            [
                "PLANT"                => $plantName,
                "plant_average_delays" => $plantAverages,
                "children"             => $children,
            ];
        })->values();
        return response()->json([
            "status" => 200,
            "message" => "Data Fetched Successfully",
            "data" => $grouped
        ]);
    }
     catch (\Exception $e) 
    {
        return response()->json([
            "status"  => 500,
            "message" => "Something went wrong",
            "error"   => $e->getMessage(),
        ], 500);
    }
}
public function filterOverallCountDesgni(Request $request)
{
\Log::info("Request Data: " . json_encode($request->all()));
    try 
    {
      $filterData = DB::table('man_power_upload as mpu')
        ->leftJoin('manpower_requests as mr', function ($join) {
            $join->on('mpu.Plant_code', '=', 'mr.PLANT')
                ->on('mpu.Designation', '=', 'mr.MANPOWER_DESG');
        })
       ->select(
        'mpu.Plant_code',
                 'mpu.Designation',
                 DB::raw("COUNT(*) as Designation_count"),   
                 'mpu.Total_Requirement',
                'mpu.Availability',
                'mpu.Actual_Requirement',
                'mpu.Department',
            DB::raw("COUNT(mr.SNO) as MRF_Raised_count"),
            DB::raw("SUM(CASE WHEN mr.Status = 'Joined'   THEN 1 ELSE 0 END) as Joined_count"),
            DB::raw("SUM(CASE WHEN mr.Status = 'Transfer' THEN 1 ELSE 0 END) as Transfer_count"),
            DB::raw("SUM(CASE WHEN mr.Status = 'Pending'  THEN 1 ELSE 0 END) as Pending_count"),
            DB::raw("(COUNT(*)-
                (SUM(CASE WHEN mr.Status = 'Joined' THEN 1 ELSE 0 END) +
                SUM(CASE WHEN mr.Status = 'Transfer' THEN 1 ELSE 0 END))
        ) as Existing_count"),
        DB::raw("
    (
        SUM(CASE WHEN mr.Status = 'Joined' THEN 1 ELSE 0 END) +
        SUM(CASE WHEN mr.Status = 'Transfer' THEN 1 ELSE 0 END) +
        (
            COUNT(*) - 
            (
                SUM(CASE WHEN mr.Status = 'Joined' THEN 1 ELSE 0 END) +
                SUM(CASE WHEN mr.Status = 'Transfer' THEN 1 ELSE 0 END)
            )
        )
    ) as Total
")
        )->groupBy(
            'mpu.Plant_code',
            'mpu.Designation',
            'mpu.Total_Requirement',
            'mpu.Availability',
            'mpu.Actual_Requirement',
            'mpu.Department'
        )
            // 🔹Filter by Plant if provided
            ->when($request->plant_code, function ($query) use ($request) {
                $query->where('mpu.Plant_code', $request->plant_code);
            })
            // 🔹 Filter by Designation if provided
            ->when($request->designation, function ($query) use ($request) {
                $query->where('mpu.Designation', $request->designation);
            })
            ->when($request->year, function ($query) use ($request) {
               $query->whereYear('mr.created_at', $request->year); // 👈 adjust column name
            })
            ->groupBy('mr.PLANT', 'mr.MANPOWER_DESG')->get();
          return response()->json([
               "status" => 200,
               "data" => $filterData,
               "message" => "Filtered Fetched Successfully",
               "success" => true
          ]);
    } 
    catch (\Exception $e) 
    {
        return response()->json([
            'status' => 500,
            'message' => "Internal Server Error",
            "error" => $e->getMessage()
        ]);
    }
}
}
