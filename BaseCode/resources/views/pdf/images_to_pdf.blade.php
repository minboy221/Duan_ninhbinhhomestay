<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Hợp Đồng Điện Tử</title>
    <style>
        @page {
            margin: 0px;
        }
        body {
            margin: 0px;
            padding: 0px;
            background-color: #ffffff;
        }
        .page-break {
            page-break-after: always;
        }
        .img-container {
            width: 100%;
            height: 100vh;
            text-align: center;
            overflow: hidden;
        }
        .img-container img {
            width: 100%;
            height: auto;
            max-height: 100vh;
            object-fit: contain;
        }
    </style>
</head>
<body>
    @foreach($images as $index => $imagePath)
        <div class="img-container">
            <img src="{{ storage_path('app/public/' . $imagePath) }}" alt="Contract Page {{ $index + 1 }}">
        </div>
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
