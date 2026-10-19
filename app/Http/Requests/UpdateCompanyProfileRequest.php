<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Every signed-in user is a site owner; the route is protected by the auth middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array{
     *     name: array<int, string>,
     *     tagline: array<int, string>,
     *     description: array<int, string>,
     *     founded_year: array<int, string>,
     *     mission: array<int, string>,
     *     email: array<int, string>,
     *     phone: array<int, string>,
     *     address: array<int, string>,
     * }
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'tagline' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:500'],
            'founded_year' => ['required', 'integer', 'between:1900,'.now()->year],
            'mission' => ['required', 'string', 'max:500'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+().\-\s]+$/'],
            'address' => ['required', 'string', 'max:500'],
        ];
    }
}
