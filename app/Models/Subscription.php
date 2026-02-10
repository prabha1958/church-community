<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        'member_id',
        'financial_year',
        'monthly_fee',
        // months columns will be mass assigned in controller appropriately

        // payment columns
        'apr_payment_id',
        'apr_paid_at',
        'may_payment_id',
        'may_paid_at',
        'jun_payment_id',
        'jun_paid_at',
        'jul_payment_id',
        'jul_paid_at',
        'aug_payment_id',
        'aug_paid_at',
        'sep_payment_id',
        'sep_paid_at',
        'oct_payment_id',
        'oct_paid_at',
        'nov_payment_id',
        'nov_paid_at',
        'dec_payment_id',
        'dec_paid_at',
        'jan_payment_id',
        'jan_paid_at',
        'feb_payment_id',
        'feb_paid_at',
        'mar_payment_id',
        'mar_paid_at',

    ];

    protected $casts = [
        'raw' => 'array',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Return months array of FY in order Apr -> Mar
     */
    public static function fyMonths(): array
    {
        return ['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'];
    }

    /**
     * Given a Date/Carbon, return the month key name in our subscription row (apr..mar).
     * $date: Carbon | null => default now
     */
    public static function monthKeyForDate($date = null): string
    {
        $dt = $date ? Carbon::parse($date) : Carbon::now();
        $mon = (int)$dt->format('n'); // 1..12
        // months mapping: Apr (4) -> 'apr', ... Mar(3) -> 'mar'
        $map = [
            1 => 'jan',
            2 => 'feb',
            3 => 'mar',
            4 => 'apr',
            5 => 'may',
            6 => 'jun',
            7 => 'jul',
            8 => 'aug',
            9 => 'sep',
            10 => 'oct',
            11 => 'nov',
            12 => 'dec'
        ];
        return $map[$mon];
    }

    /**
     * Return financial year string (like '2025-2026') for a given date (or now).
     * FY starts April 1.
     */
    public static function financialYearForDate($date = null): string
    {
        $dt = $date ? Carbon::parse($date) : Carbon::now();
        $year = (int)$dt->format('Y');
        $month = (int)$dt->format('n');
        if ($month >= 4) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }

    /**
     * Return array of unpaid month keys up to a given date (inclusive).
     * If you want all unpaid months in the FY, pass endDate=null to include whole FY.
     */
    public function unpaidMonthsUpTo($endDate = null): array
    {
        // determine months to check in order from Apr to current month in FY
        $months = self::fyMonths();

        // compute index up to which to check
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now();
        // If end belongs to next calendar year but same FY, still okay.
        // Build FY start and then iterate until end (cap at Mar).
        $fy = $this->financial_year; // string '2025-2026'
        [$startYear, $endYear] = explode('-', $fy);
        $fyStart = Carbon::create((int)$startYear, 4, 1);
        $fyEnd = Carbon::create((int)$endYear, 3, 31, 23, 59, 59);

        // if endDate outside FY, clamp
        if ($end->lt($fyStart)) {
            return []; // nothing due yet
        }
        if ($end->gt($fyEnd)) {
            $end = $fyEnd;
        }

        // Determine months from Apr up to end (inclusive) within this FY
        $result = [];
        foreach ($months as $m) {
            $paymentCol = "{$m}_payment_id";
            $paidAtCol = "{$m}_paid_at";

            // find the month number for this $m in the actual calendar (use fyStart offset)
            // We'll use Carbon to get month start for each $m:
            $monthNumber = self::monthNumberFromKey($m, (int)$startYear);
            $monthStart = Carbon::createFromDate($monthNumber['year'], $monthNumber['month'], 1);
            if ($monthStart->gt($end)) break;

            if (empty($this->$paymentCol)) {
                $result[] = $m;
            }
        }
        return $result;
    }

    /**
     * Helper: map key like 'apr' to calendar month and year in this FY.
     */
    public static function monthNumberFromKey(string $key, int $fyStartYear): array
    {
        $map = [
            'apr' => ['month' => 4, 'year' => $fyStartYear],
            'may' => ['month' => 5, 'year' => $fyStartYear],
            'jun' => ['month' => 6, 'year' => $fyStartYear],
            'jul' => ['month' => 7, 'year' => $fyStartYear],
            'aug' => ['month' => 8, 'year' => $fyStartYear],
            'sep' => ['month' => 9, 'year' => $fyStartYear],
            'oct' => ['month' => 10, 'year' => $fyStartYear],
            'nov' => ['month' => 11, 'year' => $fyStartYear],
            'dec' => ['month' => 12, 'year' => $fyStartYear],
            'jan' => ['month' => 1, 'year' => $fyStartYear + 1],
            'feb' => ['month' => 2, 'year' => $fyStartYear + 1],
            'mar' => ['month' => 3, 'year' => $fyStartYear + 1],
        ];
        return $map[$key];
    }

    public function monthStatus(): array
    {
        $months = self::fyMonths(); // ['apr','may',...]
        $out = [];

        foreach ($months as $m) {
            $out[] = [
                'month' => $m,
                'paid'  => ! empty($this->{"{$m}_payment_id"}),
                'paid_at' => $this->{"{$m}_paid_at"},
            ];
        }

        return $out;
    }
}
