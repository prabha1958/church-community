<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateAllianceRequest extends FormRequest
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
        return [
            'alliance_type' => 'required|string|max:255',
            'family_name' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'date_of_birth' => 'sometimes|date',

            'father_name' => 'sometimes|nullable|string|max:255',
            'mother_name' => 'sometimes|nullable|string|max:255',
            'father_occupation' => 'sometimes|nullable|string|max:255',
            'mother_occupation' => 'sometimes|nullable|string|max:255',

            'educational_qualifications' => 'sometimes|nullable|string',
            'profession' => 'sometimes|nullable|string|max:255',
            'designation' => 'sometimes|nullable|string|max:255',
            'company_name' => 'sometimes|nullable|string|max:255',
            'place_of_working' => 'sometimes|nullable|string|max:255',

            'about_self' => 'sometimes|nullable|string',
            'about_family' => 'sometimes|nullable|string',

            'profile_photo' => 'nullable|image|max:5120',
            'photo1' => 'nullable|image|max:5120',
            'photo2' => 'nullable|image|max:5120',
            'photo3' => 'nullable|image|max:5120',

            'is_published' => 'sometimes|boolean',
        ];
    }
}
