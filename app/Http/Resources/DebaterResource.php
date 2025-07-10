<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebaterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'profile' => [
                'debater_id' => $this->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'email' => $this->user->email,
                'mobile_number' => $this->user->mobile_number,
                'governorate' => $this->user->governorate,
                'profile_picture_url' => $this->user->profile_picture_url,
                'birth_date' => $this->user->birth_date,
                'education_degree' => $this->user->education_degree,
                'faculty' => $this->user->faculty?->name,
                'university' => $this->user->faculty?->university?->name,
                'coach_name' => $this->coach->user->first_name . ' ' . $this->coach->user->last_name,
            ],
            'debates' => '',
            'guard' => 'debater'
        ];
    }
}
