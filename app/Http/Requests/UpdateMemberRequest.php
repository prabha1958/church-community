<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $memberId = $this->route('member')?->id;

        return [
            'family_name'         => ['required', 'string', 'max:255'],
            'first_name'          => ['required', 'string', 'max:255'],
            'middle_name'         => ['nullable', 'string', 'max:255'],
            'last_name'           => ['nullable', 'string', 'max:255'],
            'couple_pic' => ['nullable', 'file', 'image', 'max:2048'],
            'date_of_birth'       => ['required', 'date'],
            'wedding_date'       => ['nullable', 'date'],
            'area_no'       => ['required', 'string', 'max:2'],

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
