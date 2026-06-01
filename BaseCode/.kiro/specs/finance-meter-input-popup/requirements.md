# Requirements Document

## Introduction

Tính năng này thay đổi cách nhập số liệu điện/nước trên trang "Quản Lý Tài Chính" của chủ trọ (Landlord Finance). Hiện tại, bảng "Nhập Chỉ Số Điện / Nước & Tính Tiền" cho phép sửa trực tiếp (inline) trong từng ô của bảng đối với: số người, chỉ số điện cũ/mới, chỉ số nước cũ/mới.

Mục tiêu là chuyển bảng này sang chế độ **chỉ đọc** (read-only, chỉ hiển thị, không có ô nhập inline), và việc nhập liệu được thực hiện qua một **popup/modal** mở ra khi người dùng bấm một nút "Nhập số liệu" duy nhất đặt ở đầu bảng. Trong popup, người dùng **chọn phòng cần nhập từ một danh sách thả xuống (dropdown / select)**, sau đó nhập số liệu cho phòng đã chọn. Sau khi lưu trong popup, bảng sẽ cập nhật lại giá trị và các kết quả tính toán (tiêu thụ, tiền điện, tiền nước, tổng).

Tính năng nằm trong file `resources/js/Pages/Landlord/Finance/index.vue` (Vue 3 + Inertia + Tailwind). Dữ liệu hiện đang lưu ở frontend qua `localStorage` (mock data), chưa kết nối backend thực. Phạm vi tính năng này giữ nguyên cơ chế lưu dữ liệu hiện tại (localStorage), chỉ thay đổi cách nhập liệu.

## Glossary

- **Trang_Tài_Chính (Finance_Page)**: Trang `resources/js/Pages/Landlord/Finance/index.vue` hiển thị bảng chỉ số và tính tiền cho chủ trọ.
- **Bảng_Chỉ_Số (Reading_Table)**: Bảng "Nhập Chỉ Số Điện / Nước & Tính Tiền" hiển thị danh sách phòng và số liệu.
- **Popup_Nhập_Liệu (Input_Modal)**: Cửa sổ modal mở ra để nhập số liệu cho một phòng, hiển thị bằng `Teleport` theo mẫu modal "Ảnh Đồng Hồ" đang có.
- **Nút_Nhập_Số_Liệu (Enter_Data_Button)**: Nút "Nhập số liệu" duy nhất đặt ở đầu Bảng_Chỉ_Số dùng để mở Popup_Nhập_Liệu.
- **Bộ_Chọn_Phòng (Room_Selector)**: Danh sách thả xuống (select / dropdown) trong Popup_Nhập_Liệu để chọn Phòng cần nhập số liệu.
- **Phòng (Room)**: Một bản ghi dữ liệu gồm: `id`, `name`, `tenants` (số người), `rent` (tiền phòng), `elecStart`/`elecEnd` (chỉ số điện cũ/mới), `waterStart`/`waterEnd` (chỉ số nước cũ/mới), `elecPrice`/`waterPrice` (đơn giá), `status`.
- **Phòng_Được_Chọn (Selected_Room)**: Phòng đang được chọn trong Bộ_Chọn_Phòng để nhập số liệu.
- **Tiêu_Thụ_Điện**: Giá trị `elecEnd - elecStart`.
- **Tiêu_Thụ_Nước**: Giá trị `waterEnd - waterStart`.
- **Tiền_Điện**: Giá trị `Tiêu_Thụ_Điện × elecPrice`.
- **Tiền_Nước**: Giá trị `Tiêu_Thụ_Nước × waterPrice`.
- **Tổng (Total)**: Giá trị `rent + Tiền_Điện + Tiền_Nước`.
- **Lưu_Trữ_Cục_Bộ (Local_Storage)**: Cơ chế lưu dữ liệu phòng ở khóa `landlord_rooms` trong `localStorage` của trình duyệt.

## Requirements

### Requirement 1: Bảng chỉ số ở chế độ chỉ đọc

**User Story:** Là chủ trọ, tôi muốn bảng chỉ số chỉ hiển thị số liệu mà không cho sửa trực tiếp trong ô, để tránh nhập nhầm và có giao diện gọn gàng hơn.

#### Acceptance Criteria

1. THE Bảng_Chỉ_Số SHALL hiển thị giá trị số người dưới dạng văn bản chỉ đọc, không có ô nhập liệu.
2. THE Bảng_Chỉ_Số SHALL hiển thị chỉ số điện cũ và chỉ số điện mới dưới dạng văn bản chỉ đọc, không có ô nhập liệu.
3. THE Bảng_Chỉ_Số SHALL hiển thị chỉ số nước cũ và chỉ số nước mới dưới dạng văn bản chỉ đọc, không có ô nhập liệu.
4. THE Bảng_Chỉ_Số SHALL hiển thị Tiêu_Thụ_Điện, Tiêu_Thụ_Nước, Tiền_Điện, Tiền_Nước, và Tổng cho mỗi Phòng dựa trên dữ liệu hiện tại.
5. THE Bảng_Chỉ_Số SHALL giữ nguyên các cột hiện có: Phòng, Số người, Tiền phòng, Điện (Số cũ/Số mới/Tiêu thụ), Tiền điện, Nước (Số cũ/Số mới/Tiêu thụ), Tiền nước, Tổng, Ảnh ĐH, Hành động.

### Requirement 2: Nút mở popup nhập số liệu

**User Story:** Là chủ trọ, tôi muốn có một nút "Nhập số liệu" ở đầu bảng, để mở form nhập liệu chung mà không phải thao tác trên từng dòng.

#### Acceptance Criteria

1. THE Bảng_Chỉ_Số SHALL hiển thị một Nút_Nhập_Số_Liệu duy nhất ở khu vực đầu bảng (phần tiêu đề của card chứa Bảng_Chỉ_Số).
2. WHEN người dùng bấm Nút_Nhập_Số_Liệu, THE Trang_Tài_Chính SHALL mở Popup_Nhập_Liệu.
3. THE Trang_Tài_Chính SHALL giữ nguyên các hành động hiện có của mỗi dòng (Tạo Hóa Đơn khi chưa thanh toán, hoặc trạng thái "Đã thanh toán") và nút mở modal Ảnh Đồng Hồ.

### Requirement 3: Chọn phòng trong popup bằng danh sách thả xuống

**User Story:** Là chủ trọ, tôi muốn chọn phòng cần nhập từ một danh sách thả xuống trong popup, để thao tác đơn giản và rõ ràng hơn so với nhập trực tiếp trên bảng.

#### Acceptance Criteria

1. WHEN Popup_Nhập_Liệu mở, THE Popup_Nhập_Liệu SHALL hiển thị một Bộ_Chọn_Phòng chứa danh sách tất cả các Phòng hiện có trong Bảng_Chỉ_Số.
2. THE Bộ_Chọn_Phòng SHALL hiển thị tên mỗi Phòng làm nhãn của từng lựa chọn.
3. WHEN Popup_Nhập_Liệu mở, THE Bộ_Chọn_Phòng SHALL chọn sẵn một Phòng mặc định là Phòng đầu tiên trong danh sách.
4. WHEN người dùng chọn một Phòng khác trong Bộ_Chọn_Phòng, THE Popup_Nhập_Liệu SHALL nạp lại các ô nhập theo giá trị hiện tại của Phòng_Được_Chọn.

### Requirement 4: Hiển thị form nhập với giá trị hiện tại của phòng được chọn

**User Story:** Là chủ trọ, tôi muốn popup hiển thị sẵn số liệu hiện tại của phòng đã chọn, để tôi chỉnh sửa dễ dàng mà không phải nhập lại từ đầu.

#### Acceptance Criteria

1. WHILE một Phòng đang được chọn trong Bộ_Chọn_Phòng, THE Popup_Nhập_Liệu SHALL hiển thị các ô nhập cho: số người, chỉ số điện cũ, chỉ số điện mới, chỉ số nước cũ, chỉ số nước mới.
2. WHEN một Phòng được chọn trong Bộ_Chọn_Phòng, THE Popup_Nhập_Liệu SHALL điền sẵn mỗi ô nhập bằng giá trị hiện tại tương ứng của Phòng_Được_Chọn.
3. WHILE người dùng đang chỉnh sửa giá trị trong Popup_Nhập_Liệu, THE Popup_Nhập_Liệu SHALL hiển thị các giá trị tính toán xem trước gồm Tiêu_Thụ_Điện, Tiêu_Thụ_Nước, Tiền_Điện, Tiền_Nước và Tổng dựa trên giá trị đang nhập.
4. THE Popup_Nhập_Liệu SHALL chỉ nhận giá trị số cho các ô số người, chỉ số điện và chỉ số nước.

### Requirement 5: Lưu số liệu và cập nhật bảng

**User Story:** Là chủ trọ, tôi muốn lưu số liệu đã nhập trong popup, để bảng hiển thị giá trị mới và tính lại tổng tiền.

#### Acceptance Criteria

1. WHEN người dùng bấm nút lưu trong Popup_Nhập_Liệu với dữ liệu hợp lệ, THE Trang_Tài_Chính SHALL cập nhật số người, chỉ số điện cũ/mới và chỉ số nước cũ/mới của Phòng_Được_Chọn theo giá trị trong Popup_Nhập_Liệu.
2. WHEN số liệu của một Phòng được cập nhật, THE Bảng_Chỉ_Số SHALL hiển thị lại Tiêu_Thụ_Điện, Tiêu_Thụ_Nước, Tiền_Điện, Tiền_Nước và Tổng được tính lại theo giá trị mới.
3. WHEN số liệu của một Phòng được cập nhật, THE Trang_Tài_Chính SHALL tính lại các chỉ số tổng hợp gồm Tổng dự kiến, Đã thu và Còn nợ.
4. WHEN người dùng lưu số liệu thành công, THE Trang_Tài_Chính SHALL lưu dữ liệu Phòng đã cập nhật vào Lưu_Trữ_Cục_Bộ ở khóa `landlord_rooms`.
5. WHEN người dùng lưu số liệu thành công, THE Popup_Nhập_Liệu SHALL đóng lại.

### Requirement 6: Hủy nhập liệu mà không thay đổi dữ liệu

**User Story:** Là chủ trọ, tôi muốn hủy thao tác nhập liệu, để dữ liệu phòng không bị thay đổi khi tôi đổi ý.

#### Acceptance Criteria

1. WHEN người dùng bấm nút đóng hoặc nút hủy trong Popup_Nhập_Liệu, THE Trang_Tài_Chính SHALL đóng Popup_Nhập_Liệu mà không thay đổi dữ liệu của bất kỳ Phòng nào.
2. WHEN người dùng bấm vào vùng nền tối bên ngoài Popup_Nhập_Liệu, THE Trang_Tài_Chính SHALL đóng Popup_Nhập_Liệu mà không thay đổi dữ liệu của bất kỳ Phòng nào.
3. WHEN người dùng mở lại Popup_Nhập_Liệu sau khi đã hủy, THE Popup_Nhập_Liệu SHALL hiển thị lại giá trị hiện tại chưa thay đổi của Phòng_Được_Chọn.

### Requirement 7: Kiểm tra tính hợp lệ của số liệu nhập

**User Story:** Là chủ trọ, tôi muốn hệ thống cảnh báo khi tôi nhập số liệu không hợp lý, để tránh tính sai tiền điện nước.

#### Acceptance Criteria

1. IF chỉ số điện mới nhỏ hơn chỉ số điện cũ, THEN THE Popup_Nhập_Liệu SHALL hiển thị thông báo lỗi và SHALL giữ trạng thái không cho lưu.
2. IF chỉ số nước mới nhỏ hơn chỉ số nước cũ, THEN THE Popup_Nhập_Liệu SHALL hiển thị thông báo lỗi và SHALL giữ trạng thái không cho lưu.
3. IF số người nhỏ hơn 1, THEN THE Popup_Nhập_Liệu SHALL hiển thị thông báo lỗi và SHALL giữ trạng thái không cho lưu.
4. IF bất kỳ ô số người, chỉ số điện hoặc chỉ số nước nào để trống hoặc không phải số, THEN THE Popup_Nhập_Liệu SHALL hiển thị thông báo lỗi và SHALL giữ trạng thái không cho lưu.
