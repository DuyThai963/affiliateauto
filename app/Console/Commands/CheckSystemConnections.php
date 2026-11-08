<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        $this->info('=== KIỂM TRA TRẠNG THÁI HẠ TẦNG AFFILIATEAUTO ===');

        // 1. Kiểm tra Neon PostgreSQL
        $this->line("\n[1/3] Kiểm tra kết nối Neon PostgreSQL...");
        try {
            $databaseQuery = DB::select('SELECT current_database() AS db_name, version() AS version');
            $databaseName = $databaseQuery[0]->db_name;
            $databaseVersion = $databaseQuery[0]->version;

            $this->info("  -> KẾT NỐI POSTGRES THÀNH CÔNG: Database '{$databaseName}'");
            $this->comment("  -> Version: {$databaseVersion}");
        } catch (Exception $exception) {
            $this->error("  -> LỖI POSTGRESQL: " . $exception->getMessage());
        }

        // 2. Kiểm tra Upstash Redis qua TLS
        $this->line("\n[2/3] Kiểm tra kết nối Upstash Redis (TLS Socket)...");
        try {
            $redis = Redis::connection();
            $pingKey = 'app_ping_check_' . time();
            $redis->set($pingKey, 'ACTIVE');
            $redis->expire($pingKey, 30);
            $redisValue = $redis->get($pingKey);

            $this->info("  -> KẾT NỐI REDIS TLS THÀNH CÔNG: [{$pingKey}] => {$redisValue}");
        } catch (Exception $exception) {
            $this->error("  -> LỖI UPSTASH REDIS TLS: " . $exception->getMessage());
        }

        // 3. Kiểm tra Upstash REST API
        $this->line("\n[3/3] Kiểm tra Upstash REST API Fallback...");
        try {
            $restUrl = env('UPSTASH_REDIS_REST_URL');
            $restToken = env('UPSTASH_REDIS_REST_TOKEN');

            if ($restUrl && $restToken) {
                $httpResponse = Http::withToken($restToken)->get("{$restUrl}/ping");
                if ($httpResponse->successful()) {
                    $this->info("  -> UPSTASH REST API THÀNH CÔNG: " . $httpResponse->body());
                } else {
                    $this->warn("  -> CẢNH BÁO REST API: Status code " . $httpResponse->status());
                }
            } else {
                $this->comment("  -> BỎ QUA: Chưa có cấu hình REST URL/TOKEN");
            }
        } catch (Exception $exception) {
            $this->error("  -> LỖI REST API: " . $exception->getMessage());
        }

        $this->line("\n=== HOÀN TẤT KIỂM TRA HẠ TẦNG ===");
        return Command::SUCCESS;
    }
}
