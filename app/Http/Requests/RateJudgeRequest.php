<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RateJudgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judge_id'  => ['required', 'integer', 'exists:judges,id'],
            'debate_id' => ['required', 'integer', 'exists:debates,id'],
            'rate'      => ['required', 'numeric', 'min:1', 'max:5'],
            'opinion'   => ['nullable', 'string'],
        ];
    }
}
