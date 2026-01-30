<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PastorRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // For update, `order_no` uniqueness not enforced here; add rule if needed.
        return [
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'qualifications' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_of_joining' => ['required', 'date'],
            'date_of_leaving' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_of_joining'],
            'past_service_description' => ['sometimes', 'nullable', 'string'],
            'photo' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'order_no' => ['required', 'integer'],
        ];
    }
}
