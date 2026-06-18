<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gift_card_requests')) {
            Schema::create('gift_card_requests', function (Blueprint $table): void {
                $table->id();
                $table->string('request_no')->unique();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();

                $table->string('display_name_type')->default('requester');
                $table->string('requester_name');
                $table->string('recipient_name')->nullable();
                $table->string('display_name')->nullable();
                $table->unsignedSmallInteger('card_quantity')->default(1);
                $table->string('recipient_mobile')->nullable();

                $table->decimal('card_amount', 12, 2)->default(0);
                $table->char('currency', 3)->default('SYP');
                $table->decimal('cards_subtotal', 12, 2)->default(0);

                $table->string('fulfillment_method')->default('branch_pickup');
                $table->foreignId('pickup_branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->unsignedInteger('shipping_method_id')->nullable()->index();
                $table->longText('delivery_address')->nullable();
                $table->decimal('delivery_fee', 12, 2)->default(0);

                $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
                $table->foreignId('redemption_branch_id')->constrained('branches')->restrictOnDelete();
                $table->decimal('total_amount', 12, 2)->default(0);

                $table->string('status')->default('pending')->index();
                $table->string('payment_status')->default('pending')->index();
                $table->longText('customer_notes')->nullable();
                $table->longText('admin_notes')->nullable();

                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'created_at']);
            });
        }

        if (Schema::hasTable('gift_cards')) {
            if (! Schema::hasColumn('gift_cards', 'gift_card_request_id')) {
                Schema::table('gift_cards', function (Blueprint $table): void {
                    $table->foreignId('gift_card_request_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('gift_card_requests')
                        ->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('gift_cards', 'recipient_mobile')) {
                Schema::table('gift_cards', function (Blueprint $table): void {
                    $table->string('recipient_mobile')->nullable()->after('recipient_name');
                });
            }

            if (! Schema::hasColumn('gift_cards', 'currency')) {
                Schema::table('gift_cards', function (Blueprint $table): void {
                    $table->char('currency', 3)->default('SYP')->after('balance');
                });
            }

            if (! Schema::hasColumn('gift_cards', 'redemption_branch_id')) {
                Schema::table('gift_cards', function (Blueprint $table): void {
                    $table->foreignId('redemption_branch_id')
                        ->nullable()
                        ->after('currency')
                        ->constrained('branches')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gift_cards')) {
            if (Schema::hasColumn('gift_cards', 'redemption_branch_id')) {
                Schema::table('gift_cards', function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('redemption_branch_id');
                });
            }

            if (Schema::hasColumn('gift_cards', 'gift_card_request_id')) {
                Schema::table('gift_cards', function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('gift_card_request_id');
                });
            }

            $dropColumns = array_values(array_filter([
                Schema::hasColumn('gift_cards', 'recipient_mobile') ? 'recipient_mobile' : null,
                Schema::hasColumn('gift_cards', 'currency') ? 'currency' : null,
            ]));

            if ($dropColumns !== []) {
                Schema::table('gift_cards', function (Blueprint $table) use ($dropColumns): void {
                    $table->dropColumn($dropColumns);
                });
            }
        }

        Schema::dropIfExists('gift_card_requests');
    }
};

