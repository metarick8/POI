<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->map(function ($user) {
            if ($user->debater) {
                return new DebaterResource($user->debater);
            } elseif ($user->coach) {
                return new CoachResource($user->coach);
            } elseif ($user->judge) {
                return new JudgeResource($user->judge);
            } else {
                return new MobileUserResource($user);
            }
        })->toArray();
    }
}
