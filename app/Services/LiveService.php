<?php


namespace App\Services;


use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;

class LiveService
{
    public function createToken($roomName, $participantName) {
        // Define the token options.
        $tokenOptions = (new AccessTokenOptions())
            ->setIdentity($participantName);

        // Define the video grants.
        $videoGrant = (new VideoGrant())
            ->setRoomJoin()->setRoomName($roomName);

        // Initialize and fetch the JWT Token.
        $token = (new AccessToken(env('LIVEKIT_API_KEY'), env('LIVEKIT_API_SECRET')))
            ->init($tokenOptions)
            ->setGrant($videoGrant)
            ->toJwt();

        return $token;

    }

}
