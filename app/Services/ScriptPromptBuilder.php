<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Product;

class ScriptPromptBuilder
{
    /**
     * Xây dựng Prompt hoàn chỉnh dựa trên kênh và sản phẩm (nếu có).
     */
    public function buildPrompt(Channel $channel, ?Product $product = null, ?string $customTopic = null): string
    {
        if ($channel->type === 'growth') {
            return $this->buildGrowthPrompt($customTopic);
        }

        return $this->buildAffiliatePrompt($product, $customTopic);
    }

    /**
     * Prompt dành riêng cho Kênh Growth (Xây kênh viral, kích thích tương tác, KHÔNG bán hàng).
     */
    public function buildGrowthPrompt(?string $topic = null): string
    {
        $chosenTopic = $topic ?: 'Một sự thật khoa học gây sốc hoặc bí mật tâm lý đời thường ít ai biết';

        return <<<PROMPT
BẠN LÀ CHUYÊN GIA BIÊN KỊCH VIDEO NGẮN VIRAL (TIKTOK / REELS / YOUTUBE SHORTS) ĐỈNH CAO.
NHIỆM VỤ: Viết một kịch bản video ngắn cực kỳ hấp dẫn, giữ chân người xem từ giây đầu tiên đến giây cuối cùng.

CHỦ ĐỀ: {$chosenTopic}

QUY TẮC BẮT BUỘC (STRICT GUIDELINES):
1. ĐỘ DÀI: Lời thoại (voice_script) khoảng 80 - 120 từ tiếng Việt (thời lượng đọc tự nhiên khoảng 25 - 40 giây).
2. 3 GIÂY ĐẦU (HOOK): Phải giật tít, nêu vấn đề nghịch lý, gây tranh cãi hoặc kích thích sự tò mò tột độ.
3. PHONG CÁCH: Nhịp độ nhanh, dồn dập, giọng điệu tự nhiên, từ ngữ đời thường, giàu cảm xúc.
4. TUYỆT ĐỐI CẤM (STRICTLY PROHIBITED):
   - KHÔNG nhắc đến bất kỳ sản phẩm, thương hiệu cụ thể nào.
   - KHÔNG dùng các từ khóa bán hàng: "mua", "giảm giá", "sale", "giỏ hàng", "shop", "link", "ưu đãi".
   - Đây là kênh Growth để kéo tương tác và giữ chân khán giả, KHÔNG PHẢI KÊNH BÁN HÀNG.
5. HASHTAGS: Tập trung vào độ phủ rộng như #fyp #viral #kienthuc #xuhuong #khampha.

ĐỊNH DẠNG ĐẦU RA BẮT BUỘC:
Bạn PHẢI trả về duy nhất 1 chuỗi JSON hợp lệ (không kèm giải thích, không markdown code block ```json) theo cấu trúc:
{
  "title": "Tiêu đề video ngắn gọn dưới 60 ký tự",
  "hook_text": "Dòng chữ giật tít xuất hiện trên màn hình trong 3 giây đầu",
  "voice_script": "Toàn bộ kịch bản lời thoại liền mạch, ngắt nghỉ rõ ràng để chuyển sang giọng đọc AI",
  "visual_prompts": [
    {
      "start_sec": 0,
      "end_sec": 3,
      "visual_query": "Gợi ý hình ảnh/footage mở đầu gây sốc"
    },
    {
      "start_sec": 3,
      "end_sec": 15,
      "visual_query": "Gợi ý phân cảnh minh họa nội dung chính"
    },
    {
      "start_sec": 15,
      "end_sec": 35,
      "visual_query": "Gợi ý phân cảnh kết luận bất ngờ"
    }
  ],
  "caption": "Mô tả ngắn gọn, hấp dẫn kèm câu hỏi gợi mở thảo luận ở phần bình luận",
  "hashtags": "#fyp #viral #kienthuc #xuhuong"
}
PROMPT;
    }

    /**
     * Prompt dành riêng cho Kênh Affiliate (Mô hình AIDA thực chiến, giải quyết nỗi đau & điều hướng giỏ hàng).
     */
    public function buildAffiliatePrompt(?Product $product = null, ?string $customAngle = null): string
    {
        $productName = $product?->product_name ?: 'Sản phẩm gia dụng thông minh tiện ích';
        $productNotes = $product?->notes ?: 'Thiết kế nhỏ gọn, bền bỉ, tiết kiệm 70% thời gian thao tác, giá cực hời';
        $angle = $customAngle ?: 'Giải quyết sai lầm phổ biến hàng ngày';

        return <<<PROMPT
BẠN LÀ BẬC THẦY SÁNG TẠO NỘI DUNG TIKTOK SHOP & SHOPEE VIDEO CHUYỂN ĐỔI CAO (AIDA MODEL).
NHIỆM VỤ: Viết một kịch bản video review ngắn đánh trúng tâm lý, tạo cảm xúc muốn sở hữu ngay sản phẩm.

THÔNG TIN SẢN PHẨM:
- Tên sản phẩm: {$productName}
- Điểm nổi bật & tính năng cốt lõi: {$productNotes}
- Góc tiếp cận (Angle): {$angle}

CẤU TRÚC KỊCH BẢN BẮT BUỘC (MÔ HÌNH AIDA THỰC CHIẾN):
1. GIÂY 0 - 3 (ATTENTION / HOOK):
   - Đánh thẳng vào nỗi đau nhức nhối hoặc thói quen sai lầm tai hại mà đa số mọi người đang mắc phải.
2. GIÂY 3 - 15 (INTEREST & PROBLEM):
   - Mô tả cảm giác ức chế, phiền toái, tốn thời gian/tiền bạc khi chưa có sản phẩm giải quyết.
3. GIÂY 15 - 30 (DESIRE & SOLUTION):
   - Xuất hiện giải pháp: Giới thiệu {$productName}.
   - Nêu bật ngay 1-2 tính năng đắt giá nhất ({$productNotes}) và chứng minh hiệu quả thực tế.
4. 3 GIÂY CUỐI (ACTION / CTA):
   - Kêu gọi hành động trực tiếp và điều hướng mắt/tay người xem: Hướng dẫn bấm vào biểu tượng Giỏ Hàng màu vàng ở góc trái phía dưới màn hình để săn giá sale hời hôm nay.
5. HASHTAGS: #goclamdep #tieudungthongminh #affiliatemarketing #reviewgiaoduc #dealhot #muataitiktok

ĐỊNH DẠNG ĐẦU RA BẮT BUỘC:
Bạn PHẢI trả về duy nhất 1 chuỗi JSON hợp lệ (không kèm giải thích, không markdown code block ```json) theo cấu trúc:
{
  "title": "Tiêu đề video đánh giá ngắn dưới 60 ký tự",
  "hook_text": "Câu chữ giật tít hiển thị 3 giây đầu",
  "voice_script": "Toàn bộ lời thoại liền mạch từ Hook -> Nỗi đau -> Giải pháp -> Kêu gọi bấm góc trái giỏ hàng",
  "visual_prompts": [
    {
      "start_sec": 0,
      "end_sec": 3,
      "visual_query": "Cảnh quay nỗi đau hoặc sai lầm thường gặp của khách hàng"
    },
    {
      "start_sec": 3,
      "end_sec": 15,
      "visual_query": "Cảnh khó chịu, bất tiện trong sinh hoạt"
    },
    {
      "start_sec": 15,
      "end_sec": 28,
      "visual_query": "Cận cảnh mở hộp và sử dụng sản phẩm {$productName}"
    },
    {
      "start_sec": 28,
      "end_sec": 32,
      "visual_query": "Mũi tên hoặc ngón tay chỉ về góc trái màn hình nơi có nút giỏ hàng"
    }
  ],
  "caption": "Mô tả sản phẩm ngắn gọn, giục săn ưu đãi kèm hashtag",
  "hashtags": "#goclamdep #tieudungthongminh #affiliatemarketing #reviewgiaoduc #dealhot"
}
PROMPT;
    }
}

