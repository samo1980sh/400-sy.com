<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_qr_logs', function (Blueprint $table): void {
            $table->boolean('is_suspicious')->default(false)->after('notes');
            $table->text('suspicious_reason')->nullable()->after('is_suspicious');
            $table->index(['is_suspicious', 'scanned_at'], 'customer_qr_logs_suspicious_scanned_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('customer_qr_logs', function (Blueprint $table): void {
            $table->dropIndex('customer_qr_logs_suspicious_scanned_at_index');
            $table->dropColumn(['is_suspicious', 'suspicious_reason']);
        });
    }
};
