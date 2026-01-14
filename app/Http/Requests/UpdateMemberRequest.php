<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'family_name'         => ['nullable', 'string', 'max:255'],
            'first_name'          => ['nullable', 'string', 'max:255'],
            'last_name'           => ['nullable', 'string', 'max:255'],
            'date_of_birth'       => ['nullable', 'date'],
            'wedding_date'        => ['nullable', 'date'],

            // NEW fields
            'spouse_name'         => ['nullable', 'string', 'max:255'],
            'gender'              => ['nullable', 'in:male,female,other'],
            'status_flag'         => ['required', 'boolean'],


            // file/photo if present in your app
            'profile_photo'       => ['nullable', 'image', 'max:5120'],
            'couple_pic' => ['nullable', 'file', 'image', 'max:2048'],
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
