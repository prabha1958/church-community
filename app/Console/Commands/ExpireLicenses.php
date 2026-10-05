<?php

namespace App\Console\Commands;

use App\Models\License;
use Illuminate\Console\Command;

class ExpireLicenses extends Command
{
    protected $signature = 'licenses:expire';

    protected $description =
    'Mark expired licenses as expired';

    public function handle(): int
    {
        $licenses = License::on('platform')
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate(
                'expiry_date',
                '<',
                now()->toDateString()
            )
            ->get();

        foreach ($licenses as $license) {

            $license->update([
                'status' => 'expired',
            ]);

            $this->info(
                "Expired license {$license->license_key}"
            );
        }

        $this->info(
            "Expired {$licenses->count()} license(s)."
        );

        return self::SUCCESS;
    }
}
