<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PoorFeedingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins may create/update/delete. Public index/show are open.
        $user = $this->user();
        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'date_of_event'       => ['required', 'date'],
            'sponsored_by'        => ['required', 'integer', 'exists:members,id'],
            'no_of_persons_fed'   => ['required', 'integer', 'min:0'],
            'event_photos'        => ['sometimes', 'array'],
            'event_photos.*'      => ['file', 'image', 'max:5120'],
            'brief_description'   => ['required', 'string'],
        ];
    }
}
