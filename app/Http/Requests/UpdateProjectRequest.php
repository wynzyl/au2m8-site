<?php

namespace App\Http\Requests;

class UpdateProjectRequest extends StoreProjectRequest
{
    /**
     * Get the validation rules that apply to the request; a new image is optional.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'image' => ['nullable', ...$this->imageRules()],
        ]);
    }
}
