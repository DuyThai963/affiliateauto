FROM php:8.4-fpm-bookworm

# Thiết lập biến môi trường
ENV DEBIAN_FRONTEND=noninteractive
ENV PYTHONUNBUFFERED=1
ENV PLAYWRIGHT_BROWSERS_PATH=/ms-playwright
ENV HOME=/var/www

WORKDIR /var/www

# 1. Cài đặt các gói hệ thống cốt lõi, Python 3, FFmpeg, Supervisor, Nginx client tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    unzip \
    zip \
    supervisor \
    ffmpeg \
    python3 \
    python3-pip \
    python3-venv \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    # Thư viện hệ thống phụ thuộc của Playwright Chromium
    libnss3 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libpango-1.0-0 \
    libcairo2 \
    libasound2 \
    fonts-liberation \
    fonts-noto-color-emoji \
    && rm -rf /var/lib/apt/lists/*

# 2. Cài đặt các tiện ích mở rộng PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pdo_mysql \
        zip \
        pcntl \
        posix \
        bcmath \
        intl \
        gd \
        opcache

# Cài đặt PHP Redis extension qua PECL
RUN pecl install redis && docker-php-ext-enable redis

# 3. Cài đặt Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# 4. Cài đặt Node.js 20.x LTS
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# 5. Cài đặt Edge-TTS (Python) cho tính năng lồng tiếng tiếng Việt
RUN pip3 install --no-cache-dir --break-system-packages edge-tts

# 6. Chuẩn bị thư mục mã nguồn và cài đặt dependencies
COPY . /var/www

# Cài đặt PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Cài đặt Node.js dependencies & Playwright Chromium
RUN npm ci --omit=dev || npm install --omit=dev \
    && npx playwright install --with-deps chromium

# 7. Phân quyền thư mục lưu trữ và bộ đệm
RUN mkdir -p /var/www/storage/app/browser_profiles \
    && mkdir -p /var/www/storage/app/buffer_vault \
    && mkdir -p /var/www/storage/app/temp_assets \
    && mkdir -p /var/www/storage/app/cloud_vault_5tb \
    && mkdir -p /var/www/storage/framework/cache \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Cấu hình Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

