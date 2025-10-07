<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmpMhcProjects;
use App\Models\mhc_projects;
use App\Models\SubProject;
use App\Models\EmpDetails;

use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{



    public function getUserProjects()
    {
        try
        {
         $userLegacyId = Auth::user()->Legacy_Id;
         $projects=[];
         $userProjectIds = EmpMhcProjects::where('Legacy_Id', $userLegacyId)->get();

           foreach($userProjectIds  as $projs )
           {
             $projects[]=$projs->project_name;
           }
            return response()->json([
                'success' => true,
                'data' => $projects,
                'user_legacy_id' => $userLegacyId
            ]);
        }catch(\Exception $e){
            return response()->json(["status"=>500,'message'=>"Internal Server Error","error"=>$e->getMessage()]);
        }
        
    }

  
   

    public function getSubProjects($project_Name)
    {
     

        try {

        $subProjects = SubProject::where('project_name', $project_Name)
            ->get();
        return response()->json([
            'success' => true,
            'data' => $subProjects
        ]);

        }
        catch(\Expection $e) {
            return response()-> json(["status" => 500, 'message'=> "Internal Server Error", "error" => $e ->getMessage()]);
        }

             
    } 
}