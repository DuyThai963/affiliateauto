<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SubtitleGeneratorService
{
    /**
     * Tạo file phụ đề .ass chuẩn TikTok 9:16 có Safe Zone cách đáy 450px.
     */
    public function generateAssSubtitle(
        string $voiceScript,
        string $audioPath,
        string $outputPath,
        ?string $hookText = null
    ): string {
        $outputDir = dirname($outputPath);
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        // 1. Lấy tổng thời lượng file audio (giây)
        $audioDurationSeconds = $this->getAudioDuration($audioPath);

        // 2. Chia kịch bản thành các cụm từ ngắn nhịp điệu TikTok (3 - 6 từ/cụm)
        $phrases = $this->segmentScriptIntoPhrases($voiceScript);
        $totalPhrases = count($phrases);

        if ($totalPhrases === 0) {
            $phrases = [$voiceScript];
            $totalPhrases = 1;
        }

        // 3. Phân bổ thời gian (Linear Interpolation)
        $durationPerPhrase = $audioDurationSeconds / $totalPhrases;

        // 4. Header file ASS chuẩn dọc 1080x1920
        $assContent = <<<ASS
[Script Info]
Title: AffiliateAuto TikTok Dynamic Subtitles
ScriptType: v4.00+
WrapStyle: 0
ScaledBorderAndShadow: yes
PlayResX: 1080
PlayResY: 1920

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: TikTokYellow,Arial,56,&H0000FFFF,&H000000FF,&H00000000,&H80000000,-1,0,0,0,100,100,0,0,1,4.0,1.0,2,80,160,450,1
Style: HookBanner,Arial,62,&H00FFFFFF,&H000000FF,&H00000000,&H90000000,-1,0,0,0,100,100,0,0,1,5.0,2.0,8,80,160,350,1

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text

ASS;

        // 5. Nếu có Hook Text -> ghim ở nửa trên màn hình trong 3.5 giây đầu
        if (!empty($hookText)) {
            $hookStart = $this->formatAssTime(0.0);
            $hookEnd = $this->formatAssTime(min(3.5, $audioDurationSeconds));
            $cleanHookText = str_replace(["\r", "\n"], ' ', strtoupper(trim($hookText)));
            $assContent .= "Dialogue: 1,{$hookStart},{$hookEnd},HookBanner,,0,0,0,,{$cleanHookText}\n";
        }

        // 6. Ghi các dòng phụ đề thoại chạy theo nhịp audio
        $currentTime = 0.0;
        foreach ($phrases as $phrase) {
            $startTime = $currentTime;
            $endTime = min($currentTime + $durationPerPhrase, $audioDurationSeconds);

            $formattedStart = $this->formatAssTime($startTime);
            $formattedEnd = $this->formatAssTime($endTime);
            $cleanPhrase = str_replace(["\r", "\n"], ' ', trim($phrase));

            $assContent .= "Dialogue: 0,{$formattedStart},{$formattedEnd},TikTokYellow,,0,0,0,,{$cleanPhrase}\n";

            $currentTime = $endTime;
        }

        File::put($outputPath, $assContent);
        Log::info("SubtitleGeneratorService: Đã xuất file phụ đề ASS: {$outputPath} ({$totalPhrases} phân đoạn, duration: {$audioDurationSeconds}s)");

        return $outputPath;
    }

    /**
     * Đo độ dài file audio bằng FFmpeg hoặc ước lượng theo số từ.
     */
    protected function getAudioDuration(string $audioPath): float
    {
        if (File::exists($audioPath)) {
            $ffmpegBinary = $this->getFfmpegBinary();

            $process = new Process([
                $ffmpegBinary,
                '-i',
                $audioPath,
            ]);
            $process->run();

            $output = $process->getErrorOutput() . ' ' . $process->getOutput();
            if (preg_match('/Duration:\s*(\d+):(\d+):(\d+\.\d+)/', $output, $matches)) {
                $hours = (int) $matches[1];
                $minutes = (int) $matches[2];
                $seconds = (float) $matches[3];
                return ($hours * 3600) + ($minutes * 60) + $seconds;
            }
        }

        // Ước lượng mặc định: 15 giây
        return 15.0;
    }

    /**
     * Phân tách kịch bản thành các cụm từ ngắn gọn từ 3 đến 6 từ.
     *
     * @return array<int, string>
     */
    protected function segmentScriptIntoPhrases(string $script): array
    {
        // Tách theo dấu câu trước: chấm, phẩy, hỏi, chấm than
        $rawSegments = preg_split('/(?<=[.,?!])\s+/u', trim($script));
        $phrases = [];

        foreach ($rawSegments as $segment) {
            $words = preg_split('/\s+/u', trim($segment));
            if (empty($words) || empty($words[0])) {
                continue;
            }

            $currentPhrase = [];
            foreach ($words as $word) {
                $currentPhrase[] = $word;
                if (count($currentPhrase) >= 5) {
                    $phrases[] = implode(' ', $currentPhrase);
                    $currentPhrase = [];
                }
            }

            if (!empty($currentPhrase)) {
                $phrases[] = implode(' ', $currentPhrase);
            }
        }

        return $phrases;
    }

    /**
     * Định dạng số giây sang ASS timestamp format H:MM:SS.CC.
     */
    protected function formatAssTime(float $seconds): string
    {
        $hours = (int) floor($seconds / 3600);
        $remainder = $seconds - ($hours * 3600);
        $minutes = (int) floor($remainder / 60);
        $secs = $remainder - ($minutes * 60);
        $centis = (int) round(($secs - floor($secs)) * 100);
        $intSecs = (int) floor($secs);

        return sprintf('%d:%02d:%02d.%02d', $hours, $minutes, $intSecs, $centis);
    }

    /**
     * Lấy đường dẫn FFmpeg khả dụng.
     */
    public function getFfmpegBinary(): string
    {
        return (string) (env('FFMPEG_BIN') ?: 'ffmpeg');
    }
}

