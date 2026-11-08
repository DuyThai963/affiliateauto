# PROJECT RULES & ARCHITECTURAL GUIDELINES
# PROJECT: AffiliateAuto
# STACK: Laravel 11, Livewire 3, Tailwind CSS, Alpine.js, MySQL 8.0, Redis

---

### 1. NGUYÊN TẮC LẬP TRÌNH BẮT BUỘC (CODING STANDARDS)

- **Cú pháp PHP chuẩn:** 
  - KHÔNG sử dụng cú pháp arrow function rút gọn (`fn(...) => ...`). Luôn viết hàm ẩn danh đầy đủ: `function (...) { ... }`.
  - KHÔNG đặt tên biến viết tắt tùy tiện như `$q`, `$res`, `$tmp`, `$cb`. Luôn đặt tên biến tường minh theo ngữ cảnh: `$query`, `$response`, `$callback`, `$taskResult`.
- **Bảo toàn mã nguồn hiện hữu (No Unrequested Refactoring):**
  - Chỉ viết mới hoặc chỉnh sửa đúng vị trí được yêu cầu trong từng Sprint.
  - Tuyệt đối KHÔNG tự ý refactor, cơ cấu lại các file, class, cấu trúc thư mục hoặc logic cũ đang hoạt động ổn định.
  - Ưu tiên tính ổn định của luồng nghiệp vụ hiện có hơn là tự ý tối ưu hóa code theo sở thích cá nhân.
- **Git Conventions:**
  - Khi phân nhánh hoặc commit, sử dụng prefix hành động cá nhân (ví dụ: `thai-add`, `thai-fix`).

---

### 2. BẢN ĐỒ KIẾN TRÚC & PHÂN TÁCH TRÁCH NHIỆM (ARCHITECTURE)

- **Mô hình Driver / Adapter Pattern:**
  - Toàn bộ các công cụ bên ngoài (LLM API, TTS giọng đọc, Render video Remotion/FFmpeg, Publisher Playwright) BẮT BUỘC phải kế thừa từ các Interface chuẩn tại `app/Core/Contracts/`.
  - KHÔNG BAO GIỜ gọi trực tiếp code thư viện ngoài hoặc script repo thứ ba bên trong Controller, Livewire Component hay Job. Mọi thứ phải đi qua `Driver`.
- **Cơ chế Hàng đợi (Queue-First Architecture):**
  - Mọi tác vụ nặng (gọi AI, sinh audio, render MP4, chạy Playwright) đều phải dispatch qua Redis Queue, không xử lý đồng bộ trên HTTP request.
  - Render và Upload phải chạy tuần tự (Concurrency = 1) để tránh tràn CPU/RAM hệ thống.
- **State Machine nghiêm ngặt:**
  - Bảng `video_tasks` chỉ chuyển trạng thái tuần tự:
    `pending` -> `scripting` -> `assets_ready` -> `rendering` -> `ready_in_vault` -> `publishing` -> `published` (hoặc `failed`).
  - Mỗi bước xử lý phải cập nhật trường `current_step` (chuỗi văn bản mô tả việc đang làm) và `progress` (0 - 100%) để Dashboard polling hiển thị.

---

### 3. QUY TẮC NGHIỆP VỤ BẤT BIẾN (DOMAIN RULES)

- **Phân định rõ 2 loại Kênh (Channel Type):**
  - **`growth` (Kênh Xây Dựng / Kéo View):** 
    - Mục tiêu: Tối ưu tương tác, giữ chân người xem.
    - Video: Kịch bản viral hoặc Reup qua bộ lọc biến tính (tẩy metadata, crop zoom, lật gương, thay audio/sub).
    - Xuất bản: TUYỆT ĐỐI KHÔNG tìm kiếm hay gắn link giỏ hàng/sản phẩm Showcase.
  - **`affiliate` (Kênh Buôn Bán / Kiếm Tiền):**
    - Mục tiêu: Chuyển đổi đơn hàng.
    - Video: Kịch bản giải quyết nỗi đau theo mô hình AIDA, giới thiệu sản phẩm.
    - Xuất bản: BẮT BUỘC phải mở popup giỏ hàng, tìm kiếm theo tên/ID sản phẩm và ghim link mua hàng trước khi bấm Đăng.
- **Tách biệt Tạo Video (Quota Maximizer) và Đăng Video (Scheduler):**
  - **Sản xuất:** Chạy theo hạn mức Token API (xoay vòng Key, vắt sạch quota hàng ngày để tích trữ video vào `storage/buffer_vault/`).
  - **Xuất bản:** Độc lập với kho đệm. Chỉ đăng trong khung giờ người thật (07:30 - 22:30), giãn cách từ 3 - 5 tiếng/video, có Jitter ngẫu nhiên ±(7 - 23 phút).
- **An toàn tài khoản (Safety & Anti-Spam):**
  - Trình duyệt Playwright phải dùng riêng `browser_profile_path` cố định cho từng kênh.
  - Gặp Captcha phức tạp -> DỪNG tác vụ, đổi trạng thái kênh thành `needs_relogin`, ghi log cảnh báo, không được spam click.

---

### 4. TIÊU CHUẨN ĐẦU RA CODE (DELIVERY STANDARDS)

- Mọi form Livewire phải có validation rõ ràng, báo lỗi thân thiện.
- Sử dụng Tailwind CSS đồng bộ, giao diện scannable, tối ưu cho desktop view.
- Có log ghi lại chi tiết lỗi (`error_log`) ở mọi bước xử lý nền để dễ dàng debug.

