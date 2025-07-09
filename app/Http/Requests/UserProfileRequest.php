<?php

namespace App\Http\Requests;

use App\Enums\SyrianGovernorate;
use App\Enums\EducationDegree;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        if ($this->method() === 'POST')
            $rules = [
                'first_name' => ['required', 'string', 'max:50'],
                'last_name' => ['required', 'string', 'max:50'],
                'email' => ['required', 'string', 'email', 'max:100', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'profile_picture_url' => ['string', 'url', 'regex:/^https:\/\/res\.cloudinary\.com\/dts4tnvo4\/image\/upload\/v\d+\/Profile%20picture\/(user|debater|coach|judge)\/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|gif|bmp|webp)$/'],
                'public_id' => ['string', 'required_with:profile_picture_url', 'regex:/^Profile picture\/(user|debater|coach|judge)\/[a-zA-Z0-9_-]+$/'],
                'governorate' => ['required', 'string', Rule::enum(SyrianGovernorate::class)],
                'mobile_number' => ['required', 'string', 'size:10', 'regex:/^09\d{8}$/'],
                'birth_date' => ['required', 'date'],
                'faculty_id' => ['integer', 'exists:faculties,id'],
                'university_id' => ['integer', 'exists:universities,id'],
                'university_name' => ['string', 'required_with:faculty_name'],
                'faculty_name' => ['string', 'required_with:university_id,university_name'],
                'education_degree' => ['required', Rule::enum(EducationDegree::class)]
            ];

        elseif ($this->method() === 'PATCH') {
            $rules = [
                'profile_picture_url' => ['string', 'url', 'regex:/^https:\/\/res\.cloudinary\.com\/dts4tnvo4\/image\/upload\/v\d+\/Profile%20picture\/(user|debater|coach|judge)\/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|gif|bmp|webp)$/'],
                'public_id' => ['string', 'required_with:profile_picture_url', 'regex:/^Profile picture\/(user|debater|coach|judge)\/[a-zA-Z0-9_-]+$/'],
                'mobile_number' => ['string', 'size:10', 'regex:/^09\d{8}$/'],
                'governorate' => ['string', Rule::enum(SyrianGovernorate::class)]
            ];
        }

        return $rules;
    }
}
