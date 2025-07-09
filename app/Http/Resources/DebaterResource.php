<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebaterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'profile' => array_merge((new MobileUserResource($this->user))->toArray($request), [
                'coach_name' => '{$this->coach->user->first_name} {$this->coach->user->last_name}']),
                'debates' => ''
        ];
    }
}
