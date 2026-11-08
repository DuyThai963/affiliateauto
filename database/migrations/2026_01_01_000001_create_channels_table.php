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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['tiktok', 'shopee']);
            $table->string('channel_name', 100);
            $table->enum('type', ['growth', 'affiliate']);
            $table->string('browser_profile_path', 255);
            $table->integer('daily_post_limit')->default(5);
            $table->integer('min_interval_minutes')->default(200);
            $table->enum('status', ['active', 'paused', 'needs_relogin'])->default('active');
            $table->timestamp('last_posted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};

