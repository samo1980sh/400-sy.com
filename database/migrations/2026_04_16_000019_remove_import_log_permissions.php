<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['import-batches.view-any', 'import-rows.view-any'])
            ->pluck('id')
            ->all();

        if ($permissionIds === []) {
            return;
        }

        DB::table('permission_role')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();
    }

    public function down(): void
    {
        DB::table('permissions')->insert([
            [
                'name' => 'سجل الاستيراد عرض',
                'slug' => 'import-batches.view-any',
                'group' => 'دفعات الاستيراد',
                'description' => null,
                'is_active' => true,
            ],
            [
                'name' => 'أسطر الاستيراد عرض',
                'slug' => 'import-rows.view-any',
                'group' => 'أسطر الاستيراد',
                'description' => null,
                'is_active' => true,
            ],
        ]);
    }
};
