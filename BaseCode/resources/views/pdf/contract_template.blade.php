<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Hợp Đồng Thuê Nhà</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        h1, h2, h3, h4 {
            text-align: center;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        .signature-table {
            width: 100%;
            margin-top: 50px;
            text-align: center;
        }
        .signature-table td {
            width: 50%;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</h2>
        <h4>Độc lập - Tự do - Hạnh phúc</h4>
        <hr style="width: 30%;">
        <h3>HỢP ĐỒNG THUÊ PHÒNG TRỌ</h3>
    </div>

    <div class="section">
        <p>Hôm nay, ngày {{ now()->format('d') }} tháng {{ now()->format('m') }} năm {{ now()->format('Y') }}, tại địa chỉ: {{ $room->boardingHouse->address ?? '' }}, chúng tôi gồm:</p>
    </div>

    <div class="section">
        <div class="section-title">BÊN CHO THUÊ (BÊN A):</div>
        <p>Họ và tên: <strong>{{ $landlord->name }}</strong></p>
        <p>SĐT: {{ $landlord->phone ?? '..........................' }}</p>
        <p>CCCD/CMND: {{ $landlord->cccd_number ?? '..........................' }}</p>
    </div>

    <div class="section">
        <div class="section-title">BÊN THUÊ (BÊN B):</div>
        <p>Họ và tên: <strong>{{ $tenant->name }}</strong></p>
        <p>SĐT: {{ $tenant->phone ?? '..........................' }}</p>
        <p>CCCD/CMND: {{ $tenant->cccd_number ?? '..........................' }}</p>
    </div>

    <div class="section">
        <div class="section-title">ĐIỀU 1: ĐỐI TƯỢNG VÀ GIÁ CẢ</div>
        <p>Bên A đồng ý cho Bên B thuê phòng số <strong>{{ $room->room_number }}</strong> tại địa chỉ {{ $room->address ?? $room->boardingHouse->address ?? '' }}.</p>
        <p>Giá thuê hàng tháng: <strong>{{ number_format($contract->monthly_rent, 0, ',', '.') }} VNĐ</strong></p>
        <p>Tiền cọc: <strong>{{ number_format($contract->deposit_amount, 0, ',', '.') }} VNĐ</strong></p>
    </div>

    <div class="section">
        <div class="section-title">ĐIỀU 2: THỜI HẠN THUÊ</div>
        <p>Thời gian thuê bắt đầu từ ngày <strong>{{ $contract->start_date->format('d/m/Y') }}</strong> đến ngày <strong>{{ $contract->end_date->format('d/m/Y') }}</strong>.</p>
    </div>

    <div class="section">
        <div class="section-title">ĐIỀU 3: CAM KẾT CHUNG</div>
        <p>Hai bên cam kết thực hiện đúng các điều khoản trong hợp đồng. Nếu có tranh chấp sẽ cùng nhau thương lượng giải quyết.</p>
    </div>

    <table class="signature-table">
        <tr>
            <td>
                <strong>BÊN THUÊ (BÊN B)</strong><br>
                <i>(Ký và ghi rõ họ tên)</i>
                <br><br><br><br><br>
                {{ $tenant->name }}
            </td>
            <td>
                <strong>BÊN CHO THUÊ (BÊN A)</strong><br>
                <i>(Ký và ghi rõ họ tên)</i>
                <br><br><br><br><br>
                {{ $landlord->name }}
            </td>
        </tr>
    </table>
</body>
</html>
