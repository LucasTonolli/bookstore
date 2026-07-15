<?php

namespace App\Http\Requests;

use App\Enums\Roles;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListUsersRequest extends FormRequest
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
            'name' => ['string', 'max:255'],
            'email' => ['string', 'max:255'],
            'role' => ['in:' . implode(',', $roles)],
            'sort'      => ['sometimes', 'in:name,email,role,created_at'],
            'direction' => ['sometimes', 'in:asc,desc'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page'      => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
