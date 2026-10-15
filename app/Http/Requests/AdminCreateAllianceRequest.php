<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\Alliance;
use Illuminate\Validation\Validator;

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
        return [
            'member_id' => 'required|digits:5',
            'family_name' => 'required|nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'profile_photo' => 'sometimes|image|max:5120',
            'photo1' => 'sometimes|nullable|image|max:5120',
            'photo2' => 'sometimes|nullable|image|max:5120',
            'photo3' => 'sometimes|nullable|image|max:5120',
            'father_name' => 'sometimes|nullable|string|max:255',
            'mother_name' => 'sometimes|nullable|string|max:255',
            'father_occupation' => 'sometimes|nullable|string|max:255',
            'mother_occupation' => 'sometimes|nullable|string|max:255',
            'educational_qualifications' => 'sometimes|nullable|string',
            'profession' => 'sometimes|nullable|string|max:255',
            'designation' => 'sometimes|nullable|string|max:255',
            'company_name' => 'sometimes|nullable|string|max:255',
            'place_of_working' => 'sometimes|nullable|string|max:255',
            'about_self' => 'sometimes|nullable|string',
            'about_family' => 'sometimes|nullable|string',
            'alliance_type' => ['required', 'in:bride,bridegroom'],
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {

            $memberId = $this->input('member_id');

            if (! $memberId) {
                return;
            }

            $sixMonthsAgo = Carbon::now()->subMonths(6);

            $existingAlliance = Alliance::where('member_id', $memberId)
                ->whereNotNull('payment_date')
                ->where('payment_date', '>=', $sixMonthsAgo)
                ->exists();

            if ($existingAlliance) {
                $validator->errors()->add(
                    'member_id',
                    'This member already has an active alliance within the last 6 months.'
                );
            }
        });
    }
}
