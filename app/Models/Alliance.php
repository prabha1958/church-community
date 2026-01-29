<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alliance extends Model
{
    protected $fillable = [
        'member_id',
        'match_type',
        'alliance_type',
        'family_name',
        'first_name',
        'last_name',
        'date_of_birth',
        'profile_photo',
        'photo1',
        'photo2',
        'photo3',
        'father_name',
        'mother_name',
        'father_occupation',
        'mother_occupation',
        'educational_qualifications',
        'profession',
        'designation',
        'company_name',
        'place_of_working',
        'about_self',
        'about_family',
        'is_published',

    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'payment_date' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function member()
    {
        return $this->belongsTo(
            Member::class,
            'member_id', // FK in alliances table
            'id'         // PK in members table
        );
    }


    public function payments(): HasMany
    {
        return $this->hasMany(AlliancePayment::class, 'alliance_id');
    }

    public function applyPayment(AlliancePayment $payment): void
    {
        $this->amount = $payment->amount;
        $this->payment_id = $payment->payment_gateway_payment_id ?? null;
        $this->payment_date = $payment->paid_at;
        $this->save();
    }
}
