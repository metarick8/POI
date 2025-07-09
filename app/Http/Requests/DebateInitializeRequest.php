<?php

namespace App\Http\Requests;

use App\Enums\DebateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DebateInitializeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'motion_id' => ['required', 'integer', 'exists:motions,id'],
            // 'chair_judge_id' => ['required', 'integer', 'exists:judges,id'],
            'date' => ['required', 'date', 'after:' . now()->addDays(3)->toDateString()],
            'type' => ['required', 'string', Rule::enum(DebateType::class)],
            'time' => ['required', 'date_format:H:i']
            // 'panelist_judges' => ['array', 'min:1', 'max:2'],
            // 'panelist_judges.*' => ['integer', 'exists:judges,id', 'distinct'],
            // 'chair_judge_id' => ['different:panelist_judges.*']

        ];
    }
}
