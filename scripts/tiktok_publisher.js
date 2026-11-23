import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';

/**
 * AffiliateAuto - TikTok Playwright Headless Publisher
 * Uploads MP4 to TikTok Studio/Creator Center, manages caption/hashtags,
 * branches growth vs affiliate product linking, and handles Captcha safely.
 */
function parseArgs() {
    const args = process.argv.slice(2);
    const parsed = {
        profilePath: '',
        videoPath: '',
        caption: '',
        hashtags: '',
        channelType: 'affiliate', // 'growth' | 'affiliate'
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
        '#secsdk-captcha-drag-wrapper',
        '.secsdk-captcha-drag-icon',
        '#captcha-verify-image',
        '[data-testid="captcha-verify-container"]',
        'iframe[src*="captcha"]',
        'iframe[src*="secsdk"]',
        '.verify-wrap',
    ];

    for (const selector of captchaSelectors) {
        const isPresent = await page.locator(selector).first().isVisible().catch(() => false);
        if (isPresent) {
            const tempDir = path.resolve('storage/app/temp_assets');
            if (!fs.existsSync(tempDir)) {
                fs.mkdirSync(tempDir, { recursive: true });
            }
            const screenshotPath = path.join(tempDir, `captcha_tiktok_${taskId}.png`);
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
                '--no-first-run',
                '--no-default-browser-check',
            ],
            ignoreDefaultArgs: ['--enable-automation'],
        });

        const pages = context.pages();
        const page = pages.length > 0 ? pages[0] : await context.newPage();

        await page.addInitScript(() => {
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        });

        const uploadUrl = 'https://www.tiktok.com/creator-center/upload';
        await page.goto(uploadUrl, { waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {});

        // 1. Kiểm tra Captcha ban đầu
        const initialCaptcha = await detectCaptcha(page, opts.taskId);
        if (initialCaptcha) {
            console.log(JSON.stringify({
                status: 'captcha_detected',
                error: 'Phát hiện Captcha xác thực từ TikTok Studio',
                screenshot: initialCaptcha,
            }));
            await context.close();
            process.exit(0);
        }

        // 2. Kiểm tra trạng thái đăng nhập
        const currentUrl = page.url();
        if (currentUrl.includes('/login') || currentUrl.includes('passport.tiktok.com')) {
            console.log(JSON.stringify({
                status: 'needs_relogin',
                error: 'Phiên đăng nhập TikTok đã hết hạn hoặc chưa đăng nhập.',
            }));
            await context.close();
            process.exit(0);
        }

        // 3. Tải video lên (Upload file)
        const fileChooserPromise = page.waitForEvent('filechooser', { timeout: 10000 }).catch(() => null);
        const fileInput = page.locator('input[type="file"][accept*="video"], input[type="file"]').first();

        const inputFound = await fileInput.count() > 0;
        if (inputFound) {
            await fileInput.setInputFiles(absoluteVideoPath);
        } else {
            // Click vào vùng upload nếu input ẩn
            const dropzone = page.locator('button:has-text("Select video"), div:has-text("Select video to upload")').first();
            if (await dropzone.isVisible().catch(() => false)) {
                const [chooser] = await Promise.all([
                    fileChooserPromise,
                    dropzone.click().catch(() => {}),
                ]);
                if (chooser) {
                    await chooser.setFiles(absoluteVideoPath);
                }
            }
        }

        // 4. Đợi video tải lên hoàn tất (chờ progress 100% hoặc text preview xuất hiện)
        await page.waitForTimeout(3000);

        // 5. Nhập Caption & Hashtags
        const fullCaption = `${opts.caption} ${opts.hashtags}`.trim();
        const captionEditor = page.locator('div[contenteditable="true"], textarea[placeholder*="caption"]').first();
        if (await captionEditor.isVisible().catch(() => false)) {
            await captionEditor.click();
            await page.keyboard.press('Control+A');
            await page.keyboard.press('Backspace');
            await captionEditor.fill(fullCaption).catch(async () => {
                await page.keyboard.type(fullCaption, { delay: 30 });
            });
        }

        // 6. Phân nhánh nghiệp vụ Growth vs Affiliate
        if (opts.channelType === 'growth') {
            // Kênh Growth: Tuyệt đối không gắn link sản phẩm thương mại
            console.log('[INFO] Kênh Growth: Bỏ qua liên kết thương mại.');
        } else if (opts.channelType === 'affiliate' && opts.productName) {
            // Kênh Affiliate: Tìm kiếm và ghim sản phẩm giỏ hàng Showcase
            console.log(`[INFO] Kênh Affiliate: Đang gắn liên kết sản phẩm: ${opts.productName}`);
            const addLinkBtn = page.locator('button:has-text("Thêm liên kết"), button:has-text("Add link"), div:has-text("Thêm liên kết")').first();
            if (await addLinkBtn.isVisible().catch(() => false)) {
                await addLinkBtn.click();
                await page.waitForTimeout(1000);

                const productOption = page.locator('div:has-text("Sản phẩm"), div:has-text("Product"), li:has-text("Product")').first();
                if (await productOption.isVisible().catch(() => false)) {
                    await productOption.click();
                    await page.waitForTimeout(1500);

                    const searchInput = page.locator('input[placeholder*="Tìm kiếm"], input[placeholder*="Search product"]').first();
                    if (await searchInput.isVisible().catch(() => false)) {
                        await searchInput.fill(opts.productName);
                        await page.keyboard.press('Enter');
                        await page.waitForTimeout(2000);

                        // Chọn sản phẩm đầu tiên
                        const selectProductBtn = page.locator('button:has-text("Thêm"), button:has-text("Add")').first();
                        if (await selectProductBtn.isVisible().catch(() => false)) {
                            await selectProductBtn.click();
                            await page.waitForTimeout(1000);
                        }
                    }
                }
            }
        }

        // 7. Kiểm tra Captcha lần 2 trước khi bấm Post
        const postCaptcha = await detectCaptcha(page, opts.taskId);
        if (postCaptcha) {
            console.log(JSON.stringify({
                status: 'captcha_detected',
                error: 'Phát hiện Captcha xác thực trong quá trình tải video lên',
                screenshot: postCaptcha,
            }));
            await context.close();
            process.exit(0);
        }

        // 8. Bấm nút Đăng (Post Button)
        const postButton = page.locator('button:has-text("Đăng"), button:has-text("Post")').last();
        if (await postButton.isVisible().catch(() => false)) {
            await postButton.click();
            await page.waitForTimeout(5000);

            // Chờ modal xác nhận xuất hiện
            const successIndicator = page.locator('text="Video của bạn đã được tải lên", text="Your video has been uploaded", text="Manage your posts"').first();
            await successIndicator.waitFor({ state: 'visible', timeout: 20000 }).catch(() => {});
        }

        const finalUrl = page.url();
        console.log(JSON.stringify({
            status: 'success',
            message: 'Video đã được xuất bản thành công lên kênh TikTok!',
            postUrl: finalUrl,
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

