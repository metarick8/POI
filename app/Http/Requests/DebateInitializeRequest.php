<?php

namespace App\Http\Requests;

use App\Enums\DebateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DebateInitializeRequest extends FormRequest
{
    public function authorize()
    {
        $user = Auth::guard('admin')->user();
        return $isAuthorized = Auth::guard('admin')->check();
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after:' . now()->addDays(3)->toDateString()],
            'type' => ['required', 'string', Rule::enum(DebateType::class)],
            'time' => ['required', 'date_format:H:i'],
        ];
    }
}
