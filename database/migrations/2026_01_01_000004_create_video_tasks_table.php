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
        Schema::create('video_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->enum('task_type', ['affiliate_original', 'growth_reup_clone'])->default('affiliate_original');
            $table->string('title', 255)->nullable();
            $table->text('caption')->nullable();
            $table->string('hashtags', 255)->nullable();
            $table->json('script_data')->nullable();
            $table->string('video_path', 255)->nullable();
            $table->enum('status', [
                'pending',
                'scripting',
                'assets_ready',
                'rendering',
                'waiting_approval',
                'ready_in_vault',
                'publishing',
                'published',
                'failed',
            ])->default('pending');
            $table->string('current_step', 150)->default('Khởi tạo tác vụ');
            $table->integer('progress')->default(0);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->text('error_log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_tasks');
    }
};

