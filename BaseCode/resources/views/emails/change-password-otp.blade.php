<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Xác minh Đổi Mật Khẩu</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="color: #00628c; text-align: center; margin-bottom: 20px;">Ninh Bình HomeStay</h2>
        <p style="color: #333; font-size: 16px; line-height: 1.5;">Xin chào,</p>
        <p style="color: #333; font-size: 16px; line-height: 1.5;">Hệ thống ghi nhận bạn đang yêu cầu thay đổi mật khẩu tài khoản. Để bảo vệ an toàn cho bạn, vui lòng nhập mã OTP dưới đây để xác nhận thao tác này:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <span style="display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #57baf6; background-color: #f0f8ff; padding: 15px 30px; border-radius: 8px; border: 2px dashed #57baf6;">
                {{ $otpCode }}
            </span>
        </div>

        <p style="color: #333; font-size: 16px; line-height: 1.5;">Mã xác minh này có hiệu lực trong vòng <strong>15 phút</strong>. Tuyệt đối không chia sẻ mã này cho bất kỳ ai.</p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;" />
        <p style="color: #888; font-size: 14px; text-align: center;">Nếu bạn KHÔNG yêu cầu đổi mật khẩu, vui lòng bỏ qua email này và tiến hành bảo mật tài khoản ngay lập tức.</p>
    </div>
</body>
</html>
