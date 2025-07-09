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
        $rules = [
            'sentence' => ['string'],
            'sub_classifications' => ['array'],
            'sub_classifications.*' => ['distinct', 'exists:sub_classifications,id']
        ];

        if ($this->method() === 'POST') {
            $rules['sentence'][] = 'required';
            $rules['sub_classifications'][] = 'required';
        }

        elseif ($this->method() === 'PATCH') {
            $rules['motion_id'] = ['required', 'exists:motions,id'];
        }

        return $rules;
    }
}
