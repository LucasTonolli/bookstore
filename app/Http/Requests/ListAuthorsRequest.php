<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListAuthorsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'last_name' => ['string', 'max:255'],
            'nacionality' => ['string', 'max:255'],
            'birth_date' => ['date'],
            'sort'      => ['sometimes', 'in:name,created_at'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page'      => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
