# 🏢 HỆ THỐNG QUẢN LÝ PHÒNG TRỌ & HOMESTAY THÔNG MINH
> **Đồ Án Tốt Nghiệp / Sản Phẩm Enterprise - Laravel 10 + Vue 3 (Inertia.js)**

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D)
![Inertia.js](https://img.shields.io/badge/Inertia.js-9553E9?style=for-the-badge&logo=inertia&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white)

---

## 📖 GIỚI THIỆU HỆ THỐNG

Hệ thống quản lý nhà trọ và kết nối thuê phòng thông minh được thiết kế nhằm tối ưu hóa toàn bộ quy trình giữa **Chủ trọ**, **Khách thuê** và **Quản trị viên (Admin)**.

Hệ thống tích hợp các công nghệ tiên tiến nhất:
* 🤖 **Trợ lý AI Gemini**: Tìm kiếm phòng trọ thông minh bằng ngôn ngữ tự nhiên tiếng Việt, hỗ trợ tiếng lóng/từ viết tắt (VD: `2củ5`, `1tr5`).
* ⚡ **Cơ chế AI Offline Fallback**: Tự động giải nghĩa bằng bộ mã Regex/NLP offline khi mất kết nối API Gemini.
* ☁️ **Lưu trữ Đám mây Cloudflare R2**: Tối ưu tốc độ tải ảnh phòng trọ và chứng từ hợp đồng.
* 🔔 **Thông báo thời gian thực (FCM)**: Gửi Push Notification trực tiếp về điện thoại khi có lịch xem phòng/hóa đơn mới.
* 📄 **Đọc ảnh thông minh OCR & Xuất PDF**: Tự động đọc chỉ số đồng hồ điện nước từ ảnh chụp và xuất hợp đồng/hóa đơn PDF.
* 🐳 **Tự động hóa Docker Desktop**: Khởi tạo toàn bộ môi trường chạy thực tế chỉ với **1 lệnh duy nhất**.

---

## 🛠️ CÔNG NGHỆ & KIẾN TRÚC (TECH STACK)

| Thành phần | Công nghệ sử dụng |
| :--- | :--- |
| **Backend Framework** | Laravel 10.x (PHP 8.1+) |
| **Frontend Framework** | Vue 3.x (Composition API) + Inertia.js (Single Page Application) |
| **Styling & UI** | Tailwind CSS + SweetAlert2 |
| **Database & Cache** | MySQL 8.0 & Redis 7.0 |
| **Cloud Services** | Cloudflare R2 Storage (S3 API), Google Gemini AI API, Firebase FCM |
| **Architecture Pattern** | Repository Pattern, Service Layer, Custom Form Requests |
| **Testing Safety Net** | PHPUnit Automated Test Suite (100% Passing) |
| **DevOps Container** | Docker Desktop (PHP-FPM 8.1, Nginx Alpine, MySQL 8.0, Redis) |

---

## ⚡ HƯỚNG DẪN KHỞI CHẠY BẰNG DOCKER DESKTOP (TỰ ĐỘNG 100%)

### 1️⃣ Cấu hình file `.env`
Mở file `BaseCode/.env` và đảm bảo các thông số kết nối Docker như sau:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=datn_homestay
DB_USERNAME=root
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PORT=6379
```

### 2️⃣ Chạy lệnh khởi chạy duy nhất
Mở **Terminal (PowerShell)** tại thư mục `BaseCode` và gõ câu lệnh:

```bash
docker-compose up -d --build
```

> 🪄 **Cơ chế tự động hóa (Automated Entrypoint)**:
> Hệ thống sẽ tự động thực thi chuỗi thao tác mà bạn không cần gõ thêm lệnh thủ công:
> 1. ✅ Tự kiểm tra & cài đặt gói Composer
> 2. ✅ Tự động tạo `APP_KEY`
> 3. ✅ Tự động tạo liên kết `storage:link`
> 4. ✅ Tự động chờ MySQL sẵn sàng và chạy `migrate` CSDL
> 5. ✅ Tự động bật Nginx Web Server

👉 **Đường dẫn truy cập ứng dụng**: [http://localhost:8000](http://localhost:8000)

---

## 💻 HƯỚNG DẪN KHỞI CHẠY BẰNG LARAGON / LOCAL PHP

Nếu bạn muốn chạy ứng dụng trực tiếp bằng môi trường Laragon local:

1. Chỉnh sửa file `BaseCode/.env`:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   REDIS_HOST=127.0.0.1
   ```
2. Chạy lệnh phía dưới trên Terminal Windows:
   ```bash
   php artisan config:clear
   php artisan migrate
   php artisan serve
   ```
3. Chạy song song Terminal biên dịch giao diện Vue:
   ```bash
   npm run dev
   ```

---

## 🧪 CHẠY KIỂM THỬ TỰ ĐỘNG (UNIT TESTS)

Để chạy kiểm thử tự động toàn bộ logic tính toán hóa đơn và hợp đồng:

```bash
# Chạy trong Container Docker
docker exec -it datn_app php artisan test

# Hoặc chạy trên môi trường Local
php artisan test
```

---
