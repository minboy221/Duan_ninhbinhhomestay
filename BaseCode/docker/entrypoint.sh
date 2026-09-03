#!/bin/sh

set -e

echo "🚀 [1/5] Đang kiểm tra & cài đặt thư viện Composer..."
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

echo "🔑 [2/5] Đang khởi tạo App Key..."
php artisan key:generate --force --no-interaction

echo "🔗 [3/5] Đang tạo Storage Link..."
php artisan storage:link --force --no-interaction

echo "⏳ [4/5] Chờ MySQL Container sẵn sàng kết nối..."
until php -r "try { new PDO('mysql:host='.env('DB_HOST').';port='.env('DB_PORT').';dbname='.env('DB_DATABASE'), env('DB_USERNAME'), env('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }"; do
    sleep 2
done

echo "🗄️ [5/5] Tự động chạy Migration CSDL..."
php artisan migrate --force

echo "⚡ Môi trường đã sẵn sàng! Đang khởi động PHP-FPM..."
exec "$@"
