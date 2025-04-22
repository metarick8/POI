<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DebaterRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'coach_id' => 'required|integer|exists:coaches,id',
        ];
    }
}
