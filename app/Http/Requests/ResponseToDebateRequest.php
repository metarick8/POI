<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponseToDebateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "response" => ['required', 'boolean'],
            'application_id' => ['required', 'integer', 'exists:applications,id']
        ];
    }
}
