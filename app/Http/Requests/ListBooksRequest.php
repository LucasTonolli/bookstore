<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListBooksRequest extends FormRequest
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
            'title' => ['string', 'max:255'],
            'subtitle' => ['string', 'max:255'],
            'published_year' => ['integer', 'min:0'],
            'isbn' => ['string', 'max:13'],
            'pages' => ['integer', 'min:1'],
            'edition' => ['string', 'max:255'],
            'publisher' => ['string', 'max:255'],
            'language' => ['string', 'max:255'],
            'description' => ['string'],
            'sort' => ['sometimes', 'in:title,created_at'],
            'direction' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
