<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTeamsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_assignments' => 'required|array|size:4',
            'team_assignments.*' => 'required|array|size:2',
            'team_assignments.*.*' => 'required|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'team_assignments.required' => 'Team assignments are required',
            'team_assignments.array' => 'Team assignments must be an array',
            'team_assignments.size' => 'Must assign exactly 4 teams',
            'team_assignments.*.required' => 'Each team assignment is required',
            'team_assignments.*.array' => 'Each team assignment must be an array',
            'team_assignments.*.size' => 'Each team must have exactly 2 debaters',
            'team_assignments.*.*.required' => 'Debater ID is required',
            'team_assignments.*.*.integer' => 'Debater ID must be an integer',
            'team_assignments.*.*.exists' => 'The selected debater does not exist',
        ];
    }
}
