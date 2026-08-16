<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Amenity;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRoomSearchService
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Phân tích câu tìm kiếm bằng ngôn ngữ tự nhiên thành JSON bộ lọc
     */
    public function parseSearchPrompt(string $prompt): array
    {
        $prompt = trim($prompt);
        if (empty($prompt)) {
            return $this->getEmptyFilterResult();
        }

        // Lấy danh mục thực tế từ Database để làm ngữ cảnh chuẩn (Ground Truth)
        $metaData = $this->categoryService->getActiveData();
        $areas = $metaData['areas']->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->toArray();
        $categories = $metaData['types']->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        $amenities = $metaData['amenities']->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->toArray();

        // 1. Thử gọi Gemini AI nếu có cấu hình GEMINI_API_KEY
        $apiKey = env('GEMINI_API_KEY');
        if (!empty($apiKey)) {
            $aiResult = $this->parseWithGemini($prompt, $apiKey, $areas, $categories, $amenities);
            if (!empty($aiResult) && !empty($aiResult['success'])) {
                return $aiResult;
            }
        }

        // 2. Fallback: Dùng bộ phân tích thông minh NLP Regex (Offline 100%, không phụ thuộc API)
        return $this->parseWithRuleBasedFallback($prompt, $areas, $categories, $amenities);
    }

    /**
     * Gọi Google Gemini AI qua REST API với Structured JSON Output
     */
    private function parseWithGemini(string $prompt, string $apiKey, array $areas, array $categories, array $amenities): ?array
    {
        try {
            $cleanKey = trim(trim($apiKey), '"\'');
            $cleanKey = preg_replace('/\s+/', '', $cleanKey);

            $areasJson = json_encode($areas, JSON_UNESCAPED_UNICODE);
            $categoriesJson = json_encode($categories, JSON_UNESCAPED_UNICODE);
            $amenitiesJson = json_encode($amenities, JSON_UNESCAPED_UNICODE);

            $systemInstruction = <<<INSTRUCTION
Bạn là trợ lý AI thông minh chuyên phân tích yêu cầu tìm kiếm phòng trọ tại Ninh Bình.
Nhiệm vụ của bạn: Đọc câu tìm kiếm tự nhiên của khách thuê và bóc tách thành các tham số lọc chuẩn JSON.

DỮ LIỆU THỰC TẾ TRONG HỆ THỐNG:
- Danh sách Khu vực (Areas): {$areasJson}
- Danh sách Loại phòng (Categories): {$categoriesJson}
- Danh sách Tiện ích (Amenities): {$amenitiesJson}

QUY TẮC BÓC TÁCH:
1. "price_min", "price_max": Số tiền VND (ví dụ "2.5 triệu" -> 2500000, "dưới 2tr" -> price_max: 2000000, "từ 1 đến 2 triệu" -> price_min: 1000000, price_max: 2000000). Nếu không nói thì để null.
2. "area_min", "area_max": Diện tích m2 (ví dụ "trên 30m2" -> area_min: 30). Nếu không nói thì để null.
3. "floor_number": Số tầng nguyên (ví dụ "tầng 1" -> 1, "tầng trệt" -> 1, "tầng 2" -> 2). Nếu không nói thì để null.
4. "area_id" & "area_name": Chọn đúng ID và Name trong danh sách Khu vực nếu người dùng nhắc tới hoặc gần giống.
5. "category_id" & "category_name": Chọn đúng ID và Name trong danh sách Loại phòng nếu phù hợp.
6. "amenity_ids" & "amenity_names": Mảng các ID và Tên tiện ích tìm thấy trong danh sách Tiện ích (ví dụ gác xép, thú cưng, wifi, điều hòa...).
7. "keyword": Từ khóa bổ sung nếu có (ví dụ tên đường, địa danh cụ thể).
8. "explanation": Một câu tóm tắt ngắn gọn, lịch sự bằng tiếng Việt giải thích những gì AI đã lọc (VD: "Đã tìm phòng tầng 1 tại Hoa Lư, giá dưới 2.5 triệu có gác xép và cho nuôi thú cưng.").

Trả về DUY NHẤT 1 JSON object theo định dạng:
{
  "keyword": null,
  "area_id": null,
  "area_name": null,
  "price_min": null,
  "price_max": null,
  "area_min": null,
  "area_max": null,
  "floor_number": null,
  "category_id": null,
  "category_name": null,
  "amenity_ids": [],
  "amenity_names": [],
  "explanation": ""
}
INSTRUCTION;

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemInstruction . "\n\nCÂU TÌM KIẾM CỦA KHÁCH THUÊ: \"" . $prompt . "\""]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature' => 0.1,
                ]
            ];

            $models = ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-flash-latest'];

            foreach ($models as $model) {
                try {
                    $response = Http::timeout(10)
                        ->withoutVerifying()
                        ->withHeaders([
                            'x-goog-api-key' => $cleanKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload);

                    if ($response->successful()) {
                        $resData = $response->json();
                        $textJson = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                        if ($textJson) {
                            $parsed = json_decode($textJson, true);
                            if (is_array($parsed)) {
                                $parsed['success'] = true;
                                $parsed['engine'] = 'gemini';
                                $parsed['original_prompt'] = $prompt;
                                return $this->normalizeFilterResult($parsed);
                            }
                        }
                    }
                } catch (\Throwable $mEx) {
                    Log::warning("Gemini Search model {$model} error: " . $mEx->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Log::error("AiRoomSearchService Gemini error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Bộ phân tích Fallback sử dụng Rule-based NLP Regex (Không cần mạng internet, không tốn chi phí)
     */
    public function parseWithRuleBasedFallback(string $prompt, array $areas, array $categories, array $amenities): array
    {
        $lowerPrompt = mb_strtolower($prompt, 'UTF-8');
        $normalizedPrompt = $this->removeVietnameseTones($lowerPrompt);

        $result = $this->getEmptyFilterResult();
        $result['original_prompt'] = $prompt;
        $result['engine'] = 'rule_based';
        $result['success'] = true;

        $explanationParts = [];

        // 1. Phân tích Khoảng giá (Price)
        // Dưới X triệu / <= X tr / dưới X tr5 / Xk
        if (preg_match('/(?:dưới|<|nhỏ hơn|tối đa|tam|tầm)\s*([0-9]+(?:\.[0-9]+)?)\s*(?:tr|trieu|triệu|m)/ui', $lowerPrompt, $m)) {
            $result['price_max'] = (int) (floatval($m[1]) * 1000000);
            $explanationParts[] = 'Giá tối đa ' . number_format($result['price_max'], 0, ',', '.') . ' đ';
        } elseif (preg_match('/([0-9]+)\s*tr\s*([0-9]+)/ui', $lowerPrompt, $m)) { // Ví dụ 2tr5 -> 2.500.000
            $result['price_max'] = (int) ($m[1] * 1000000 + $m[2] * 100000);
            $explanationParts[] = 'Giá khoảng ' . number_format($result['price_max'], 0, ',', '.') . ' đ';
        } elseif (preg_match('/(?:từ|tu)\s*([0-9]+(?:\.[0-9]+)?)\s*(?:đến|den|-|tới|toi)\s*([0-9]+(?:\.[0-9]+)?)\s*(?:tr|trieu|triệu)/ui', $lowerPrompt, $m)) {
            $result['price_min'] = (int) (floatval($m[1]) * 1000000);
            $result['price_max'] = (int) (floatval($m[2]) * 1000000);
            $explanationParts[] = 'Giá từ ' . number_format($result['price_min'], 0, ',', '.') . ' đ - ' . number_format($result['price_max'], 0, ',', '.') . ' đ';
        } elseif (preg_match('/(?:trên|>|lớn hơn)\s*([0-9]+(?:\.[0-9]+)?)\s*(?:tr|trieu|triệu)/ui', $lowerPrompt, $m)) {
            $result['price_min'] = (int) (floatval($m[1]) * 1000000);
            $explanationParts[] = 'Giá trên ' . number_format($result['price_min'], 0, ',', '.') . ' đ';
        }

        // 2. Phân tích Số tầng (Floor)
        if (preg_match('/(?:tầng|tang|lầu|lau)\s*([0-9]+|trệt|tret|gác|gac)/ui', $lowerPrompt, $m)) {
            $floorVal = mb_strtolower($m[1], 'UTF-8');
            if (in_array($floorVal, ['trệt', 'tret', '1'])) {
                $result['floor_number'] = 1;
                $explanationParts[] = 'Tầng 1 (Trệt)';
            } else {
                $result['floor_number'] = intval($floorVal) ?: null;
                if ($result['floor_number']) {
                    $explanationParts[] = 'Tầng ' . $result['floor_number'];
                }
            }
        }

        // 3. Phân tích Diện tích (Area size)
        if (preg_match('/(?:dưới|<)\s*([0-9]+)\s*(?:m2|m²|mét vuông|met vuong)/ui', $lowerPrompt, $m)) {
            $result['area_max'] = floatval($m[1]);
            $explanationParts[] = 'Diện tích < ' . $result['area_max'] . 'm²';
        } elseif (preg_match('/(?:trên|>)\s*([0-9]+)\s*(?:m2|m²|mét vuông|met vuong)/ui', $lowerPrompt, $m)) {
            $result['area_min'] = floatval($m[1]);
            $explanationParts[] = 'Diện tích > ' . $result['area_min'] . 'm²';
        } elseif (preg_match('/([0-9]+)\s*(?:đến|-)\s*([0-9]+)\s*(?:m2|m²)/ui', $lowerPrompt, $m)) {
            $result['area_min'] = floatval($m[1]);
            $result['area_max'] = floatval($m[2]);
            $explanationParts[] = 'Diện tích ' . $result['area_min'] . ' - ' . $result['area_max'] . 'm²';
        }

        // 4. Phân tích Khu vực (Area matching)
        foreach ($areas as $area) {
            $areaId = is_array($area) ? ($area['id'] ?? null) : ($area->id ?? null);
            $areaName = is_array($area) ? ($area['name'] ?? '') : ($area->name ?? '');
            if (!$areaName) continue;

            $cleanAreaName = preg_replace('/^(Phường|Xã|Thị trấn|Huyện|Thành phố)\s+/ui', '', $areaName);
            $normClean = $this->removeVietnameseTones(mb_strtolower($cleanAreaName, 'UTF-8'));
            $normFull = $this->removeVietnameseTones(mb_strtolower($areaName, 'UTF-8'));

            if (
                stripos($normalizedPrompt, $normClean) !== false ||
                stripos($normalizedPrompt, $normFull) !== false ||
                stripos($lowerPrompt, mb_strtolower($cleanAreaName, 'UTF-8')) !== false
            ) {
                $result['area_id'] = $areaId;
                $result['area_name'] = $areaName;
                $explanationParts[] = 'Khu vực ' . $areaName;
                break;
            }
        }

        // 5. Phân tích Loại phòng (Category matching)
        foreach ($categories as $cat) {
            $catId = is_array($cat) ? ($cat['id'] ?? null) : ($cat->id ?? null);
            $catName = is_array($cat) ? ($cat['name'] ?? '') : ($cat->name ?? '');
            if (!$catName) continue;

            $normCat = $this->removeVietnameseTones(mb_strtolower($catName, 'UTF-8'));
            if (stripos($normalizedPrompt, $normCat) !== false || stripos($lowerPrompt, mb_strtolower($catName, 'UTF-8')) !== false) {
                $result['category_id'] = $catId;
                $result['category_name'] = $catName;
                $explanationParts[] = 'Loại ' . $catName;
                break;
            }
        }

        // 6. Phân tích Tiện ích (Amenities matching)
        $matchedAmenityIds = [];
        $matchedAmenityNames = [];
        
        $amenityAliases = [
            'thú cưng' => ['thú cưng', 'chó mèo', 'pet', 'nuôi pet', 'nuôi chó', 'nuôi mèo', 'thucung'],
            'gác xép' => ['gác xép', 'gác lửng', 'gac xep', 'gac lung'],
            'điều hòa' => ['điều hòa', 'điều hoà', 'dieu hoa', 'máy lạnh', 'may lanh'],
            'nóng lạnh' => ['nóng lạnh', 'nong lanh', 'bình nóng lạnh', 'nuoc nong'],
            'máy giặt' => ['máy giặt', 'may giat'],
            'tủ lạnh' => ['tủ lạnh', 'tu lanh'],
            'wifi' => ['wifi', 'mạng', 'internet'],
            'ban công' => ['ban công', 'ban cong', 'cửa sổ', 'cua so', 'thoáng mát'],
            'bếp' => ['bếp', 'nấu ăn', 'tu bep', 'ke bep', 'nau an'],
            'camera' => ['camera', 'an ninh', 'bảo vệ'],
            'chỗ để xe' => ['chỗ để xe', 'để xe', 'de xe', 'gui xe', 'nhà xe', 'bãi xe', 'bai xe'],
        ];

        foreach ($amenities as $amenity) {
            $amId = is_array($amenity) ? ($amenity['id'] ?? null) : ($amenity->id ?? null);
            $amName = is_array($amenity) ? ($amenity['name'] ?? '') : ($amenity->name ?? '');
            if (!$amName) continue;

            $amNorm = $this->removeVietnameseTones(mb_strtolower($amName, 'UTF-8'));
            $isMatched = false;

            if (stripos($normalizedPrompt, $amNorm) !== false || stripos($lowerPrompt, mb_strtolower($amName, 'UTF-8')) !== false) {
                $isMatched = true;
            } else {
                foreach ($amenityAliases as $key => $aliases) {
                    if (stripos($amNorm, $this->removeVietnameseTones($key)) !== false) {
                        foreach ($aliases as $alias) {
                            $normAlias = $this->removeVietnameseTones($alias);
                            if (mb_strlen($normAlias, 'UTF-8') <= 3) {
                                if (preg_match('/(?:\b|\s|^)' . preg_quote($normAlias, '/') . '(?:\b|\s|$)/iu', $normalizedPrompt)) {
                                    $isMatched = true;
                                    break 2;
                                }
                            } else {
                                if (stripos($normalizedPrompt, $normAlias) !== false) {
                                    $isMatched = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            if ($isMatched && $amId) {
                $matchedAmenityIds[] = $amId;
                $matchedAmenityNames[] = $amName;
            }
        }

        $result['amenity_ids'] = array_values(array_unique($matchedAmenityIds));
        $result['amenity_names'] = array_values(array_unique($matchedAmenityNames));
        if (!empty($result['amenity_names'])) {
            $explanationParts[] = 'Có: ' . implode(', ', $result['amenity_names']);
        }

        // 7. Trích xuất địa điểm tự do (nếu chưa khớp Area DB)
        if (empty($result['area_id'])) {
            if (preg_match('/(?:quanh khu|khu vực|gần|tại|ở)\s+([A-Za-zÀ-ỹ0-9\s]+?)(?:,|$|\.|\bdưới\b|\btrên\b|\btừ\b|\bcó\b|\bcho\b|\bgiá\b)/iu', $prompt, $locMatch)) {
                $locWord = trim($locMatch[1]);
                if (mb_strlen($locWord, 'UTF-8') >= 2) {
                    $result['keyword'] = $locWord;
                    $explanationParts[] = 'Khu vực tự do: ' . $locWord;
                }
            }
        }

        // 8. Trích xuất đặc tính bổ sung nếu chưa có trong bảng Amenities (vd: Gác xép, Thú cưng)
        $extraFeatures = [];
        if (preg_match('/\b(gác xép|gác lửng|gac xep|gac lung)\b/iu', $prompt)) {
            $extraFeatures[] = 'Gác xép';
        }
        if (preg_match('/\b(thú cưng|chó mèo|nuôi pet|pet)\b/iu', $prompt)) {
            $extraFeatures[] = 'Cho nuôi thú cưng';
        }
        if (!empty($extraFeatures)) {
            $explanationParts[] = 'Đặc điểm: ' . implode(', ', $extraFeatures);
            if (empty($result['keyword'])) {
                $result['keyword'] = implode(' ', $extraFeatures);
            }
        }

        // Tóm tắt kết quả
        if (!empty($explanationParts)) {
            $result['explanation'] = 'Đã lọc theo: ' . implode(' • ', $explanationParts);
        } else {
            $result['keyword'] = $prompt;
            $result['explanation'] = 'Tìm kiếm phòng theo từ khóa: "' . $prompt . '"';
        }

        return $result;
    }

    /**
     * Chuẩn hóa kết quả filter
     */
    private function normalizeFilterResult(array $data): array
    {
        return [
            'success' => true,
            'engine' => $data['engine'] ?? 'gemini',
            'original_prompt' => $data['original_prompt'] ?? '',
            'keyword' => !empty($data['keyword']) ? (string)$data['keyword'] : null,
            'area_id' => !empty($data['area_id']) ? (int)$data['area_id'] : null,
            'area_name' => !empty($data['area_name']) ? (string)$data['area_name'] : null,
            'price_min' => !empty($data['price_min']) ? (int)$data['price_min'] : null,
            'price_max' => !empty($data['price_max']) ? (int)$data['price_max'] : null,
            'area_min' => !empty($data['area_min']) ? (float)$data['area_min'] : null,
            'area_max' => !empty($data['area_max']) ? (float)$data['area_max'] : null,
            'floor_number' => !empty($data['floor_number']) ? (int)$data['floor_number'] : null,
            'category_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
            'category_name' => !empty($data['category_name']) ? (string)$data['category_name'] : null,
            'amenity_ids' => is_array($data['amenity_ids'] ?? null) ? array_map('intval', $data['amenity_ids']) : [],
            'amenity_names' => is_array($data['amenity_names'] ?? null) ? $data['amenity_names'] : [],
            'explanation' => !empty($data['explanation']) ? (string)$data['explanation'] : 'Đã phân tích yêu cầu tìm kiếm.',
        ];
    }

    /**
     * Cấu trúc rỗng ban đầu
     */
    private function getEmptyFilterResult(): array
    {
        return [
            'success' => false,
            'engine' => null,
            'original_prompt' => '',
            'keyword' => null,
            'area_id' => null,
            'area_name' => null,
            'price_min' => null,
            'price_max' => null,
            'area_min' => null,
            'area_max' => null,
            'floor_number' => null,
            'category_id' => null,
            'category_name' => null,
            'amenity_ids' => [],
            'amenity_names' => [],
            'explanation' => '',
        ];
    }

    /**
     * Bỏ dấu tiếng Việt phục vụ so sánh không dấu
     */
    private function removeVietnameseTones(string $str): string
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/u", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/u", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/u", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/u", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/u", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/u", 'y', $str);
        $str = preg_replace("/(đ)/u", 'd', $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/u", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/u", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/u", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/u", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/u", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/u", 'Y', $str);
        $str = preg_replace("/(Đ)/u", 'D', $str);
        return $str;
    }
}
