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

---

### ❓ Câu 4: Em làm thế nào để giao diện hiển thị đẹp trên cả máy tính và điện thoại?
> **Trả lời:** 
> - Em viết CSS Responsive dùng `@media (max-width: 767px)`. 
> - Trên máy tính hiển thị dạng Bảng dữ liệu 5 cột (`<table>`). 
> - Trên điện thoại, bảng tự động ẩn đi và thay thế bằng danh sách **Card Thẻ Thông Tin** (`mobile-apt-card`) có kèm nút gọi điện nhanh `tel:` và thanh tab điều khiển bản đồ co giãn 100% không bị đè nút.

---

## 💳 4. TÍNH NĂNG TÍCH HỢP THANH TOÁN TỰ ĐỘNG VIETQR & NGÂN HÀNG WEBHOOK

### 📌 4.1. TỔNG QUAN TÍNH NĂNG (OVERVIEW)
- **Mục tiêu:** Tự động hóa 100% quy trình gạch nợ hóa đơn tiền trọ khi khách hàng quét mã VietQR chuyển khoản qua app ngân hàng mà không cần chủ trọ thao tác duyệt tay.
- **Vị trí áp dụng:** 
  - Giao diện Khách Thuê: Trang **Quản Lý Hóa Đơn Trọ** (`resources/js/Pages/Profile/listthanhtoan.vue`)
  - Giao diện Chủ Trọ: Trang **Quản Lý Hóa Đơn** (`resources/js/Pages/Landlord/Invoices/index.vue`)
  - Cổng tiếp nhận Webhook API: (`app/Http/Controllers/Api/PaymentWebhookController.php`) & Route (`routes/api.php`)
- **Công nghệ sử dụng:** VietQR Dynamic Image API, Laravel Webhook Controller, Regex Content Matching Algorithm, Vue 3 Composition API + Inertia Partial Reload Polling.

---

### 🛠️ 4.2. QUY TRÌNH KỸ THUẬT & KIẾN TRÚC MÃ NGUỒN (ARCHITECTURAL FLOW)

#### BƯỚC 1: Sinh Ảnh Mã QR Động VietQR (`resources/js/Pages/Profile/listthanhtoan.vue` - Dòng 291 đến 325)
- Em sử dụng dịch vụ **VietQR Dynamic Image Quick Link API** (`https://img.vietqr.io/image/...`) chuẩn Napas247 để tự động sinh mã QR chứa sẵn **Số tài khoản chủ trọ**, **Số tiền chính xác** và **Nội dung chuyển khoản chứa Mã Hóa Đơn**:
  ```javascript
  // 1. Tự động chuẩn hóa chuỗi ghi chú chuyển khoản chứa mã HD (Dòng 291 - 314)
  const transferMemo = computed(() => {
      const cleanInvCode = (activeInvoice.value.invoice_code || "").replace('#', '');
      const roomStr = activeInvoice.value.contract?.room?.name || "";
      const monthStr = (activeInvoice.value.billing_month || "").replace('/', '');
      return `${cleanInvCode} ${roomStr} TT thang ${monthStr}`;
  });

  // 2. Tạo URL ảnh VietQR Động theo thời gian thực (Dòng 318 - 325)
  const qrUrl = computed(() => {
      if (!activeInvoice.value) return "";
      const { bankName, bankAcc, bankAccName } = landlordBankInfo.value;
      const amount = Math.round(activeInvoice.value.total_amount);
      const memo = transferMemo.value;

      return `https://img.vietqr.io/image/${bankName}-${bankAcc}-compact2.png?amount=${amount}&addInfo=${encodeURIComponent(memo)}&accountName=${encodeURIComponent(bankAccName)}`;
  });
  ```

#### BƯỚC 2: Ngân hàng tiếp nhận & Bắn tín hiệu Webhook ngầm về Backend (`routes/api.php` - Dòng 24 & `VerifyCsrfToken.php` - Dòng 15)
- Khách dùng app ngân hàng (MB, VietinBank, VCB...) quét mã và xác nhận chuyển khoản.
- Cổng thanh toán (SePay/PayOS) nhận biến động số dư và bắn một yêu cầu `POST /api/webhooks/payment` chứa dữ liệu JSON (số tiền, nội dung chuyển khoản) tới hệ thống Laravel.

#### BƯỚC 3: Xử lý Webhook & Thuật toán bóc tách dữ liệu (`app/Http/Controllers/Api/PaymentWebhookController.php` - Dòng 15 đến 157)
- Route `/api/webhooks/payment` được miễn kiểm tra CSRF trong `VerifyCsrfToken.php` (Dòng 15).
- Hàm `handleWebhook(Request $request)` (Dòng 15-63) tiếp nhận và gọi `findInvoiceFromContent($content)` (Dòng 106-157):
  - **Thuật toán chuẩn hóa chuỗi:** Tự động chuyển in hoa, bỏ dấu tiếng Việt, loại bỏ ký tự đặc biệt (`#`, `-`, `_`, khoảng trắng).
  - Dò tìm thông minh qua 4 cấp độ: (1) Mã Hóa Đơn `HD...` $\rightarrow$ (2) Kết hợp Số Phòng + Tháng thanh toán (hỗ trợ `YYYYMM`, `YYYY-MM`, `MM/YYYY`) $\rightarrow$ (3) Tên khách thuê $\rightarrow$ (4) Đối soát số tiền.
  - Cập nhật CSDL: `$invoice->update(['status' => 'paid', 'paid_at' => now()])` (Dòng 50-53).

#### BƯỚC 4: Tự động cập nhật giao diện Chủ Trọ & Khách Thuê ngầm (No F5 / Partial Reload)
- **Phía Khách Thuê (`listthanhtoan.vue` - Dòng 30-57):** Chạy `setInterval` polling mỗi 3s kiểm tra API `/api/invoices/{id}/status`. Ngay khi status đổi sang `'paid'`, hiển thị thông báo thành công.
- **Phía Chủ Trọ (`resources/js/Pages/Landlord/Invoices/index.vue` - Dòng 35-46):** Chạy `setInterval` ngầm mỗi 5s gọi `router.reload({ preserveScroll: true, only: ['invoices'] })`.
- Dòng hóa đơn tự động chuyển từ **🟡 Chưa thanh toán** $\rightarrow$ **🟢 Đã nhận** mà **KHÔNG CẦN F5 hay tải lại trang**.

---

### 🎯 4.3. BỘ CÂU HỎI PHẢN BIỆN HỘI ĐỒNG BẢO VỆ VỀ THANH TOÁN (PAYMENT DEFENSE Q&A)

### ❓ Câu 5: Nếu khách quét mã QR, đến bước chuyển tiền trong App ngân hàng rồi HỦY NGANG, sau đó quay lại web bấm "Tôi đã chuyển khoản", hệ thống có bị đánh lừa và báo thành công không?
> **Trả lời:**
> - **Tuyệt đối KHÔNG ạ!** 
> - Nút "Tôi đã chuyển khoản" trên web thực chất **chỉ gửi một thông báo chờ cho Chủ trọ**, nút này **KHÔNG CÓ QUYỀN** đổi trạng thái hóa đơn sang "Đã thanh toán" (`paid`). Hóa đơn vẫn giữ nguyên trạng thái **🟡 Chưa thanh toán**.
> - Trạng thái hóa đơn **CHỈ TỰ ĐỘNG CHUYỂN SANG ĐÃ THANH TOÁN** khi Ngân hàng gửi tín hiệu Webhook bảo mật xác nhận tiền đã thực sự vào tài khoản, hoặc do Chủ trọ bấm duyệt tay sau khi kiểm tra số dư.

---

### ❓ Câu 6: Em làm thế nào để khi tiền vừa "Ting Ting" về tài khoản thì màn hình Chủ trọ tự động chuyển màu xanh "Đã nhận" mà không cần bấm F5?
> **Trả lời:**
> - Em kết hợp giữa **Laravel Webhook API** ở Backend và **Inertia Partial Reload Polling** ở Frontend.
> - Khi tiền về, Backend cập nhật trạng thái hóa đơn trong MySQL thành `paid` chỉ mất 0.1s.
> - Tại giao diện Chủ trọ (`Invoices/index.vue`), em cài đặt bộ đếm `setInterval` chạy ngầm mỗi 5 giây gọi `router.reload({ preserveScroll: true, only: ['invoices'] })`. 
> - Tính năng Partial Reload chỉ kéo dữ liệu JSON của danh sách hóa đơn về mà không tải lại toàn bộ trang HTML/CSS, sau đó nhờ Reactivity System của Vue 3, dòng hóa đơn tự động chuyển sang màu xanh **🟢 Đã nhận** mượt mà.

---

### ❓ Câu 7: Nếu nội dung chuyển khoản ngân hàng bị cắt ngắn hoặc biến dạng (ví dụ bỏ dấu gạch, mất ký tự #), làm sao hệ thống khớp đúng hóa đơn?
> **Trả lời:**
> - Trong file `PaymentWebhookController.php`, em xây dựng hàm `findInvoiceFromContent($content)` với thuật toán chuẩn hóa dữ liệu đa lớp:
>   1. **Bước 1 (Regex Normalization):** Loại bỏ toàn bộ ký tự `#`, `-`, khoảng trắng và chuyển về chuỗi in hoa không dấu.
>   2. **Bước 2 (Multi-pattern Matching):** Dùng Regular Expression phân tích linh hoạt mọi kiểu viết tháng thanh toán (`202609`, `2026-09`, `09/2026`).
>   3. **Bước 3 (Fallback Safety):** Nếu mã HĐ bị mờ/mất, hệ thống tiếp tục đối soát Số phòng + Tên khách thuê + Số tiền hóa đơn để đảm bảo khớp 100% không bị nhầm lẫn.

---

### ❓ Câu 8: Em sinh ảnh mã QR thanh toán VietQR Động như thế nào? Có cần cài thư viện nặng hay mua dịch vụ trả phí không?
> **Trả lời:**
> - Em sử dụng dịch vụ **VietQR Dynamic Image Quick Link API (Chuẩn Napas247)**: 
>   `https://img.vietqr.io/image/{BANK_NAME}-{ACCOUNT_NO}-{TEMPLATE}.png`
> - Em truyền động 3 thông số chính qua Query Parameters:
>   1. `amount`: Số tiền hóa đơn (`Math.round(total_amount)`).
>   2. `addInfo`: Chuỗi nội dung chuyển khoản chứa mã HĐ (`encodeURIComponent(transferMemo)`).
>   3. `accountName`: Tên chủ tài khoản ngân hàng của chủ trọ.
> - **Ưu điểm:** Giải pháp hoàn toàn **miễn phí 100%**, tải ảnh cực nhanh, không cần cài đặt thư viện nặng trên server, chuẩn định dạng Napas247 nên 100% ứng dụng ngân hàng (MB, VietinBank, Vietcombank, Techcombank, BIDV, Agribank...) quét vào tự động điền đúng Số tiền & Nội dung chuyển khoản.

---

### 🚨 4.4. LƯU Ý QUAN TRỌNG KHI TRIỂN KHAI LÊN SERVER THỰC TẾ (PRODUCTION DEPLOYMENT)

1. **Khi chạy thử nghiệm trên máy tính cá nhân (Localhost):**
   - Mở Terminal chạy lệnh: `npx localtunnel --port 8000` (hoặc `ngrok http 8000`).
   - Lấy URL tạm thời được cấp (Ví dụ: `https://wicked-boxes-double.loca.lt/api/webhooks/payment`) để dán vào SePay/PayOS khi test.

2. **Khi đưa dự án lên Server/Hosting thật có Tên miền (Production):**
   - **Chỉ thực hiện đúng 1 lần duy nhất:** Đăng nhập trang quản trị SePay ([my.sepay.vn](https://my.sepay.vn)) $\rightarrow$ Mục **Webhooks** $\rightarrow$ Sửa URL Webhook thành tên miền chính thức:
     👉 `https://TEN_MIEN_CUA_BAN.com/api/webhooks/payment`
   - Chọn định dạng gửi: **JSON**.
   - **Kết quả:** Hệ thống sẽ tự động gạch nợ **24/7/365 vĩnh viễn**, không phụ thuộc vào máy tính cá nhân, không bao giờ bị tắt và không cần chạy thêm bất kỳ câu lệnh nào!
