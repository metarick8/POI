<?php
namespace App\Services;

use Exception;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseService
{

    protected $messaging;
    public function __construct()
    {
        $serviceAccountPath = storage_path('poi-project-bf260-firebase-adminsdk-fbsvc-b71b3f12fb.json');
        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $this->messaging = $factory->createMessaging();
    }
    public function sendNotification($token, $title, $body, $data=[]) {
        try{

            $message = CloudMessage::withTarget('token',$token)
            ->withNotification(['title'=>$title, 'body'=>$body])
            ->withData($data);
            
            $this->messaging->send($message);
        } catch(Exception $e) {
        }
    }
}