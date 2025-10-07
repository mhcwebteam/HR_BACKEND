<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\ReminderEvent;

class ReminderController extends Controller
{
 public function sendReminder(Request $request)
    {
        $message = [
            'title' => $request->title ?? 'Reminder',
            'message' => $request->message ?? 'You have a scheduled task',
            'minutes_left' => $request->minutes ?? 1
        ];

        broadcast(new ReminderEvent($message));

        return response()->json([
            'success' => true,
            'message' => 'Reminder sent successfully'
        ]);
    }
}



