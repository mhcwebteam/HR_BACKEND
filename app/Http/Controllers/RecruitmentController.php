<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\OnboardingInvitation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecruitmentController extends Controller
{
    public function sendOnboardingLink(Request $request)
    {
        Log::info('Received onboarding link request', $request->all());

     
        $request->validate([
            'case_id' => 'required|string',
            'employee_email' => 'required|email',
            'employee_name' => 'required|string',
            'designation' => 'nullable|string',
            'department' => 'nullable|string',
            'plant' => 'nullable|string',
        ]);

        try {
          
            $token = Str::random(60);
            $formLink = url("/employee-onboarding/{$token}");

            $username = $request->case_id;
            $password = $request->case_id;

            // Send onboarding mail
            Mail::to($request->employee_email)->send(
                new OnboardingInvitation(
                    $request->employee_name,
                    $formLink,
                    $request->designation ?? 'N/A',
                    $request->department ?? 'N/A',
                    $username,
                    $password
                )
            );

            Log::info("Onboarding email sent to: {$request->employee_email}");

            return response()->json([
                'success' => true,
                'message' => 'Onboarding link sent successfully!',
                'data' => [
                    'form_link' => $formLink,
                    'case_id' => $request->case_id,
                    'employee_email' => $request->employee_email,
                    'employee_name' => $request->employee_name,
                    'designation' => $request->designation ?? 'N/A',
                    'department' => $request->department ?? 'N/A',
                    'plant' => $request->plant ?? 'N/A',
                    'sent_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send onboarding email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send onboarding email. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
