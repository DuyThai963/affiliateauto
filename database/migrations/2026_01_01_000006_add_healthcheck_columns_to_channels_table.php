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
        Schema::table('channels', function (Blueprint $table) {
            if (!Schema::hasColumn('channels', 'last_healthchecked_at')) {
                $table->timestamp('last_healthchecked_at')->nullable()->after('last_posted_at');
            }
            if (!Schema::hasColumn('channels', 'health_notes')) {
                $table->string('health_notes', 255)->nullable()->after('last_healthchecked_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            if (Schema::hasColumn('channels', 'health_notes')) {
                $table->dropColumn('health_notes');
            }
            if (Schema::hasColumn('channels', 'last_healthchecked_at')) {
                $table->dropColumn('last_healthchecked_at');
            }
        });
    }
};

