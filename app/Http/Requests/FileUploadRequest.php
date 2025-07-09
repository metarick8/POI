<?php

namespace App\Http\Requests;

use App\Enums\FileOwner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile_picture' => ['required', 'image'],
            'actor' => ['required', 'string', Rule::enum(FileOwner::class)],
        ];
    }
}
