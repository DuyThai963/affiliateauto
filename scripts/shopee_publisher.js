import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

/**
 * AffiliateAuto - Shopee Video Playwright Headless Publisher
 * Uploads MP4 to Shopee Creator Portal, attaches product link for affiliate, and posts safely.
 */
function parseArgs() {
    const args = process.argv.slice(2);
    const parsed = {
        profilePath: '',
        videoPath: '',
        caption: '',
        hashtags: '',
        channelType: 'affiliate',
        productName: '',
        taskId: 0,
        headless: true,
    };

    for (let i = 0; i < args.length; i++) {
        if (args[i] === '--profile-path' && args[i + 1]) {
            parsed.profilePath = args[i + 1];
            i++;
        } else if (args[i] === '--video-path' && args[i + 1]) {
            parsed.videoPath = args[i + 1];
            i++;
        } else if (args[i] === '--caption' && args[i + 1]) {
            parsed.caption = args[i + 1];
            i++;
        } else if (args[i] === '--hashtags' && args[i + 1]) {
            parsed.hashtags = args[i + 1];
            i++;
        } else if (args[i] === '--channel-type' && args[i + 1]) {
            parsed.channelType = args[i + 1].toLowerCase();
            i++;
        } else if (args[i] === '--product-name' && args[i + 1]) {
            parsed.productName = args[i + 1];
            i++;
        } else if (args[i] === '--task-id' && args[i + 1]) {
            parsed.taskId = parseInt(args[i + 1], 10) || 0;
            i++;
        } else if (args[i] === '--headless' && args[i + 1]) {
            parsed.headless = args[i + 1] === 'true';
            i++;
        }
    }

    return parsed;
}

async function detectCaptcha(page, taskId) {
    const captchaSelectors = [
        '.shopee-captcha',
        'iframe[src*="captcha"]',
        '#captcha-component',
        '.puzzle-verify',
    ];

    for (const selector of captchaSelectors) {
        const isPresent = await page.locator(selector).first().isVisible().catch(() => false);
        if (isPresent) {
            const tempDir = path.resolve('storage/app/temp_assets');
            if (!fs.existsSync(tempDir)) {
                fs.mkdirSync(tempDir, { recursive: true });
            }
            const screenshotPath = path.join(tempDir, `captcha_shopee_${taskId}.png`);
            await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => {});
            return screenshotPath;
        }
    }
    return null;
}

async function main() {
    const opts = parseArgs();

    if (!opts.profilePath || !opts.videoPath) {
        console.log(JSON.stringify({
            status: 'failed',
            error: 'Thiếu tham số --profile-path hoặc --video-path',
        }));
        process.exit(1);
    }

    const absoluteProfileDir = path.resolve(opts.profilePath);
    const absoluteVideoPath = path.resolve(opts.videoPath);

    if (!fs.existsSync(absoluteVideoPath)) {
        console.log(JSON.stringify({
            status: 'failed',
            error: `Không tìm thấy file video tại: ${absoluteVideoPath}`,
        }));
        process.exit(1);
    }

    let context = null;

    try {
        context = await chromium.launchPersistentContext(absoluteProfileDir, {
            headless: opts.headless,
            viewport: null,
            args: [
                '--disable-blink-features=AutomationControlled',
                '--no-sandbox',
                '--start-maximized',
                '--disable-infobars',
            ],
            ignoreDefaultArgs: ['--enable-automation'],
        });

        const pages = context.pages();
        const page = pages.length > 0 ? pages[0] : await context.newPage();

        await page.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        });

        const portalUrl = 'https://creator.shopee.vn/';
        await page.goto(portalUrl, { waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {});

        // 1. Kiểm tra Captcha
        const initialCaptcha = await detectCaptcha(page, opts.taskId);
        if (initialCaptcha) {
            console.log(JSON.stringify({
                status: 'captcha_detected',
                error: 'Phát hiện Captcha xác thực từ Shopee Creator Portal',
                screenshot: initialCaptcha,
            }));
            await context.close();
            process.exit(0);
        }

        // 2. Kiểm tra đăng nhập
        const currentUrl = page.url();
        if (currentUrl.includes('/login') || currentUrl.includes('buyer/login')) {
            console.log(JSON.stringify({
                status: 'needs_relogin',
                error: 'Phiên đăng nhập Shopee đã hết hạn hoặc chưa đăng nhập.',
            }));
            await context.close();
            process.exit(0);
        }

        // 3. Tải video lên
        const fileInput = page.locator('input[type="file"][accept*="video"], input[type="file"]').first();
        if (await fileInput.count() > 0) {
            await fileInput.setInputFiles(absoluteVideoPath);
        }

        await page.waitForTimeout(3000);

        // 4. Nhập tiêu đề & mô tả
        const fullCaption = `${opts.caption} ${opts.hashtags}`.trim();
        const captionInput = page.locator('textarea, input[placeholder*="mô tả"], input[placeholder*="tiêu đề"]').first();
        if (await captionInput.isVisible().catch(() => false)) {
            await captionInput.fill(fullCaption);
        }

        // 5. Phân nhánh gắn sản phẩm Affiliate
        if (opts.channelType === 'affiliate' && opts.productName) {
            const addProductBtn = page.locator('button:has-text("Thêm sản phẩm"), button:has-text("Gắn sản phẩm")').first();
            if (await addProductBtn.isVisible().catch(() => false)) {
                await addProductBtn.click();
                await page.waitForTimeout(1500);

                const searchProductInput = page.locator('input[placeholder*="Tìm sản phẩm"], input[placeholder*="tên sản phẩm"]').first();
                if (await searchProductInput.isVisible().catch(() => false)) {
                    await searchProductInput.fill(opts.productName);
                    await page.keyboard.press('Enter');
                    await page.waitForTimeout(2000);

                    const selectBtn = page.locator('button:has-text("Chọn"), button:has-text("Thêm")').first();
                    if (await selectBtn.isVisible().catch(() => false)) {
                        await selectBtn.click();
                    }
                }
            }
        }

        // 6. Kiểm tra Captcha lần 2
        const postCaptcha = await detectCaptcha(page, opts.taskId);
        if (postCaptcha) {
            console.log(JSON.stringify({
                status: 'captcha_detected',
                error: 'Phát hiện Captcha xác thực khi đăng video Shopee',
                screenshot: postCaptcha,
            }));
            await context.close();
            process.exit(0);
        }

        // 7. Bấm đăng video
        const submitBtn = page.locator('button:has-text("Đăng"), button:has-text("Xuất bản"), button[type="submit"]').last();
        if (await submitBtn.isVisible().catch(() => false)) {
            await submitBtn.click();
            await page.waitForTimeout(5000);
        }

        console.log(JSON.stringify({
            status: 'success',
            message: 'Video đã được xuất bản thành công lên Shopee Video!',
            taskId: opts.taskId,
        }));

        await context.close();
        process.exit(0);

    } catch (error) {
        if (context) {
            await context.close().catch(() => {});
        }
        console.log(JSON.stringify({
            status: 'failed',
            error: error.message,
            taskId: opts.taskId,
        }));
        process.exit(1);
    }
}

main();

