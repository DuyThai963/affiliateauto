<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\VideoTask;
use Carbon\Carbon;

class HumanLikeSchedulerService
{
    /**
     * Tính toán khung giờ vàng xuất bản tiếp theo cho kênh với độ lệch tự nhiên (Jitter).
     */
    public function calculateNextPublishSlot(Channel $channel, ?Carbon $baseTime = null): ?Carbon
    {
        $now = $baseTime ? $baseTime->copy() : Carbon::now();
        $today = $now->copy()->startOfDay();

        // 1. Kiểm tra hạn mức đăng trong ngày của kênh
        $postsToday = VideoTask::query()
            ->where('channel_id', $channel->id)
            ->where('status', 'published')
            ->where('published_at', '>=', $today)
            ->count();

        if ($postsToday >= $channel->daily_post_limit) {
            return null; // Đã hoàn thành chỉ tiêu ngày
        }

        // 2. Xác định mốc thời gian tối thiểu từ lần đăng trước
        $minIntervalMinutes = max(60, (int) ($channel->min_interval_minutes ?: 180));
        
        if ($channel->last_posted_at !== null) {
            $earliestSlot = $channel->last_posted_at->copy()->addMinutes($minIntervalMinutes);
        } else {
            $earliestSlot = $now->copy();
        }

        if ($earliestSlot->isPast()) {
            $earliestSlot = $now->copy();
        }

        // 3. Thêm Jitter ngẫu nhiên (±7 đến 23 phút)
        $jitterDirection = random_int(0, 1) === 1 ? 1 : -1;
        $jitterMinutes = random_int(7, 23) * $jitterDirection;
        $targetSlot = $earliestSlot->copy()->addMinutes($jitterMinutes);

        // Đảm bảo không dời về quá khứ
        if ($targetSlot->isPast()) {
            $targetSlot = $now->copy()->addMinutes(random_int(5, 15));
        }

        // 4. Áp dụng khung giờ sinh hoạt tự nhiên (07:30 đến 22:30)
        return $this->enforceHumanPostingWindow($targetSlot);
    }

    /**
     * Ép khung giờ xuất bản rơi vào khoảng thời gian tự nhiên của con người (07:30 - 22:30).
     */
    protected function enforceHumanPostingWindow(Carbon $slot): Carbon
    {
        $windowStart = $slot->copy()->setTime(7, 30, 0);
        $windowEnd = $slot->copy()->setTime(22, 30, 0);

        // Nếu sớm hơn 07:30 sáng, dời lên 07:30 + jitter nhẹ
        if ($slot->lt($windowStart)) {
            return $windowStart->copy()->addMinutes(random_int(5, 25));
        }

        // Nếu muộn hơn 22:30 tối, dời sang 07:30 sáng hôm sau + jitter nhẹ
        if ($slot->gt($windowEnd)) {
            return $slot->copy()->addDay()->setTime(7, 30, 0)->addMinutes(random_int(10, 35));
        }

        return $slot;
    }

    /**
     * Kiểm tra xem tác vụ có sẵn sàng để xuất bản ngay bây giờ hay không.
     */
    public function shouldPublishNow(VideoTask $task): bool
    {
        if ($task->status !== 'ready_in_vault') {
            return false;
        }

        if ($task->scheduled_at === null) {
            return false;
        }

        return $task->scheduled_at->isPast();
    }
}

