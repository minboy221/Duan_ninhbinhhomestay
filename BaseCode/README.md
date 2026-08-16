# Duan_ninhbinhhomestay

Base dự án sẽ sử dung mô hình MVC mở rộng sẽ gồm có những phần để xử lý như là: +Controller: nhận yêu cầu và trả ra kết quả
+Services: xử lý logic nghiệp bị thay cho Controller để tránh xử lý nhiều
+Repository: tương tác với Database.

Xác minh khuôn mặt thì cài câu lệnh này: npm install face-api.js
nếu mà tải về bị lỗi thì là do phiên bản vite không hỗ trợ
cách fix: vào file package.json tìm "laravel-vite-plugin": "^0.7.2" rồi đổi thành "laravel-vite-plugin": "^1.0.0" rồi chạy lệnh npm install và sau đó chạy npm install face-api.js

php artisan ziggy:generate chạy thêm phần này để lấy file ảnh private

Giải thích hiển thị của trạng thái phòng - Quản lý trọ :

Trạng thái hiện tại | Hiển thị | khóa phòng  
Còn Trống | Đã Đặt Cọc, Bảo Trì, | Y
Đã Đặt Cọc | Đã Thuê, Còn Trống, | N
Đã Thuê | Sắp Hết HĐ, Bảo Trì, | N
Sắp Hết HĐ | Chờ Gia Hạn, Còn Trống | N
Chờ Gia Hạn | Đã Thuê, Còn Trống | N
Bảo Trì | Còn Trống | Y
Đang Xây dựng | Còn Trống | Y

tải thêm npm install heic2any để chạy file hief thành jpg,...

nếu phần xác minh của admin không hiển thị toạ độ thì mở laragon -> chuột phải -> php -> extensions và bật exif

npm install exifr cài câu lệnh này để đọc ảnh từ file đuôi .heic

npm install @vueup/vue-quill@latest --save chạy câu lệnh để dùng soạn thảo văn bản

Cài PWA: npm install vite-plugin-pwa --save-dev

---

# 🗺️ TÀI LIỆU BẢO VỆ ĐỒ ÁN - TÍCH HỢP BẢN ĐỒ & CHỈ ĐƯỜNG GOOGLE MAPS

Tài liệu này tổng hợp chi tiết **kiến trúc, các bước lập trình, mã nguồn và câu hỏi phản biện hội đồng** về tính năng Bản đồ & Chỉ đường trực tiếp trên website **Ninh Bình HomeStay**.

---

## 📌 1. TỔNG QUAN TÍNH NĂNG (OVERVIEW)

- **Mục tiêu:** Cho phép khách thuê phòng xem bản đồ vị trí thực tế của nhà trọ và tự động tính toán, vẽ đường đi trực tiếp từ vị trí hiện tại của khách đến phòng trọ ngay trên trang web.
- **Vị trí áp dụng:** 
  - Trang **Lịch Hẹn Xem Phòng** (`resources/js/Pages/Profile/lichhen.vue`)
  - Trang **Chi Tiết Trọ** (`resources/js/Pages/Profile/chitiettro.vue`)
- **Công nghệ sử dụng:** Vue 3 (Composition API), HTML5 Geolocation API, Google Maps Embed Iframe Service, CSS Responsive Grid/Flexbox.

---

## 🛠️ 2. CÁC BƯỚC THỰC HIỆN TỪNG BƯỚC (STEP-BY-STEP IMPLEMENTATION)

### BƯỚC 1: Lấy dữ liệu địa chỉ trọ từ Backend (Laravel Eloquent)
- Khi khách xem lịch hẹn, Laravel Controller truy vấn cơ sở dữ liệu lấy thông tin `BoardingHouse` (Địa chỉ chi tiết `address_detail` hoặc `address`) kết nối qua mối quan hệ Model `Room -> BoardingHouse`.
- Dữ liệu được gửi sang Vue Component dưới dạng Props via Inertia.js:
  ```javascript
  const props = defineProps({
      appointments: Array
  });
  ```

### BƯỚC 2: Định vị GPS người dùng (HTML5 Geolocation API)
- Khi người dùng tương tác mở bản đồ hoặc chọn chế độ *"Chỉ đường từ tôi"*, gọi API miễn phí từ trình duyệt để xin tọa độ hiện tại của thiết bị:
  ```javascript
  if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
          (pos) => {
              userCoords.value = {
                  lat: pos.coords.latitude,   // Vĩ độ
                  lng: pos.coords.longitude   // Kinh độ
              };
          },
          (err) => {
              console.warn("Người dùng từ chối cấp quyền vị trí GPS");
          }
      );
  }
  ```

### BƯỚC 3: Xây dựng hàm tạo URL Embed Google Maps động
- Viết hàm JavaScript tạo URL iframe động dựa trên chế độ hiển thị người dùng chọn:
  - **Chế độ xem Vị Trí (`place`):**
    ```javascript
    const url = `https://maps.google.com/maps?q=${encodeURIComponent(address)}&t=&z=15&ie=UTF8&iwloc=&output=embed`;
    ```
  - **Chế độ Chỉ Đường (`directions`):**
    - `saddr` (Source Address - Điểm đi): Tọa độ GPS người dùng (`lat,lng`) hoặc `My+Location`.
    - `daddr` (Destination Address - Điểm đến): Địa chỉ chi tiết của nhà trọ.
    ```javascript
    const url = `https://maps.google.com/maps?saddr=${userCoords.value.lat},${userCoords.value.lng}&daddr=${encodeURIComponent(address)}&output=embed`;
    ```

### BƯỚC 4: Quản lý trạng thái và Nhúng Khung Iframe trong Vue 3
- Tạo các Reactive Reference để lưu vết item đang mở bản đồ:
  ```javascript
  const expandedMapAptId = ref(null); // ID lịch hẹn đang mở bản đồ
  const inlineMapMode = ref("place");  // 'place' hoặc 'directions'
  ```
- Nhúng thẻ `<iframe>` vào giao diện Vue template:
  ```html
  <iframe
      width="100%"
      height="100%"
      style="border: 0; display: block;"
      loading="lazy"
      allowfullscreen
      referrerpolicy="no-referrer-when-downgrade"
      :src="getInlineGoogleMapsEmbedUrl(apt)">
  </iframe>
  ```

### BƯỚC 5: Tối ưu Responsive Đa nền tảng (Desktop & Mobile)
- **Trên Máy tính ($\ge$ 768px):** Nhúng thêm 1 dòng `<tr>` xổ xuống trực tiếp trong `<table>` danh sách.
- **Trên Điện thoại (< 768px):** Dùng CSS Media Queries ẩn Bảng, chuyển sang dạng **Card Thẻ Thông Tin** (`mobile-apt-card`).
- Thanh nút chọn chế độ (*Vị trí / Chỉ đường / Cẩm nang*) được thiết kế Flexbox 100% width vừa khít màn hình smartphone.

---

## 🎯 3. BỘ CÂU HỎI PHẢN BIỆN HỘI ĐỒNG BẢO VỆ & CÁCH TRẢ LỜI (DEFENSE Q&A)

### ❓ Câu 1: Tại sao em dùng Google Maps Embed Iframe mà không dùng Google Maps JavaScript SDK / API Key?
> **Trả lời:** 
> - Google Maps JS SDK yêu cầu đăng ký API Key và bắt buộc phải liên kết thẻ Visa/Mastercard với tài khoản Google Cloud Platform, dễ rủi ro bị tính phí nếu có lượt truy cập lớn hoặc lộ API Key.
> - Giải pháp Embed Iframe hoàn toàn **miễn phí 100%**, không giới hạn số lượt hiển thị, không cần khai báo API Key, tải nhanh và bảo mật tuyệt đối cho dự án.

---

### ❓ Câu 2: Chức năng chỉ đường lấy vị trí khách hàng bằng cách nào? Có chính xác không?
> **Trả lời:** 
> - Em sử dụng **HTML5 Geolocation API** tích hợp sẵn trên các trình duyệt hiện đại (Chrome, Safari, Edge). 
> - Khi người dùng bấm *"Chỉ đường từ tôi"*, trình duyệt sẽ phát tín hiệu xin tọa độ GPS (Kinh độ `lng` & Vĩ độ `lat`) trực tiếp từ chip GPS của điện thoại hoặc IP mạng. Độ chính xác lên tới bán kính vài mét.

---

### ❓ Câu 3: Mở bản đồ trực tiếp trên bảng như vậy có làm vỡ giao diện khi có 10-20 lịch hẹn không?
> **Trả lời:** 
> - Không ạ. Em áp dụng 2 cơ chế bảo vệ layout:
>   1. **Cơ chế Block Flow trong HTML:** Khối bản đồ khi mở ra sẽ đóng vai trò là một phần tử khối, tự động giãn chiều cao container và đẩy các phần tử bên dưới xuống một cách tự nhiên.
>   2. **Cơ chế Phân Trang (Pagination):** Em đã cài đặt phân trang 5 lịch hẹn / 1 trang (`pageSize = 5`). Do đó tổng chiều dài trang web luôn được kiểm soát tối ưu.

---

### ❓ Câu 4: Em làm thế nào để giao diện hiển thị đẹp trên cả máy tính và điện thoại?
> **Trả lời:** 
> - Em viết CSS Responsive dùng `@media (max-width: 767px)`. 
> - Trên máy tính hiển thị dạng Bảng dữ liệu 5 cột (`<table>`). 
> - Trên điện thoại, bảng tự động ẩn đi và thay thế bằng danh sách **Card Thẻ Thông Tin** (`mobile-apt-card`) có kèm nút gọi điện nhanh `tel:` và thanh tab điều khiển bản đồ co giãn 100% không bị đè nút.
