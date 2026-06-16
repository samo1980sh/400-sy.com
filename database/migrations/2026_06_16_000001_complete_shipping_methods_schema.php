<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipping_methods')) {
            return;
        }

        $missingCode = ! Schema::hasColumn('shipping_methods', 'code');
        $missingNotes = ! Schema::hasColumn('shipping_methods', 'notes');
        $missingCreatedAt = ! Schema::hasColumn('shipping_methods', 'created_at');
        $missingUpdatedAt = ! Schema::hasColumn('shipping_methods', 'updated_at');

        if ($missingCode || $missingNotes || $missingCreatedAt || $missingUpdatedAt) {
            Schema::table('shipping_methods', function (Blueprint $table) use (
                $missingCode,
                $missingNotes,
                $missingCreatedAt,
                $missingUpdatedAt,
            ): void {
                if ($missingCode) {
                    // Nullable temporarily so existing rows can be completed safely.
                    $table->string('code')->nullable()->unique()->after('name_en');
                }

                if ($missingNotes) {
                    $table->text('notes')->nullable()->after('active');
                }

                if ($missingCreatedAt) {
                    $table->timestamp('created_at')->nullable()->after('notes');
                }

                if ($missingUpdatedAt) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
        }

        $usedCodes = [];

        DB::table('shipping_methods')
            ->whereNotNull('code')
            ->where('code', '<>', '')
            ->orderBy('id')
            ->get(['id', 'code'])
            ->each(function (object $method) use (&$usedCodes): void {
                $code = trim((string) $method->code);

                if ($code !== '') {
                    $usedCodes[$code] = true;
                }
            });

        DB::table('shipping_methods')
            ->where(function ($query): void {
                $query->whereNull('code')->orWhere('code', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($methods) use (&$usedCodes): void {
                foreach ($methods as $method) {
                    $baseCode = Str::slug((string) ($method->name_en ?: $method->name_ar));

                    if ($baseCode === '') {
                        $baseCode = 'shipping-method-'.$method->id;
                    }

                    $code = $baseCode;
                    $suffix = 2;

                    while (isset($usedCodes[$code])) {
                        $code = $baseCode.'-'.$suffix;
                        $suffix++;
                    }

                    DB::table('shipping_methods')
                        ->where('id', $method->id)
                        ->update(['code' => $code]);

                    $usedCodes[$code] = true;
                }
            }, 'id');

        // Match the current canonical schema after all legacy rows have codes.
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `shipping_methods` MODIFY `code` VARCHAR(255) NOT NULL');
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: these columns may contain production data.
    }
};
