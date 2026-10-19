<?php

namespace App\Http\Requests;

use App\Models\Project;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreProjectRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'image' => ['required', ...$this->imageRules()],
            'tags' => [
                'nullable',
                'string',
                'max:1000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $tags = Project::parseTags($value);

                    if (count($tags) > 10) {
                        $fail('Add no more than 10 tags.');
                    } elseif (collect($tags)->contains(fn (string $tag): bool => mb_strlen($tag) > 30)) {
                        $fail('Each tag must be 30 characters or fewer.');
                    }
                },
            ],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'between:0,999'],
        ];
    }

    /**
     * Get the custom messages for the image rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.uploaded' => 'The image must be 2 MB or less.',
            'image.max' => 'The image must be 2 MB or less.',
            'image.image' => 'The image must be a JPEG, PNG or WebP file.',
            'image.mimes' => 'The image must be a JPEG, PNG or WebP file.',
            'image.mimetypes' => 'The image must be a JPEG, PNG or WebP file.',
            'image.dimensions' => 'The image must be no larger than 4096 pixels on either side.',
        ];
    }

    /**
     * Get the rules every uploaded project image must pass.
     *
     * @return list<File>
     */
    protected function imageRules(): array
    {
        return [
            File::image()
                ->types(['jpeg', 'png', 'webp'])
                ->max(2048)
                ->dimensions(Rule::dimensions()->maxWidth(4096)->maxHeight(4096)),
        ];
    }
}
