<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitResultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'winner' => 'required|string|max:255',
            'summary' => 'required|string|max:2000',
            'ranks' => 'required|array|size:4',
            'ranks.*' => 'required|integer|between:1,4',
        ];
    }

    public function messages(): array
    {
        return [
            'winner.required' => 'Winner announcement is required',
            'winner.string' => 'Winner must be a string',
            'winner.max' => 'Winner announcement cannot exceed 255 characters',
            'summary.required' => 'Debate summary is required',
            'summary.string' => 'Summary must be a string',
            'summary.max' => 'Summary cannot exceed 2000 characters',
            'ranks.required' => 'Team rankings are required',
            'ranks.array' => 'Rankings must be an array',
            'ranks.size' => 'Must provide rankings for exactly 4 teams',
            'ranks.*.required' => 'Each team rank is required',
            'ranks.*.integer' => 'Team rank must be an integer',
            'ranks.*.between' => 'Team rank must be between 1 and 4',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (count(array_unique($this->ranks)) !== 4) {
                $validator->errors()->add('ranks', 'All team ranks must be unique.');
            }
        });
    }
}