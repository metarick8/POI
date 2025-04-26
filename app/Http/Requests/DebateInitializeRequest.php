<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DebateInitializeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution_id' => ['required', 'integer', 'exists:resolutions, id'],
            'main_judge_id' => ['required', 'integer', 'exists:judges, id'],
            'date' => ['required', 'date', 'after:' . now()->addDays(3)->toDateString()],
            'wing_judges' => ['array', 'size:1,2'],
            'wing_judges.*' => ['integer', 'exists:judges,id']
        ];
    }
}
