<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CheckSystemConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-connections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra trạng thái kết nối tới Neon PostgreSQL và Upstash Redis';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('--- BẮT ĐẦU KIỂM TRA HẠ TẦNG KẾT NỐI ---');

        // 1. Kiểm tra PostgreSQL
        try {
            $databaseName = DB::connection()->getDatabaseName();
            $this->info("✔ PostgreSQL (Neon DB: {$databaseName}) - KẾT NỐI THÀNH CÔNG!");
            
            $channelsCount = DB::table('channels')->count();
            $apiKeysCount = DB::table('api_keys')->count();
            $productsCount = DB::table('products')->count();
            $videoTasksCount = DB::table('video_tasks')->count();
            
            $this->line("  ├─ channels: {$channelsCount} records");
            $this->line("  ├─ api_keys: {$apiKeysCount} records");
            $this->line("  ├─ products: {$productsCount} records");
            $this->line("  └─ video_tasks: {$videoTasksCount} records");
        } catch (\Throwable $exception) {
            $this->error('✘ PostgreSQL (Neon DB) THẤT BẠI: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        // 2. Kiểm tra Redis
        try {
            $redis = Redis::connection();
            $redis->set('test_connection_key', 'antigravity_ok_' . time());
            $testValue = $redis->get('test_connection_key');
            $this->info('✔ Redis (Upstash TLS) - KẾT NỐI THÀNH CÔNG!');
            $this->line("  └─ Test Key Value: {$testValue}");
        } catch (\Throwable $exception) {
            $this->error('✘ Redis (Upstash) THẤT BẠI: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('>>> TOÀN BỘ HẠ TẦNG SẴN SÀNG HOẠT ĐỘNG! <<<');
        return Command::SUCCESS;
    }
}

