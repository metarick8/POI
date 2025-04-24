<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRegisterRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'profile_picture_url' => ['string', 'url', 'regex:/^https:\/\/res\.cloudinary\.com\/dts4tnvo4\/image\/upload\/v\d+\/Profile%20picture\/(user|debater|coach|judge)\/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|gif|bmp|webp)$/'],
            'public_id' => ['string', 'required_with:profile_picture_url', 'regex:/^Profile picture\/(user|debater|coach|judge)\/[a-zA-Z0-9_-]+$/'],
        ];
    }
}
