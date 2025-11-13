# Dockerfile: PHP 8.1 + Apache + Node 20 + Puppeteer deps
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl ca-certificates gnupg wget \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev libzip-dev \
    libcurl4-openssl-dev \
    fonts-liberation fonts-dejavu-core \
    libasound2 libatk1.0-0 libc6 libcairo2 libcups2 \
    libdbus-1-3 libexpat1 libfontconfig1 libgcc1 \
    libgdk-pixbuf-2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 \
    libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 \
    libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 \
    libxss1 libxtst6 ca-certificates fonts-liberation libnss3 lsb-release xdg-utils \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql mbstring zip curl

# Enable Apache modules and set DocumentRoot to /var/www/html/public
RUN a2enmod rewrite headers expires deflate \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!Directory /var/www/html!Directory /var/www/html/public!g' /etc/apache2/apache2.conf \
    && sed -ri '/<Directory \/var\/www\/html\/public>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf || true \
    && echo 'Alias /images /var/www/html/images' >> /etc/apache2/sites-available/000-default.conf \
    && echo 'Alias /uploads /var/www/html/uploads' >> /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/images>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    Require all granted' >> /etc/apache2/sites-available/000-default.conf \
    && echo '</Directory>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/uploads>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    Require all granted' >> /etc/apache2/sites-available/000-default.conf \
    && echo '</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Install Node.js 20.x
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && node -v && npm -v

# Copy app code
WORKDIR /var/www/html
COPY . /var/www/html

# Composer install (uses local composer.phar if present)
RUN if [ -f composer.phar ]; then php composer.phar install --no-dev --prefer-dist; \
    else curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
         && composer install --no-dev --prefer-dist; fi

# Install server-renderer dependencies (Puppeteer will download Chromium)
WORKDIR /var/www/html/server-renderer
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=false \
    PUPPETEER_CACHE_DIR=/var/www/html/server-renderer/.cache
RUN npm ci || npm install

# Restore working dir
WORKDIR /var/www/html

# Create writable directories and set permissions
RUN mkdir -p uploads/avatars uploads/equipment uploads/products uploads/receipts \
    && mkdir -p server-renderer/.cache \
    && chown -R www-data:www-data uploads server-renderer/.cache \
    && chmod -R 775 uploads \
    && chmod -R 775 server-renderer/.cache

# Also ensure images directory is readable
RUN chown -R www-data:www-data images \
    && chmod -R 755 images

# Environment defaults (override with .env)
ENV APP_ENV=production \
    BASE_PATH=/

EXPOSE 80
CMD ["apache2-foreground"]
