<?php

namespace App\Http\Requests;


class JudgeRegisterRequest extends UserProfileRequest
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
