<?php

namespace App\Http\Requests;

use App\Enums\DebateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListDebatesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'sometimes|array',
            'status.*' => [new Enum(DebateStatus::class)],
        ];
    }

    public function validatedStatus(): array
    {
        return $this->input('status', []); // always returns an array
    }
}
