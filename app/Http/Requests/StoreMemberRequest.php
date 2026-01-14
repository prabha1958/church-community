<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
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
            'date_of_birth'       => ['required', 'date'],
            'wedding_date'       => ['nullable', 'date'],
            'area_no'       => ['required', 'string', 'max:2'],
            'email'               => [
                'required',
                'email',
                'max:255',
                Rule::unique('members', 'email'),
            ],
            'mobile_number'       => [
                'required',
                'string',
                'min:10',
                'max:10',
                Rule::unique('members', 'mobile_number'),
            ],
            'gender'              => ['required', 'string', 'max:255'],
            'spouse_name'         =>  ['nullable', 'string', 'max:255'],
            'occupation'          => ['nullable', 'string', 'max:255'],
            'status'              => ['required', Rule::in(['in_service', 'retired', 'other'])],
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

    /**
     * Prepare the data for validation.
     * Ensure boolean string values like "true" / "false" get converted so boolean rule works.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('status_flag')) {
            $val = $this->input('status_flag');
            // normalize typical boolean representations
            $this->merge([
                'status_flag' => in_array($val, [true, 'true', 1, '1', 'on', 'yes'], true) ? 1 : 0,
            ]);
        }
    }
}
