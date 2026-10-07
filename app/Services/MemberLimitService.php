<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MemberLimitService
{
    /**
     * Default member limit if the tenant setting
     * is missing for some reason.
     */
    protected const DEFAULT_LIMIT = 3000;

    /**
     * Get the configured maximum number of members
     * for the current tenant.
     */
    public function getLimit(): int
    {
        $limit = DB::connection('tenant')
            ->table('tenant_settings')
            ->value('max_members');

        return $limit !== null
            ? (int) $limit
            : self::DEFAULT_LIMIT;
    }

    /**
     * Get the current number of members
     * in the current tenant database.
     */
    public function getCurrentCount(): int
    {
        return Member::on('tenant')->count();
    }

    /**
     * Get the number of member slots still available.
     */
    public function getAvailableSlots(): int
    {
        $limit = $this->getLimit();
        $currentCount = $this->getCurrentCount();

        return max(0, $limit - $currentCount);
    }

    /**
     * Determine whether the requested number
     * of additional members can be added.
     *
     * Example:
     *
     * Current members = 2995
     * Limit           = 3000
     *
     * canAdd(5)  = true
     * canAdd(6)  = false
     */
    public function canAdd(int $numberOfMembers = 1): bool
    {
        if ($numberOfMembers < 1) {
            return true;
        }

        $limit = $this->getLimit();
        $currentCount = $this->getCurrentCount();

        return ($currentCount + $numberOfMembers) <= $limit;
    }

    /**
     * Validate that members can be added.
     *
     * Throws RuntimeException if the tenant member
     * limit would be exceeded.
     */
    public function ensureCanAdd(int $numberOfMembers = 1): void
    {
        if ($numberOfMembers < 1) {
            return;
        }

        $limit = $this->getLimit();
        $currentCount = $this->getCurrentCount();
        $availableSlots = max(0, $limit - $currentCount);

        if (($currentCount + $numberOfMembers) > $limit) {
            throw new RuntimeException(
                "Member limit exceeded. " .
                    "This church can have a maximum of {$limit} members. " .
                    "Current members: {$currentCount}. " .
                    "Available slots: {$availableSlots}. " .
                    "Requested: {$numberOfMembers}."
            );
        }
    }

    /**
     * Execute a member import safely inside a tenant
     * database transaction.
     *
     * The callback should perform the actual inserts.
     *
     * Example:
     *
     * $service->import(function () use ($rows) {
     *     foreach ($rows as $row) {
     *         Member::create($row);
     *     }
     * });
     *
     * The entire transaction is rolled back if the
     * member limit is exceeded or the callback fails.
     *
     * IMPORTANT:
     * The number of members being imported must be
     * passed to this method.
     */
    public function import(
        int $numberOfMembers,
        callable $callback
    ): mixed {
        if ($numberOfMembers < 1) {
            return $callback();
        }

        return DB::connection('tenant')->transaction(function () use (
            $numberOfMembers,
            $callback
        ) {
            /*
             * Lock the tenant settings row.
             *
             * This prevents two simultaneous imports from
             * both passing the member-limit check.
             */
            $settings = DB::connection('tenant')
                ->table('tenant_settings')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            /*
             * If the settings row doesn't exist, use the
             * default limit.
             */
            $limit = $settings
                ? (int) $settings->max_members
                : self::DEFAULT_LIMIT;

            /*
             * Count members while we are inside the
             * transaction.
             */
            $currentCount = Member::on('tenant')->count();

            $availableSlots = max(
                0,
                $limit - $currentCount
            );

            /*
             * Reject the entire import if it would exceed
             * the tenant limit.
             */
            if (($currentCount + $numberOfMembers) > $limit) {
                throw new RuntimeException(
                    "Member import rejected. " .
                        "This church can have a maximum of {$limit} members. " .
                        "Current members: {$currentCount}. " .
                        "Available slots: {$availableSlots}. " .
                        "Import contains: {$numberOfMembers} members."
                );
            }

            /*
             * Execute the actual import.
             *
             * If this throws an exception, the transaction
             * is automatically rolled back.
             */
            return $callback();
        });
    }

    /**
     * Return the current member quota information.
     *
     * Useful for admin dashboards and CSV preview.
     */
    public function getStatus(): array
    {
        $limit = $this->getLimit();
        $currentCount = $this->getCurrentCount();

        return [
            'limit' => $limit,
            'current_count' => $currentCount,
            'available_slots' => max(
                0,
                $limit - $currentCount
            ),
            'is_full' => $currentCount >= $limit,
        ];
    }


    /**
     * Check the member limit while inside an existing
     * tenant database transaction.
     *
     * The tenant_settings row is locked so that two
     * simultaneous imports cannot both pass the quota check.
     */
    public function ensureCanAddInsideTransaction(
        int $numberOfMembers
    ): void {
        if ($numberOfMembers < 1) {
            return;
        }

        $settings = DB::connection('tenant')
            ->table('tenant_settings')
            ->where('id', 1)
            ->lockForUpdate()
            ->first();

        $limit = $settings
            ? (int) $settings->max_members
            : self::DEFAULT_LIMIT;

        $currentCount = Member::on('tenant')->count();

        $availableSlots = max(
            0,
            $limit - $currentCount
        );

        if (($currentCount + $numberOfMembers) > $limit) {
            throw new RuntimeException(
                "Member import rejected. " .
                    "This church can have a maximum of {$limit} members. " .
                    "Current members: {$currentCount}. " .
                    "Available slots: {$availableSlots}. " .
                    "Import contains: {$numberOfMembers} members."
            );
        }
    }
}
