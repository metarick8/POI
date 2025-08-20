<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyJudgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->role === 'judge';
    }

    public function rules(): array
    {
        return [
            'debate_id' => ['required', 'integer', 'exists:debates,id'],
            'judge_type' => ['required', 'string', 'in:chair,panelist'],
        ];
    }
}
