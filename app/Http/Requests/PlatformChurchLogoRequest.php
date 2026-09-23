<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlatformChurchLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'Please select a church logo.',
            'logo.image' => 'The logo must be a valid image.',
            'logo.mimes' => 'The logo must be a JPG, JPEG, PNG, or WebP image.',
            'logo.max' => 'The logo must not be larger than 5 MB.',
        ];
    }
}
