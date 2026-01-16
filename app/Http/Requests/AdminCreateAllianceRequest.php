<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminCreateAllianceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ensure caller is admin (admin middleware should be applied too)
        $user = $this->user();
        return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
    }

    public function rules(): array
    {
        return array_merge((new \App\Http\Requests\CreateAllianceRequest)->rules(), [
            // admin may supply a member_id to attach this alliance to any member
            'member_id' => ['required', 'integer', 'exists:members,id'],

        ]);
    }
}
