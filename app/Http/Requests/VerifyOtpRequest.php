<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact' => ['required', 'string', 'max:255'],
            'code'    => ['required', 'string', 'min:4', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact.required' => 'Please provide your registered email.',
            'contact.email'    => 'Contact must be a valid email address.',
            'code.required'    => 'Please provide the OTP code.',
        ];
    }
}
