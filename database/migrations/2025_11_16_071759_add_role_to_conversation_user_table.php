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
            // kalau belum ada kolom role
            if (!Schema::hasColumn('conversation_user', 'role')) {
                $table->string('role')->default('member')->after('user_id');
            }

            // kolom deleted_at untuk menandai penghapusan chat user
            if (!Schema::hasColumn('conversation_user', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('last_read_message_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_user', function (Blueprint $table) {
            if (Schema::hasColumn('conversation_user', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('conversation_user', 'deleted_at')) {
                $table->dropColumn('deleted_at');
            }
        });
    }
};
