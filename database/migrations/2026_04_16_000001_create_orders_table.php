<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table): void {
                $table->id();
                $table->string('order_no')->nullable()->unique();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('shipping_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
                $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();

                $table->string('customer_name_snapshot')->nullable();
                $table->string('customer_mobile_snapshot')->nullable();
                $table->string('customer_email_snapshot')->nullable();
                $table->string('customer_account_no_snapshot')->nullable();

                $table->string('shipping_label_snapshot')->nullable();
                $table->string('shipping_contact_name_snapshot')->nullable();
                $table->string('shipping_mobile_snapshot')->nullable();
                $table->string('shipping_city_snapshot')->nullable();
                $table->string('shipping_area_snapshot')->nullable();
                $table->text('shipping_address_line_snapshot')->nullable();
                $table->string('shipping_address_type_snapshot')->nullable();

                $table->string('status')->default('pending');
                $table->string('payment_status')->default('unpaid');
                $table->string('payment_method')->nullable();
                $table->string('branch')->nullable();
                $table->boolean('is_gift')->default(false);
                $table->text('gift_message')->nullable();

                $table->decimal('total_before_discount', 12, 2)->default(0);
                $table->decimal('discount_value', 12, 2)->default(0);
                $table->decimal('shipping_cost', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);

                $table->dateTime('confirmed_at')->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->dateTime('shipped_at')->nullable();
                $table->dateTime('delivered_at')->nullable();
                $table->dateTime('cancelled_at')->nullable();

                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable();
            });

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'order_no')) {
                $table->string('order_no')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('orders', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('order_no')->constrained('customers')->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'shipping_method_id')) {
                $table->foreignId('shipping_method_id')->nullable()->after('shipping_address_id')->constrained('shipping_methods')->nullOnDelete();
            }

            foreach ([
                'customer_name_snapshot' => fn (Blueprint $table) => $table->string('customer_name_snapshot')->nullable(),
                'customer_mobile_snapshot' => fn (Blueprint $table) => $table->string('customer_mobile_snapshot')->nullable(),
                'customer_email_snapshot' => fn (Blueprint $table) => $table->string('customer_email_snapshot')->nullable(),
                'customer_account_no_snapshot' => fn (Blueprint $table) => $table->string('customer_account_no_snapshot')->nullable(),
                'shipping_label_snapshot' => fn (Blueprint $table) => $table->string('shipping_label_snapshot')->nullable(),
                'shipping_contact_name_snapshot' => fn (Blueprint $table) => $table->string('shipping_contact_name_snapshot')->nullable(),
                'shipping_mobile_snapshot' => fn (Blueprint $table) => $table->string('shipping_mobile_snapshot')->nullable(),
                'shipping_city_snapshot' => fn (Blueprint $table) => $table->string('shipping_city_snapshot')->nullable(),
                'shipping_area_snapshot' => fn (Blueprint $table) => $table->string('shipping_area_snapshot')->nullable(),
                'shipping_address_line_snapshot' => fn (Blueprint $table) => $table->text('shipping_address_line_snapshot')->nullable(),
                'shipping_address_type_snapshot' => fn (Blueprint $table) => $table->string('shipping_address_type_snapshot')->nullable(),
                'is_gift' => fn (Blueprint $table) => $table->boolean('is_gift')->default(false),
                'gift_message' => fn (Blueprint $table) => $table->text('gift_message')->nullable(),
                'confirmed_at' => fn (Blueprint $table) => $table->dateTime('confirmed_at')->nullable(),
                'paid_at' => fn (Blueprint $table) => $table->dateTime('paid_at')->nullable(),
                'shipped_at' => fn (Blueprint $table) => $table->dateTime('shipped_at')->nullable(),
                'delivered_at' => fn (Blueprint $table) => $table->dateTime('delivered_at')->nullable(),
                'cancelled_at' => fn (Blueprint $table) => $table->dateTime('cancelled_at')->nullable(),
                'notes' => fn (Blueprint $table) => $table->text('notes')->nullable(),
            ] as $column => $callback) {
                if (! Schema::hasColumn('orders', $column)) {
                    $callback($table);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            foreach ([
                'order_no',
                'customer_id',
                'shipping_method_id',
                'customer_name_snapshot',
                'customer_mobile_snapshot',
                'customer_email_snapshot',
                'customer_account_no_snapshot',
                'shipping_label_snapshot',
                'shipping_contact_name_snapshot',
                'shipping_mobile_snapshot',
                'shipping_city_snapshot',
                'shipping_area_snapshot',
                'shipping_address_line_snapshot',
                'shipping_address_type_snapshot',
                'is_gift',
                'gift_message',
                'confirmed_at',
                'paid_at',
                'shipped_at',
                'delivered_at',
                'cancelled_at',
                'notes',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
