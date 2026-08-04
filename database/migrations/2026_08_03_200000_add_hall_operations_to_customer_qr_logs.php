<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->where(function ($query): void {
                $query->whereNull('account_no')
                    ->orWhereRaw("TRIM(account_no) = ''");
            })
            ->orderBy('id')
            ->chunkById(100, function ($customers): void {
                foreach ($customers as $customer) {
                    $base = 'CUST-' . str_pad((string) $customer->id, 8, '0', STR_PAD_LEFT);
                    $accountNo = $base;
                    $counter = 1;

                    while (
                        DB::table('customers')
                            ->where('account_no', $accountNo)
                            ->where('id', '<>', $customer->id)
                            ->exists()
                    ) {
                        $counter++;
                        $accountNo = $base . '-' . $counter;
                    }

                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update([
                            'account_no' => $accountNo,
                            'updated_at' => now(),
                        ]);
                }
            });

        Schema::table('customer_qr_logs', function (Blueprint $table): void {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('branch')
                ->constrained('branches')
                ->nullOnDelete();

            $table->unsignedInteger('scanned_by_user_id')
                ->nullable()
                ->after('branch_id');

            $table->foreign('scanned_by_user_id', 'customer_qr_logs_scanned_by_user_id_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreignId('point_voucher_redemption_id')
                ->nullable()
                ->after('reference_no')
                ->constrained('point_voucher_redemptions')
                ->nullOnDelete();

            $table->decimal('sale_amount', 12, 2)
                ->default(0)
                ->after('discount_amount');

            $table->decimal('net_amount', 12, 2)
                ->default(0)
                ->after('sale_amount');

            $table->unique(
                ['branch_id', 'reference_no', 'action_type'],
                'customer_qr_logs_branch_reference_action_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('customer_qr_logs', function (Blueprint $table): void {
            $table->dropUnique('customer_qr_logs_branch_reference_action_unique');
            $table->dropForeign(['branch_id']);
            $table->dropForeign('customer_qr_logs_scanned_by_user_id_foreign');
            $table->dropForeign(['point_voucher_redemption_id']);
            $table->dropColumn([
                'branch_id',
                'scanned_by_user_id',
                'point_voucher_redemption_id',
                'sale_amount',
                'net_amount',
            ]);
        });
    }
};
