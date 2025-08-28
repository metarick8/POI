<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Application;

class selectTeamsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debate_id' => ['required', 'exists:debates,id'],
            'teams' => ['required', 'array', 'size:4'],
            'teams.*' => ['required', 'array', 'size:2'],
            'teams.*.*' => [
                'required',
                'integer',
                'exists:debaters,id',
                'distinct', // Changed from Rule::distinct()
                function ($attribute, $value, $fail) {
                    // Check if debater_id is in approved applications for the debate
                    $debateId = $this->input('debate_id');
                    $isApproved = Application::where('debate_id', $debateId)
                        ->where('status', 'approved')
                        ->where('type', 'debater')
                        ->whereHas('user.debater', function ($query) use ($value) {
                            $query->where('id', $value);
                        })
                        ->exists();

                    if (!$isApproved) {
                        $fail("The debater ID {$value} is not an approved debater for the debate.");
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'teams.*.*.distinct' => 'Debater IDs must be unique across all teams.',
        ];
    }
}
