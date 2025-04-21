<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoachResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'user_Id' => $this->user_id,
            'coach' => new MobileUserResource($this->user),
        ];
    }
}
