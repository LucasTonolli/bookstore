<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class UpdateProfilePasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', 'min:5'],
            'current_password' => ['required']
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!Hash::check($this->current_password, $this->user('sanctum')->password)) {
                    $validator->errors()->add('current_password', 'Current password is incorrect');
                }
            }
        ];
    }
}
