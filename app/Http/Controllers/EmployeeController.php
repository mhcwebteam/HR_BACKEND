<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;

class EmployeeController extends Controller
{
    public function __construct()
    {

    }
    public function EmployeeList(Request $request)
    {
        try
        {
           $employeeData = Employee::get();
           return response()->json(["employeeData"=>$employeeData,"status"=>200,"success"=>true]);
        }
        catch(Exception $e)
        {
          return response()->json(['status'=>500,"message"=>"Internal Server Error","success"=>false]);
        }
    }
}
