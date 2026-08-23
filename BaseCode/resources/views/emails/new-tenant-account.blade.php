<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Thông tin tài khoản & Hợp đồng thuê phòng</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;">
        <!-- Header logo / Brand -->
        <div style="text-align: center; margin-bottom: 25px; border-b: 2px solid #10b981; padding-bottom: 15px;">
            <h2 style="color: #059669; margin: 0; font-size: 24px;">Ninh Bình HomeStay</h2>
            <p style="color: #64748b; font-size: 13px; margin-top: 5px;">Hệ thống Quản lý Thuê phòng Trọ Thông minh</p>
        </div>

        <!-- Greeting -->
        <p style="color: #334155; font-size: 16px; line-height: 1.6;">Xin chào <strong>{{ $tenantName }}</strong>,</p>
        <p style="color: #334155; font-size: 15px; line-height: 1.6;">
            Bạn vừa được làm Hợp đồng thuê trọ tại 
            @if($roomName) <strong>Phòng {{ $roomName }}</strong> @endif
            @if($boardingHouseName) - {{ $boardingHouseName }} @endif.
        </p>

        <!-- Account Info Box -->
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 20px; margin: 25px 0;">
            <h3 style="color: #166534; font-size: 16px; margin-top: 0; margin-bottom: 15px; display: flex; items-center: center;">
                 THÔNG TIN ĐĂNG NHẬP ỨNG DỤNG
            </h3>
            
            <table style="width: 100%; border-collapse: collapse; font-size: 14px; color: #1e293b;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; width: 140px;">Số điện thoại:</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ $phone }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Địa chỉ Email:</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ $email }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b;">Mật khẩu khởi tạo:</td>
                    <td style="padding: 8px 0;">
                        <span style="background-color: #ffffff; color: #047857; font-weight: bold; font-size: 18px; padding: 4px 12px; border-radius: 6px; border: 1px dashed #10b981; display: inline-block;">
                            {{ $password }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Instructions -->
        <div style="background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 15px; margin-bottom: 25px; text-align: left;">
            <p style="color: #92400e; font-size: 13px; margin: 0; line-height: 1.5;">
             <strong>Lưu ý quan trọng:</strong> Vì lý do bảo mật, vui lòng đăng nhập vào ứng dụng và đổi lại mật khẩu cá nhân ngay ở lần đăng nhập đầu tiên.
            </p>
        </div>

        <p style="color: #475569; font-size: 14px; line-height: 1.6;">
            Sau khi đăng nhập, bạn có thể xem chi tiết hợp đồng, theo dõi hóa đơn điện nước hàng tháng và gửi các phản ánh sự cố trực tiếp đến chủ trọ.
        </p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 25px 0;" />
        <p style="color: #94a3b8; font-size: 13px; text-align: center; margin: 0;">
            Email này được gửi tự động từ hệ thống <strong>Ninh Bình HomeStay</strong>. Vui lòng không trả lời trực tiếp email này.
        </p>
    </div>
</body>
</html>
