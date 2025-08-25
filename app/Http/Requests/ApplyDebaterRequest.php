<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyDebaterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return auth()->user()->role === 'debater'; // we don't have this logic
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
