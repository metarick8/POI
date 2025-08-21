<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTeamsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $debateId = $this->route('debate')->id;

        return [
            'assignments' => 'required|array|size:8',
            'assignments.*.user_id' => [
                'required',
                Rule::exists('applications', 'user_id')->where(function ($query) use ($debateId) {
                    $query->where('debate_id', $debateId);
                }),
            ],
            'assignments.*.team_id' => [
                'required',
                Rule::exists('teams', 'id'),
            ],
        ];
    }
}
