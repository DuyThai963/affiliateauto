import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

/**
 * AffiliateAuto - Profile Login Helper
 * Launches Playwright Chromium with persistent context (headed mode)
 * allowing the user to manually log in, complete OTP/Captcha, and save the session permanently.
 */
function parseArgs() {
    const args = process.argv.slice(2);
    const parsed = {
        platform: 'tiktok',
        profilePath: '',
    };

    for (let i = 0; i < args.length; i++) {
        if (args[i] === '--platform' && args[i + 1]) {
            parsed.platform = args[i + 1].toLowerCase();
            i++;
        } else if (args[i] === '--profile-path' && args[i + 1]) {
            parsed.profilePath = args[i + 1];
            i++;
        }
    }

    return parsed;
}

async function main() {
    const { platform, profilePath } = parseArgs();

    if (!profilePath) {
        console.error(JSON.stringify({ status: 'error', message: 'Missing --profile-path argument.' }));
        process.exit(1);
    }

    const absoluteProfileDir = path.resolve(profilePath);
    if (!fs.existsSync(absoluteProfileDir)) {
        fs.mkdirSync(absoluteProfileDir, { recursive: true });
    }

    let targetUrl = 'https://www.tiktok.com/creator-center/upload';
    if (platform === 'shopee') {
        targetUrl = 'https://creator.shopee.vn/';
    }

    console.log(`[INFO] Khởi chạy trình duyệt cho nền tảng: ${platform.toUpperCase()}`);
    console.log(`[INFO] Thư mục lưu phiên (UserDataDir): ${absoluteProfileDir}`);
    console.log(`[INFO] Đang mở URL: ${targetUrl}`);
    console.log(`[HƯỚNG DẪN] Bạn hãy thực hiện đăng nhập và hoàn thành OTP trên cửa sổ trình duyệt vừa mở.`);
    console.log(`[HƯỚNG DẪN] Sau khi đăng nhập thành công, hãy ĐÓNG CỬA SỔ TRÌNH DUYỆT để lưu session hoàn tất.`);

    try {
        const context = await chromium.launchPersistentContext(absoluteProfileDir, {
            headless: false,
            channel: 'chrome', // Dùng Chrome thật nếu có, fallback sang Chromium bundled
            viewport: null,
            args: [
                '--disable-blink-features=AutomationControlled',
                '--no-sandbox',
                '--start-maximized',
                '--disable-infobars',
                '--no-first-run',
                '--no-default-browser-check',
            ],
            ignoreDefaultArgs: ['--enable-automation'],
        }).catch(async (err) => {
            // Fallback nếu không có Google Chrome channel trên hệ thống
            return await chromium.launchPersistentContext(absoluteProfileDir, {
                headless: false,
                viewport: null,
                args: [
                    '--disable-blink-features=AutomationControlled',
                    '--no-sandbox',
                    '--start-maximized',
                    '--disable-infobars',
                ],
                ignoreDefaultArgs: ['--enable-automation'],
            });
        });

        const pages = context.pages();
        const page = pages.length > 0 ? pages[0] : await context.newPage();

        // Ẩn navigator.webdriver thêm 1 lớp an toàn
        await page.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', {
                get: () => undefined,
            });
        });

        await page.goto(targetUrl, { waitUntil: 'load', timeout: 60000 }).catch((e) => {
            console.log(`[WARN] Trang nạp chậm hoặc đã tải 1 phần: ${e.message}`);
        });

        // Đợi tới khi người dùng đóng trình duyệt
        await new Promise((resolve) => {
            context.on('close', resolve);
        });

        console.log(`[THÀNH CÔNG] Người dùng đã đóng trình duyệt. Toàn bộ session/cookies đã được lưu vĩnh viễn.`);
        console.log(JSON.stringify({ status: 'success', profilePath: absoluteProfileDir }));
        process.exit(0);

    } catch (error) {
        console.error(JSON.stringify({ status: 'error', message: error.message }));
        process.exit(1);
    }
}

main();

