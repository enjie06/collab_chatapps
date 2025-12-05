<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversation_user', function (Blueprint $table) {
            if (!Schema::hasColumn('conversation_user', 'last_cleared_at')) {
                $table->timestamp('last_cleared_at')->nullable()->after('deleted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_user', function (Blueprint $table) {
            $table->dropColumn('last_cleared_at');
        });
    }
};
