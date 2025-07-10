<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoachResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'profile' => [
                'coach_id' => $this->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'email' => $this->user->email,
                'mobile_number' => $this->user->mobile_number,
                'governorate' => $this->user->governorate,
                'profile_picture_url' => $this->user->profile_picture_url,
                'birth_date' => $this->user->birth_date,
                'education_degree' => $this->user->education_degree,
                'faculty' => $this->user->faculty?->name,
                'university' => $this->user->faculty?->university?->name
            ],
            'team' => 'team',
            'guard' => 'coach'
        ];
    }
}
