<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debate_id' => 'required|exists:debates,id',
            'reporter_type' => ['required', Rule::in(['debater', 'judge'])],
            'issue_type' => ['required', Rule::in(['technical', 'behavioral', 'procedural', 'other'])],
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'debate_id.required' => 'Debate ID is required',
            'debate_id.exists' => 'The selected debate does not exist',
            'reporter_type.required' => 'Reporter type is required',
            'reporter_type.in' => 'Reporter type must be either debater or judge',
            'issue_type.required' => 'Issue type is required',
            'issue_type.in' => 'Issue type must be technical, behavioral, procedural, or other',
            'title.required' => 'Report title is required',
            'title.max' => 'Report title cannot exceed 255 characters',
            'description.required' => 'Report description is required',
            'description.max' => 'Report description cannot exceed 2000 characters',
        ];
    }
}