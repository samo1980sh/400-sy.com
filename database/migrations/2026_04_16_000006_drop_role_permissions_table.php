<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('role_permissions');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Intentionally not restored.
    }
};
