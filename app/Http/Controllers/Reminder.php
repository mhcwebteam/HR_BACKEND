<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// class Reminder extends Controller
// {
   
// }



// <?php

namespace App\Http\Controllers;

use App\Events\ReminderEvent;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function sendReminder(Request $request)
    {
        $message = [
            'title' => $request->title ?? 'Reminder',
            'message' => $request->message ?? 'You have a scheduled task',
            'minutes_left' => $request->minutes ?? 2
        ];

        broadcast(new ReminderEvent($message));

        return response()->json([
            'success' => true,
            'message' => 'Reminder sent successfully'
        ]);
    }
}