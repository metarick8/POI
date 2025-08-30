<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Services\FirebaseService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService) 
    {
        $this->firebaseService = $firebaseService;
    }

    public function sendPushNotification(Request $request) {
        $request->validate([
            'token'=>'required|string',
            'title'=>'required|string',
            'body'=>'required|string',
            'data'=>'nullable|array',
        ]);
        $token= $request->input('token');
        $title= $request->input('title');
        $body= $request->input('body');
        $data= $request->input('data');
        $this->firebaseService->sendNotification($token, $title, $body, $data);
        return response()->json(['message'=>'Notification sent successfully!']);
    }
    public function setup(Request $request) {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();

        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'message' => 'FCM token updated successfully',
            'user_id' => $user->id,
        ]);


    }
    public function adminSetup(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $admin = Admin::findOrFail(1);
        $admin->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json(['message' => 'Admin FCM token updated successfully']);
    }
}
