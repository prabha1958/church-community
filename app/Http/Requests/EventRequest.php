<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // For create/update we assume admin-only; middleware should also enforce admin.
        $user = $this->user();
        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'date_of_event' => ['required', 'date'],
            'name_of_event' => ['required', 'string', 'max:255'],
            'description'   => ['sometimes', 'nullable', 'string'],
            // Accept multiple files under event_photos[] in multipart/form-data
            'event_photos' => ['sometimes', 'array'],
            'event_photos.*' => ['file', 'image', 'max:5120'], // 5MB each
        ];
    }
}
