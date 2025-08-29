<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DebatePreparationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "motion_id" => ['required', 'exists:motions,id'],
            "positions" => ['required', 'array'],
            "positions.*.team_id" => ['required', 'exists:teams,id'],
            "positions.*.debater_ids" => ['required', 'array', 'size:2'],
            "positions.*.debater_ids.*" => [
                'required',
                'exists:users,id',
                Rule::exists('debaters', 'user_id')->where(function ($query) {
                    return $query->where('status', 'active'); // Assuming a status field, adjust as needed
                })
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'positions.*.debater_ids.size' => 'Each team must have exactly 2 debaters.',
            'positions.*.debater_ids.*.exists' => 'One or more debater IDs are invalid or not active.',
        ];
    }
}
