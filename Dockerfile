FROM php:8.2-apache

# Cài đặt các system dependencies cần thiết
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt PHP extensions (pdo_mysql, mysqli, zip, gd, mbstring)
RUN docker-php-ext-install pdo pdo_mysql mysqli zip gd mbstring

# Bật Apache mod_rewrite
RUN a2enmod rewrite

# Thiết lập thư mục làm việc
WORKDIR /var/www/html

# Copy source code vào container
COPY . /var/www/html/

# Copy Composer từ image chính thức
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Cài đặt các thư viện PHP thông qua Composer
# Cần set biến môi trường COMPOSER_ALLOW_SUPERUSER để chạy dưới quyền root
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader

# Phân quyền lại cho thư mục
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage/logs || true

# Expose cổng 80 cho Apache
EXPOSE 80
