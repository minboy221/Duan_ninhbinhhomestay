# Sử dụng PHP 8.1 FPM chính thức trên nền Linux Alpine siêu nhẹ
FROM php:8.1-fpm-alpine

# Cài đặt các extension hệ thống cần thiết cho Laravel (GD, Zip, PDO MySQL, BCMath, Intl)
RUN apk add --no-cache \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    icu-dev \
    icu-libs

# Cấu hình & Cài đặt PHP Extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mbstring zip bcmath intl opcache

# Cài đặt Composer phiên bản mới nhất từ bản Docker chính thức
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Đặt thư mục làm việc trong container
WORKDIR /var/www

# Copy quyền sở hữu tệp tin
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]
