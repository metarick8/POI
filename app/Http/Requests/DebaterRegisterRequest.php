<?php

namespace App\Http\Requests;

class DebaterRegisterRequest extends UserProfileRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'coach_id' => ['integer', 'exists:coaches,id'],
            'governorate' => ['required']
        ]);
    }
}
