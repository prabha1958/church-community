<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'middle_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],

            'wedding_date' => [
                'nullable',
                'date',
            ],

            'spouse_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'occupation' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address_flat_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address_premises' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address_area' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address_landmark' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address_city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address_pin' => [
                'nullable',
                'digits:6',
            ],

            'profile_photo' => [
                'nullable',
                'file',
                'image',
                'max:2048',
            ],

            'couple_pic' => [
                'nullable',
                'file',
                'image',
                'max:2048',
            ],
        ];
    }
}
