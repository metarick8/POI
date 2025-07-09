<?php

namespace App\Http\Requests;


class CoachRegisterRequest extends UserProfileRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(parent::rules());
    }
}
