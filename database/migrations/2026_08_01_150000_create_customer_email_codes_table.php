<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_email_codes')) {
            return;
        }

        Schema::create('customer_email_codes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('email')->index();
            $table->string('purpose', 30)->index();
            $table->char('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->string('requested_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['email', 'purpose', 'consumed_at'], 'customer_email_codes_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_email_codes');
    }
};
