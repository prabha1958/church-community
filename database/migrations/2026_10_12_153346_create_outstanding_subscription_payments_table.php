
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('tenant');

        if (!$schema->hasTable('outstanding_subscription_payments')) {
            $schema->create(
                'outstanding_subscription_payments',
                function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('outstanding_subscription_id');
                    $table->unsignedBigInteger('member_id');
                    $table->unsignedInteger('amount');
                    $table->enum('payment_mode', ['cash', 'upi']);
                    $table->string('reference_no')->nullable();
                    $table->unsignedBigInteger('admin_id')->nullable();
                    $table->timestamp('paid_at');
                    $table->timestamps();

                    $table->foreign('outstanding_subscription_id', 'osp_outstanding_fk')
                        ->references('id')
                        ->on('outstanding_subscriptions')
                        ->cascadeOnUpdate()
                        ->restrictOnDelete();

                    $table->foreign('member_id', 'osp_member_fk')
                        ->references('id')
                        ->on('members')
                        ->cascadeOnUpdate()
                        ->restrictOnDelete();

                    $table->index(
                        ['member_id', 'paid_at'],
                        'outstanding_payment_member_date_index'
                    );

                    $table->index('admin_id', 'osp_admin_index');
                }
            );

            return;
        }

        $database = DB::connection('tenant')->getDatabaseName();

        $hasForeignKey = function (string $name) use ($database): bool {
            return DB::connection('tenant')->table('information_schema.KEY_COLUMN_USAGE')
                ->where('CONSTRAINT_SCHEMA', $database)
                ->where('TABLE_NAME', 'outstanding_subscription_payments')
                ->where('CONSTRAINT_NAME', $name)
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->exists();
        };

        $hasIndex = function (string $name): bool {
            return DB::connection('tenant')->table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::connection('tenant')->getDatabaseName())
                ->where('TABLE_NAME', 'outstanding_subscription_payments')
                ->where('INDEX_NAME', $name)
                ->exists();
        };

        if (!$hasForeignKey('osp_outstanding_fk')) {
            Schema::connection('tenant')->table(
                'outstanding_subscription_payments',
                function (Blueprint $table) {
                    $table->foreign('outstanding_subscription_id', 'osp_outstanding_fk')
                        ->references('id')
                        ->on('outstanding_subscriptions')
                        ->cascadeOnUpdate()
                        ->restrictOnDelete();
                }
            );
        }

        if (!$hasForeignKey('osp_member_fk')) {
            Schema::connection('tenant')->table(
                'outstanding_subscription_payments',
                function (Blueprint $table) {
                    $table->foreign('member_id', 'osp_member_fk')
                        ->references('id')
                        ->on('members')
                        ->cascadeOnUpdate()
                        ->restrictOnDelete();
                }
            );
        }

        if (!$hasIndex('outstanding_payment_member_date_index')) {
            Schema::connection('tenant')->table(
                'outstanding_subscription_payments',
                function (Blueprint $table) {
                    $table->index(
                        ['member_id', 'paid_at'],
                        'outstanding_payment_member_date_index'
                    );
                }
            );
        }

        if (!$hasIndex('osp_admin_index')) {
            Schema::connection('tenant')->table(
                'outstanding_subscription_payments',
                function (Blueprint $table) {
                    $table->index('admin_id', 'osp_admin_index');
                }
            );
        }
    }

    public function down(): void
    {
        Schema::connection('tenant')
            ->dropIfExists('outstanding_subscription_payments');
    }
};
