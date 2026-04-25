<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MemberRequest extends FormRequest
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

            'family_name'         => ['required', 'string', 'max:255'],
            'first_name'          => ['required', 'string', 'max:255'],
            'middle_name'         => ['nullable', 'string', 'max:255'],
            'last_name'           => ['nullable', 'string', 'max:255'],

            'couple_pic' => ['nullable', 'file', 'image', 'max:2048'],
            'wedding_date'       => ['nullable', 'date'],
            'date_of_birth'       => ['required', 'date'],

            'spouse_name'         =>  ['nullable', 'string', 'max:255'],
            'occupation'          => ['nullable', 'string', 'max:255'],
            'profile_photo'       => ['nullable', 'file', 'image', 'max:2048'],
            'membership_fee'      => ['nullable', 'numeric', 'min:0'],
            'address_flat_number' => ['nullable', 'string', 'max:255'],
            'address_premises'    => ['nullable', 'string', 'max:255'],
            'address_area'        => ['nullable', 'string', 'max:255'],
            'address_landmark'    => ['nullable', 'string', 'max:255'],
            'address_city'        => ['nullable', 'string', 'max:255'],
            'address_pin'         => ['nullable', 'digits:6'],



        ];
    }
}
