<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
         return [
            'sentence' => ['string', 'required'],
            'sub_classifications' => ['array', 'required'],
            'sub_classifications.*' => ['distinct', 'exists:sub_classifications,id']
        ];
    }
}
