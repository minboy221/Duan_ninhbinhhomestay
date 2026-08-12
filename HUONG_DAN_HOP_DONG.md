# Hướng dẫn triển khai: Chức năng Hợp đồng thuê phòng

> **Dự án**: Ninh Bình Homestay / StayWork  
> **Ngày tạo**: 01/07/2026  
> **Trạng thái**: Chưa triển khai — Lưu để làm sau  
> **Đã thống nhất với**: Thầy hướng dẫn + Nhóm

---

## 1. Tổng quan luồng xử lý toàn hệ thống

```
Chủ trọ đăng tin phòng trống
        ↓
Admin duyệt bài đăng
        ↓
Hiển thị trên trang Client
        ↓
Người dùng xem bài đăng, ưng ý
        ↓
Gọi / Nhắn tin liên hệ chủ trọ
        ↓
Hẹn xem phòng trực tiếp ngoài đời
        ↓
Thỏa thuận giá, cọc, thời hạn
        ↓
Ký hợp đồng GIẤY bằng tay + Đặt cọc tiền mặt
        ↓
Chủ trọ lên hệ thống số hóa hợp đồng (CHI TIẾT BÊN DƯỚI)
        ↓
Hệ thống tạo hợp đồng + gán người thuê vào phòng
        ↓
Hàng tháng: Chủ trọ tạo hóa đơn (ĐÃ LÀM XONG)
        ↓
Người thuê nhận thông báo + Thanh toán (ĐÃ LÀM XONG)
```

---

## 2. Chi tiết Giai đoạn số hóa hợp đồng (Phần quan trọng nhất)

Sau khi ký hợp đồng giấy ngoài đời thực, **chủ trọ** vào trang "Tạo hợp đồng mới" trên hệ thống:

### Bước 1 — Chọn phòng
- Chọn nhà trọ → Chọn tầng → Chọn phòng (chỉ hiện phòng trạng thái `available`)
- Hệ thống tự động điền giá thuê, diện tích từ thông tin phòng

### Bước 2 — Tìm kiếm & Gán người thuê
- Chủ trọ nhập **SĐT hoặc Email** của người thuê (đã biết từ khi gặp ngoài đời)
- Hệ thống tra cứu trong bảng `users`:
  - **Tìm thấy tài khoản** → Tự động điền: Họ tên, SĐT, CCCD → Gắn `tenant_id`
  - **Không tìm thấy** → Hệ thống tự tạo tài khoản mới (role: `tenant`, mật khẩu mặc định) → Gửi SMS/Email thông báo cho người thuê biết

### Bước 3 — Upload hợp đồng giấy đã ký
- Chủ trọ **chụp ảnh** các trang hợp đồng đã ký tay bằng điện thoại
- Upload ảnh lên hệ thống (cho phép upload nhiều ảnh)
- Hệ thống **tự động ghép các ảnh thành 1 file PDF** → Lưu vào `contract_file_path`
- Cả chủ trọ và người thuê đều có thể xem/download file PDF này

### Bước 4 — Điền thông tin hợp đồng
- Ngày bắt đầu hiệu lực (`start_date`)
- Ngày kết thúc (`end_date`)
- Tiền đặt cọc (`deposit_amount`)
- Tiền thuê hàng tháng
- Chu kỳ đóng tiền (1 tháng/lần, 3 tháng/lần...)
- Nhấn **"Xác nhận tạo hợp đồng"**

### Sau khi tạo xong
- Phòng tự động chuyển trạng thái: `available` → `deposited` / `rented`
- Người thuê đăng nhập → Xem được hợp đồng PDF, thông tin phòng
- Chủ trọ bắt đầu tạo hóa đơn hàng tháng (phần này ĐÃ LÀM XONG)

---

## 3. Cấu trúc Database (Bảng `contracts` — ĐÃ CÓ SẴN)

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | Khóa chính |
| `tenant_id` | bigint (FK → users) | ID người thuê |
| `room_id` | bigint (FK → rooms) | ID phòng |
| `start_date` | date | Ngày bắt đầu |
| `end_date` | date | Ngày kết thúc |
| `deposit_amount` | decimal | Tiền cọc |
| `contract_file_path` | string | Đường dẫn file PDF hợp đồng |
| `status` | string | `draft`, `signed`, `expired`, `terminated` |
| `signed_at` | datetime | Thời điểm ký |

---

## 4. Các file cần tạo / sửa khi triển khai

### Backend (Laravel)

| File | Hành động | Mô tả |
|------|-----------|-------|
| `app/Http/Controllers/LandlordController.php` | Thêm methods | `contracts()`, `storeContract()`, `showContract()`, `updateContract()` |
| `routes/web.php` | Thêm routes | CRUD routes cho hợp đồng dưới prefix `landlord` |
| `app/Services/ContractService.php` | Tạo mới | Logic: tìm tenant, tạo tài khoản, ghép ảnh thành PDF |
| `app/Services/PdfService.php` | Tạo mới | Chuyển đổi ảnh upload → file PDF |

### Frontend (Vue / Inertia)

| File | Hành động | Mô tả |
|------|-----------|-------|
| `resources/js/Pages/Landlord/Contracts/index.vue` | Sửa lại | Kết nối backend thay vì dữ liệu hardcode |
| Form Bước 2 trong file trên | Sửa | Thêm ô tìm kiếm tenant bằng SĐT/Email |
| Form Bước 3 trong file trên | Sửa | Thêm phần upload ảnh hợp đồng (nhiều ảnh) |
| `resources/js/Pages/Client/quanlynoio.vue` | Sửa | Hiển thị hợp đồng PDF cho người thuê xem |

---

## 5. API Endpoints cần tạo

```
GET    /landlord/contracts              → Danh sách hợp đồng
POST   /landlord/contracts              → Tạo hợp đồng mới (kèm upload ảnh)
GET    /landlord/contracts/{id}         → Chi tiết hợp đồng
PUT    /landlord/contracts/{id}         → Cập nhật hợp đồng
PATCH  /landlord/contracts/{id}/status  → Đổi trạng thái (gia hạn, chấm dứt)
DELETE /landlord/contracts/{id}         → Xóa hợp đồng

GET    /landlord/search-tenant?q=...    → Tìm kiếm người thuê bằng SĐT/Email
GET    /landlord/contracts/{id}/pdf     → Download file PDF hợp đồng
```

---

## 6. Thư viện cần cài thêm

```bash
# Ghép ảnh thành PDF
composer require barryvdh/laravel-dompdf

# Hoặc dùng Intervention Image để xử lý ảnh trước khi ghép
composer require intervention/image
```

---

## 7. Giải đáp thắc mắc chính

**Q: Chủ trọ lấy thông tin người thuê từ đâu để tạo hợp đồng trên hệ thống?**

A: Chủ trọ đã gặp người thuê ngoài đời, biết SĐT/Email. Khi lên hệ thống, nhập SĐT/Email đó vào ô tìm kiếm → Hệ thống tìm tài khoản → Tự động liên kết `tenant_id`. Nếu người thuê chưa có tài khoản, hệ thống tự tạo cho họ.

**Q: Hợp đồng giấy ký tay xử lý thế nào?**

A: Chụp ảnh → Upload lên hệ thống → Hệ thống tự ghép thành PDF → Lưu vào `contract_file_path`. Cả hai bên đều xem/download được.

**Q: Sau khi có hợp đồng thì hóa đơn hoạt động thế nào?**

A: Phần hóa đơn ĐÃ LÀM XONG. Khi hợp đồng được tạo, chủ trọ vào trang Hóa đơn → Chọn hợp đồng → Tạo hóa đơn tháng (điện, nước, tiền thuê) → Người thuê nhận thông báo → Thanh toán → Báo đã trả.

---

## 8. Tiến độ các phần liên quan

- ✅ Quản lý Phòng trọ (`Landlord/Rooms/index.vue`) — ĐÃ XONG
- ✅ Đăng tin & Duyệt (`Landlord/Listings/`, `Admin/Approval/`) — ĐÃ XONG
- ✅ Hóa đơn hàng tháng (`Landlord/Invoices/index.vue`) — ĐÃ XONG
- ✅ Thanh toán & Thông báo (`Client/listthanhtoan.vue`) — ĐÃ XONG
- ⏳ Hợp đồng (`Landlord/Contracts/index.vue`) — CHƯA LÀM, file này hướng dẫn triển khai
