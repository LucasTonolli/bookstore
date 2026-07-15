<?php

namespace App\Http\Requests;

use App\Enums\Roles;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === Roles::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roles = Roles::cases();
        $roles = array_map(fn(Roles $role) => $role->value, $roles);
        return [
            'name' => ['sometimes', 'string', 'min:3', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($this->user->id)],
            'password' => ['sometimes', 'min:5'],
            'role' => ['sometimes', 'in:' . implode(',', $roles)],
        ];
    }
}
