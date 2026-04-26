<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table): void {
                if (! Schema::hasColumn('roles', 'slug')) {
                    $table->string('slug')->nullable()->after('name');
                }

                if (! Schema::hasColumn('roles', 'description')) {
                    $table->text('description')->nullable()->after('slug');
                }

                if (! Schema::hasColumn('roles', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('description');
                }
            });

            if (Schema::hasColumn('roles', 'slug')) {
                Schema::table('roles', function (Blueprint $table): void {
                    try {
                        $table->unique('slug', 'roles_slug_unique');
                    } catch (\Throwable $exception) {
                        // Index may already exist in a prebuilt table.
                    }
                });
            }
        }

        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table): void {
                if (! Schema::hasColumn('permissions', 'slug')) {
                    $table->string('slug')->nullable()->after('name');
                }

                if (! Schema::hasColumn('permissions', 'group')) {
                    $table->string('group')->nullable()->after('slug');
                }

                if (! Schema::hasColumn('permissions', 'description')) {
                    $table->text('description')->nullable()->after('group');
                }

                if (! Schema::hasColumn('permissions', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('description');
                }
            });

            if (Schema::hasColumn('permissions', 'slug')) {
                Schema::table('permissions', function (Blueprint $table): void {
                    try {
                        $table->unique('slug', 'permissions_slug_unique');
                    } catch (\Throwable $exception) {
                        // Index may already exist in a prebuilt table.
                    }
                });
            }
        }
    }

    public function down(): void
    {
        // Keep schema changes non-destructive for safety.
    }
};
