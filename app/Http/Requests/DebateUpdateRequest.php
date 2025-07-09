<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DebateUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'date' => 'sometimes|date|after_or_equal:today',
            'time' => 'sometimes|date_format:H:i:s',
            'type' => 'sometimes|in:onsite,online',
            'motion_id' => 'sometimes|exists:motions,id',
            'chair_judge_id' => 'sometimes|exists:judges,id',
            'panelist_judges' => 'sometimes|array',
            'panelist_judges.*' => 'exists:judges,id',
        ];
    }
}
