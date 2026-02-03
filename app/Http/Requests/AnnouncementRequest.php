<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can create/update/delete
        $user = $this->user();
        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'exp_date' => ['required', 'date'],
            'picture' => ['sometimes', 'image', 'max:5120'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],

        ];
    }
}
