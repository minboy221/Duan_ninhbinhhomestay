<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ContractOcrService
{
    /**
     * Phân tích và kiểm tra tính hợp lệ của danh sách ảnh hợp đồng được tải lên khi kích hoạt hợp đồng.
     */
    public static function validateContractImages(array $imagePaths, array $expectedData = []): array
    {
        if (empty($imagePaths)) {
            return [
                'is_valid' => false,
                'reason' => 'Không tìm thấy tệp ảnh hợp đồng nào được tải lên.'
            ];
        }

        foreach ($imagePaths as $relativePath) {
            $fullPath = storage_path('app/public/' . ltrim($relativePath, '/'));
            if (!file_exists($fullPath)) {
                $fullPath = public_path('storage/' . ltrim($relativePath, '/'));
            }

            if (!file_exists($fullPath)) {
                continue;
            }

            $checkResult = self::inspectImageContent($fullPath, $expectedData);
            if (!$checkResult['is_valid']) {
                return $checkResult;
            }
        }

        return [
            'is_valid' => true,
            'reason' => null
        ];
    }

    /**
     * Phân tích nội dung chi tiết của một trang hợp đồng qua PaddleOCR Engine
     */
    private static function inspectImageContent(string $filePath, array $expectedData): array
    {
        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return [
                'is_valid' => false,
                'reason' => 'Tệp ảnh không hợp lệ hoặc bị hỏng.'
            ];
        }

        [$width, $height] = $imageInfo;
        if ($width < 300 || $height < 300) {
            return [
                'is_valid' => false,
                'reason' => 'Ảnh hợp đồng có độ phân giải quá thấp, không thể đọc rõ thông tin.'
            ];
        }

        // Trích xuất các trường bằng PaddleOCR
        $extracted = self::extractContractFields($filePath);
        if (!empty($extracted['is_blank'])) {
            return [
                'is_valid' => false,
                'reason' => 'Hợp đồng không hợp lệ! Ảnh hợp đồng tải lên là mẫu hợp đồng in chưa được điền thông tin (Bên B, CCCD, Giá thuê, Thời hạn) hoặc thiếu chữ ký của các bên.'
            ];
        }

        return [
            'is_valid' => true,
            'reason' => null
        ];
    }

    /**
     * Tìm đường dẫn executable của Python trên hệ thống
     */
    private static function findPythonExecutable(): ?string
    {
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $possibleBins = [
            $isWin ? 'python' : 'python3',
            'C:\\Python310\\python.exe',
            'C:\\Python311\\python.exe',
            'C:\\Python312\\python.exe',
            'C:\\Python39\\python.exe',
            'C:\\Users\\Admin\\AppData\\Local\\Programs\\Python\\Python310\\python.exe',
            'C:\\Users\\Admin\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
            'C:\\Users\\Admin\\AppData\\Local\\Programs\\Python\\Python312\\python.exe',
        ];

        foreach ($possibleBins as $bin) {
            if (in_array($bin, ['python', 'python3'])) {
                $checkCmd = $isWin ? "where {$bin} 2>NUL" : "which {$bin} 2>/dev/null";
                $out = @shell_exec($checkCmd);
                if ($out && strlen(trim($out)) > 0) {
                    return $bin;
                }
            } else {
                if (file_exists($bin)) {
                    return '"' . $bin . '"';
                }
            }
        }

        return $isWin ? 'python' : 'python3';
    }

    /**
     * Trích xuất các trường thông tin hợp đồng từ file ảnh chụp/scan sử dụng PaddleOCR Engine
     */
    public static function extractContractFields(string $filePath): array
    {
        $defaultExtracted = [
            'landlord_name' => '',
            'landlord_cccd' => '',
            'landlord_phone' => '',
            'landlord_address' => '',
            'tenant_name' => '',
            'tenant_cccd' => '',
            'tenant_phone' => '',
            'tenant_dob' => '',
            'tenant_address' => '',
            'start_date' => '',
            'end_date' => '',
            'monthly_rent' => null,
            'deposit_amount' => null,
            'room_number' => '',
            'raw_text' => '',
            'success' => true,
            'has_data' => false,
            'is_blank' => false,
            'message' => 'Hệ thống PaddleOCR đã ghi nhận tệp hợp đồng.'
        ];

        if (!file_exists($filePath)) {
            $defaultExtracted['success'] = false;
            $defaultExtracted['is_blank'] = true;
            $defaultExtracted['message'] = 'Không tìm thấy tệp ảnh hợp đồng.';
            return $defaultExtracted;
        }

        try {
            $imageInfo = @getimagesize($filePath);
            if (!$imageInfo) {
                $defaultExtracted['success'] = false;
                $defaultExtracted['is_blank'] = true;
                $defaultExtracted['message'] = 'Tệp tải lên không phải là ảnh hợp lệ.';
                return $defaultExtracted;
            }

            $pythonBin = self::findPythonExecutable();
            $pythonScript = base_path('scripts/paddle_ocr_extract.py');

            if ($pythonBin && file_exists($pythonScript) && function_exists('shell_exec')) {
                $cmd = escapeshellcmd("{$pythonBin} {$pythonScript}") . " " . escapeshellarg($filePath);
                $output = @shell_exec($cmd);
                if ($output) {
                    $json = @json_decode($output, true);
                    if (is_array($json)) {
                        return array_merge($defaultExtracted, $json);
                    }
                }
            }

            $defaultExtracted['message'] = 'Hệ thống đã nhận tệp ảnh hợp đồng. Vui lòng đối soát dữ liệu ở Bước 3.';
            return $defaultExtracted;

        } catch (\Throwable $e) {
            Log::error("Contract PaddleOCR Extraction Error: " . $e->getMessage());
            return $defaultExtracted;
        }
    }
}
