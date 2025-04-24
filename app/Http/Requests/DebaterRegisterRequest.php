<?php

namespace App\Http\Requests;

class DebaterRegisterRequest extends UserRegisterRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'coach_id' => ['required', 'integer', 'exists:coaches,id'],
        ]);
    }
}
