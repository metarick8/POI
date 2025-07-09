<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'mobile_number' => $this->mobile_number,
            'governorate' => $this->governorate,
            'profile_picture_url' => $this->profile_picture_url,
            'birth_date' => $this->birth_date,
            'education_degree' => $this->education_degree, // Added new attribute
            'faculty' => $this->faculty?->name,
            'university' => $this->faculty?->university?->name,
        ];
    }
}
