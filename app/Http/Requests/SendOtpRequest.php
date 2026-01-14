<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the input for validation:
     * - trim
     * - remove invisible characters (BOM/zero-width)
     */
    protected function prepareForValidation(): void
    {
        $contact = (string) $this->input('contact', '');
        $contact = trim($contact);
        $contact = preg_replace('/[\p{C}\x{00}-\x{1F}\x{7F}]/u', '', $contact);
        $this->merge(['contact' => $contact]);
    }

    public function rules(): array
    {
        return [
            'contact' => [
                'required',
                // custom inline validator: must be email OR phone-like digits (7-15 digits, optional leading +)
                function ($attribute, $value, $fail) {
                    // check email
                    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        return;
                    }

                    // normalize to digits and plus only
                    $normalized = preg_replace('/[^\d+]/', '', $value);
                    $normalized = preg_replace('/^\++/', '+', $normalized);

                    if ($normalized === '' || $normalized === '+') {
                        return $fail('Please provide a valid email address or mobile number.');
                    }

                    $digitsOnly = ltrim($normalized, '+');

                    // Accept 7-15 digits (E.164 compatible); adjust min if you want stricter rules
                    if (preg_match('/^[0-9]{7,15}$/', $digitsOnly)) {
                        return;
                    }

                    return $fail('Please provide a valid email address or mobile number.');
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'contact.required' => 'Please provide an email address or mobile number.',
        ];
    }
}
