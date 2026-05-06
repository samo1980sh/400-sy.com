<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_charts', function (Blueprint $table): void {
            if (! Schema::hasColumn('measurement_charts', 'measurement_chart_group_id')) {
                $table
                    ->foreignId('measurement_chart_group_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('measurement_chart_groups')
                    ->cascadeOnDelete();

                $table->unique(
                    ['measurement_chart_group_id', 'size_code'],
                    'measurement_charts_group_size_unique'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('measurement_charts', function (Blueprint $table): void {
            $table->dropUnique('measurement_charts_group_size_unique');
            $table->dropForeign(['measurement_chart_group_id']);
            $table->dropColumn('measurement_chart_group_id');
        });
    }
};
