/**
 * Script kiểm tra tính khả dụng (Healthcheck) của Browser Profile không cần mở giao diện (Headless mode).
 * Nhận tham số:
 *   --platform: tiktok | shopee
 *   --profile-path: Đường dẫn thư mục profile lưu cookies/local storage.
 * 
 * Xuất JSON trên stdout:
 *   {"is_alive": true, "message": "Phiên hoạt động tốt", "platform": "tiktok"}
 *   {"is_alive": false, "reason": "session_expired", "message": "Phiên hết hạn hoặc chưa đăng nhập"}
 */

import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';

function parseArgs() {
    const args = process.argv.slice(2);
    const params = {
        platform: 'tiktok',
        profilePath: ''
    };

    for (let i = 0; i < args.length; i++) {
        if (args[i] === '--platform' && args[i + 1]) {
            params.platform = args[i + 1].toLowerCase();
            i++;
        } else if (args[i] === '--profile-path' && args[i + 1]) {
            params.profilePath = args[i + 1];
            i++;
        }
    }
    return params;
}

async function runHealthcheck() {
    const params = parseArgs();

    if (!params.profilePath) {
        console.log(JSON.stringify({
            is_alive: false,
            reason: 'missing_profile_path',
            message: 'Thiếu tham số --profile-path'
        }));
        process.exit(1);
    }

    const resolvedProfile = path.resolve(params.profilePath);

    if (!fs.existsSync(resolvedProfile)) {
        console.log(JSON.stringify({
            is_alive: false,
            reason: 'profile_not_found',
            message: 'Thư mục profile không tồn tại trên hệ thống'
        }));
        process.exit(0);
    }

    let context = null;

    try {
        context = await chromium.launchPersistentContext(resolvedProfile, {
            headless: true,
            args: [
                '--disable-blink-features=AutomationControlled',
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-web-security'
            ],
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            viewport: { width: 1280, height: 720 },
            locale: 'vi-VN'
        });

        const page = context.pages().length > 0 ? context.pages()[0] : await context.newPage();

        if (params.platform === 'tiktok') {
            const uploadUrl = 'https://www.tiktok.com/creator-center/upload?from=upload';
            await page.goto(uploadUrl, { waitUntil: 'domcontentloaded', timeout: 25000 });

            // Đợi tối đa 5 giây để kiểm tra URL hoặc các thành phần nhận diện đăng nhập
            await page.waitForTimeout(3000);

            const currentUrl = page.url();

            // Nếu redirect về trang login hoặc có modal login
            if (currentUrl.includes('/login') || currentUrl.includes('login-modal')) {
                console.log(JSON.stringify({
                    is_alive: false,
                    reason: 'session_expired',
                    current_url: currentUrl,
                    message: 'Phiên TikTok đã hết hạn, hệ thống chuyển hướng về trang đăng nhập.'
                }));
                await context.close();
                process.exit(0);
            }

            // Kiểm tra thẻ input file upload video
            const hasFileInput = await page.locator('input[type="file"]').count() > 0;
            const hasUploadButton = await page.locator('button:has-text("Upload"), button:has-text("Tải lên")').count() > 0;
            const hasUserAvatar = await page.locator('[data-e2e="profile-icon"], img[class*="avatar"]').count() > 0;

            if (hasFileInput || hasUploadButton || hasUserAvatar) {
                console.log(JSON.stringify({
                    is_alive: true,
                    platform: 'tiktok',
                    current_url: currentUrl,
                    message: 'Phiên TikTok hoạt động bình thường, sẵn sàng xuất bản.'
                }));
            } else {
                console.log(JSON.stringify({
                    is_alive: false,
                    reason: 'unauthenticated',
                    current_url: currentUrl,
                    message: 'Không phát hiện trạng thái đăng nhập hợp lệ trên TikTok Studio.'
                }));
            }
        } else {
            // Shopee Video / Creator Portal
            const shopeeUrl = 'https://creator.shopee.vn';
            await page.goto(shopeeUrl, { waitUntil: 'domcontentloaded', timeout: 25000 });
            await page.waitForTimeout(3000);

            const currentUrl = page.url();

            if (currentUrl.includes('/login') || currentUrl.includes('buyer/login')) {
                console.log(JSON.stringify({
                    is_alive: false,
                    reason: 'session_expired',
                    current_url: currentUrl,
                    message: 'Phiên Shopee đã hết hạn, yêu cầu đăng nhập lại.'
                }));
            } else {
                console.log(JSON.stringify({
                    is_alive: true,
                    platform: 'shopee',
                    current_url: currentUrl,
                    message: 'Phiên Shopee hợp lệ.'
                }));
            }
        }

        await context.close();
        process.exit(0);
    } catch (error) {
        if (context) {
            try {
                await context.close();
            } catch (closeErr) {}
        }

        console.log(JSON.stringify({
            is_alive: false,
            reason: 'check_error',
            message: 'Lỗi khi kiểm tra phiên: ' + error.message
        }));
        process.exit(0);
    }
}

runHealthcheck();

