<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAllianceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // only authenticated members can create for themselves
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'family_name' => 'required|nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'profile_photo' => 'sometimes|image|max:5120',
            'photo1' => 'sometimes|nullable|image|max:5120',
            'photo2' => 'sometimes|nullable|image|max:5120',
            'photo3' => 'sometimes|nullable|image|max:5120',
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
            'alliance_type' => ['required', 'in:bride,bridegroom'],
        ];
    }
}
