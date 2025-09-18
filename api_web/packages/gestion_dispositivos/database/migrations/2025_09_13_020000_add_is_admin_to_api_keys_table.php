<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('api_keys', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('plain_preview');
                $table->index('is_admin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            if (Schema::hasColumn('api_keys', 'is_admin')) {
                $table->dropIndex(['is_admin']);
                $table->dropColumn('is_admin');
            }
        });
    }
};
