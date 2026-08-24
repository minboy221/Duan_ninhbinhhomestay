<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Xác minh địa chỉ Email</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 6px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="color: #00628c; text-align: center; margin-bottom: 20px;">Ninh Bình HomeStay</h2>
        <p style="color: #333; font-size: 16px; line-height: 1.5;">Xin chào,</p>
        <p style="color: #333; font-size: 16px; line-height: 1.5;">Cảm ơn bạn đã đăng ký tài khoản. Để hoàn tất việc đăng ký, vui lòng sử dụng mã xác minh dưới đây:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <span style="display: inline-block; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #57baf6; background-color: #f0f8ff; padding: 15px 30px; border-radius: 6px; border: 2px dashed #57baf6;">
                {{ $otpCode }}
            </span>
        </div>

        <p style="color: #333; font-size: 16px; line-height: 1.5;">Mã xác minh này có hiệu lực trong vòng <strong>15 phút</strong>. Tuyệt đối không chia sẻ mã này cho bất kỳ ai.</p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;" />
        <p style="color: #888; font-size: 14px; text-align: center;">Nếu bạn không yêu cầu đăng ký, vui lòng bỏ qua email này.</p>
    </div>
</body>
</html>
