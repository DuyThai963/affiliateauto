<?php

namespace App\Drivers\LLM;

use App\Core\Contracts\LlmDriverInterface;
use App\Core\DTOs\ScriptDataDto;

class MockLlmDriver implements LlmDriverInterface
{
    /**
     * Generate mock structured video script.
     *
     * @param array<string, mixed> $options
     */
    public function generateScript(string $prompt, array $options = []): ScriptDataDto
    {
        $productName = $options['product_name'] ?? 'Sản phẩm siêu tiện ích';
        $channelType = $options['channel_type'] ?? 'affiliate';

        if ($channelType === 'growth') {
            return new ScriptDataDto(
                title: 'Bí mật 99% mọi người không biết khi làm việc nhà!',
                hookText: 'Dừng lại 3 giây! Bạn có đang mắc phải sai lầm ngớ ngẩn này mỗi ngày không?',
                voiceScript: 'Nếu bạn vẫn đang dọn nhà theo cách truyền thống, hãy xem hết video này. Bí kíp số 1 là tối ưu hóa từng bước nhỏ để tiết kiệm 80% thời gian. Hãy thử ngay hôm nay và chia sẻ cảm nghĩ nhé!',
                visualPrompts: [
                    '00:00 - Cảnh cận mặt ngạc nhiên, text giật tít màu đỏ nổi bật',
                    '00:03 - Bối cảnh bừa bộn và biểu cảm bối rối',
                    '00:07 - Biến chuyển tức thì sang không gian gọn gàng sạch sẽ',
                    '00:12 - Lời kêu gọi tương tác và bấm theo dõi kênh',
                ],
                caption: 'Mẹo cực đỉnh giúp cuộc sống nhàn tênh! Bạn đã biết mẹo này chưa? 👇 #meohay #cuocsong #xuhuong #viral',
                hashtags: '#meohay #cuocsong #xuhuong #viral'
            );
        }

        return new ScriptDataDto(
            title: 'Trải nghiệm thực tế ' . $productName . ' - Có đáng tiền như đồn thổi?',
            hookText: 'Đừng dại mua ' . $productName . ' nếu bạn chưa biết sự thật chấn động này!',
            voiceScript: 'Mình đã dùng thử ' . $productName . ' liên tục trong 1 tuần qua và đây là kết quả chân thực nhất. Thiết kế nhỏ gọn, lực hút cực mạnh và quan trọng nhất là giá chỉ bằng 1/3 thị trường. Link sản phẩm chính hãng mình đã ghim ngay góc trái giỏ hàng, bấm vào kẻo hết mã giảm giá nhé!',
            visualPrompts: [
                '00:00 - Cầm sản phẩm đập vào khung hình, hiệu ứng âm thanh giật mình',
                '00:03 - So sánh thực tế trước và sau khi sử dụng thiết bị',
                '00:08 - Cận cảnh chi tiết tính năng độc đáo và nút bấm',
                '00:12 - Bàn tay chỉ thẳng vào nút Giỏ Hàng góc trái màn hình',
            ],
            caption: 'Đánh giá chân thực ' . $productName . '. Giá rẻ bất ngờ, săn sale ngay góc trái giỏ hàng nhé cả nhà! 🛒🔥 #review #affiliate #dealhot #muataitiktok',
            hashtags: '#review #affiliate #dealhot #muataitiktok'
        );
    }
}

