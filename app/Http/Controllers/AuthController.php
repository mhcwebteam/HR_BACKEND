<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\EmpDetails;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request) 
    {
        $user = EmpDetails::create(
        [
            'Legacy_Id' => $request->EMP_ID,
            //'password' => Hash::make($request->password),
            'Password' => $request->password
        ]
    );
        $token = JWTAuth::fromUser($user);
        return response()->json(
            [
                'token' => $token,
                'user' => $user
            ]
        );
    }

    // public function login(Request $request) 
    // {
    //     $credentials = $request->only('EMP_ID','password');
    //    // dd($credentials['username']);
    //     if (!$token = JWTAuth::attempt($credentials)) 
    //     {
    //         return response()->json(['error' => 'Invalid credentials'], 401);
    //     }

    //     return response()->json([
    //         'token' => $token,
    //         'user' => $user
    //     ]);

    // }

    public function login(Request $request)
    {
        $credentials = $request->only('username','password');
        // Get user from DB by username
        // Get user from DB by case-insensitive EMP_ID
        $user = EmpDetails::whereRaw('LOWER(Legacy_Id) = ?', 
        [strtolower($credentials['username'])])->first();
        if (!$user) 
        {
            return response()->json(
                ['error'=>'User not found']
                ,404);
        }
        // Manually compare plain-text passwords
        if (strcasecmp($credentials['password'], $user->Password) === 0)
        {
            // Password matched (you can return JWT token here)
            $token = JWTAuth::fromUser($user);
            return response()->json(
    [
            'token' => $token,
            'employee'=>
                        [ 
                           "Emp_Id"        => $user->Legacy_Id,
                           "Employee_Name" => $user->Emp_Name,
                           "Email"         => $user->Email_Id,
                           "Is_Employee"   => $user->Is_Employee,
                           "Emp_Category"  => $user->Emp_Category
                        ]
                ]
            );
        }
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    public function logout() 
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Logged out successfully']);
    }
    public function profile() 
    {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json(compact('user'));
    }
}
