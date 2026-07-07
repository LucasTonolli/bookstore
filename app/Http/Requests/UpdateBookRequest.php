<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'subtitle' => ['string', 'max:255'],
            'published_year' => ['integer', 'min:0'],
            'isbn' => ['sometimes', 'string', 'max:13', Rule::unique('books')->ignore($this->book->id)],
            'pages' => ['sometimes', 'integer', 'min:1'],
            'edition' => ['string', 'max:255'],
            'publisher' => ['string', 'max:255'],
            'language' => ['string', 'max:255'],
            'description' => ['string'],
            'authors' => ['sometimes', 'array', 'min:1', 'exists:authors,id'],
            'genres' => ['sometimes', 'array', 'min:1', 'exists:genres,id'],
        ];
    }
}
