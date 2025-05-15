<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmpDetails;

class UserController extends Controller
{
    public function getUsers(Request $request)
    {
       try
       {
            $getUsers = EmpDetails::get();
            return response()->json(["status"=>200,
            "message"=>"User Data Fetched Successfully",
            "success"=>true,'user'=>$getUsers]);
       }
       catch(\Exception $e)
       {
            return response()->json(['status'=>500,
            "message"=>"Internal Server Error","error"=>$e]);
       }
    }
}
