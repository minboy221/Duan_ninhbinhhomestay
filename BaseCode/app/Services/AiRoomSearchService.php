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
     * Chuyển đổi chuỗi số tiền tiếng Việt sang số nguyên VND
     */
    private function parseVietnamesePriceAmount(string $str): ?int
    {
        $str = trim(mb_strtolower($str, 'UTF-8'));
        $str = preg_replace('/\s+/', '', $str);

        // Pattern 1: 2tr5 / 2tr500 / 2củ5
        if (preg_match('/^([0-9]+)(?:triệu|trieu|tr|củ|cu)([0-9]+)(?:k)?$/ui', $str, $m)) {
            $main = intval($m[1]);
            $sub = $m[2];
            if (strlen($sub) === 1) {
                return $main * 1000000 + intval($sub) * 100000;
            } elseif (strlen($sub) === 2) {
                return $main * 1000000 + intval($sub) * 10000;
            } elseif (strlen($sub) === 3) {
                return $main * 1000000 + intval($sub) * 1000;
            }
            return $main * 1000000 + intval($sub);
        }

        // Pattern 2: 800k / 800 nghìn / 800 ngàn
        if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)(?:nghìn|ngàn|nghin|ngan|k)$/ui', $str, $m)) {
            $num = floatval(str_replace(',', '.', $m[1]));
            return (int) round($num * 1000);
        }

        // Pattern 3: Dạng số đầy đủ có chấm/phẩy: 3.000.000 / 3,000,000 / 3000000
        if (preg_match('/^([0-9]{1,3}(?:[\.,][0-9]{3})+|[0-9]{6,10})(?:đồng|dong|vnd|d|đ)?$/ui', $str, $m)) {
            $clean = preg_replace('/[\.,]/', '', $m[1]);
            return intval($clean);
        }

        // Pattern 4: 2.5 triệu / 3tr / 3 củ / 3m / 3
        if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)(?:triệu|trieu|tr|củ|cu|đồng|dong|vnd|m|d|đ)?$/ui', $str, $m)) {
            $num = floatval(str_replace(',', '.', $m[1]));
            if ($num >= 100000) {
                return (int) $num;
            }
            if ($num <= 50) {
                return (int) round($num * 1000000);
            }
            if ($num <= 999) {
                return (int) round($num * 1000);
            }
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
        $extractedSegments = [];

        // 1. Phân tích Khoảng giá (Price)
        // 1A. Khoảng giá: từ X đến Y / X - Y triệu / từ 1tr5 đến 3tr
        if (preg_match('/(?:từ|tu|khoảng|tầm)?\s*([0-9]+(?:[\.,][0-9]+)?(?:\s*(?:triệu|trieu|tr|củ|k))?|[0-9]+\s*tr\s*[0-9]+|[0-9]{1,3}(?:[\.,][0-9]{3})+)\s*(?:đến|den|-|tới|toi)\s*([0-9]+(?:[\.,][0-9]+)?\s*(?:triệu|trieu|tr|củ|k|nghìn|ngàn|nghin|ngan|đ|dong|đồng|vnd)?|[0-9]+\s*tr\s*[0-9]+|[0-9]{1,3}(?:[\.,][0-9]{3})+)/ui', $lowerPrompt, $m)) {
            $p1 = $this->parseVietnamesePriceAmount($m[1]);
            $p2 = $this->parseVietnamesePriceAmount($m[2]);
            if ($p1 && $p2) {
                $result['price_min'] = min($p1, $p2);
                $result['price_max'] = max($p1, $p2);
                $explanationParts[] = 'Giá ' . number_format($result['price_min'], 0, ',', '.') . ' đ - ' . number_format($result['price_max'], 0, ',', '.') . ' đ';
                $extractedSegments[] = $m[0];
            }
        }

        // 1B. Giá tối đa (Dưới X / <= X / tối đa X / nhỏ hơn X)
        if (!$result['price_max'] && preg_match('/(?:dưới|duoi|<|nhỏ hơn|nho hon|tối đa|toi da|khong qua|không quá|<=|ít hơn|it hon)\s*([0-9]+(?:[\.,][0-9]+)?\s*(?:triệu|trieu|tr|củ|cu|nghìn|ngàn|nghin|ngan|k|đ|dong|đồng|vnd)?|[0-9]+\s*tr\s*[0-9]+|[0-9]{1,3}(?:[\.,][0-9]{3})+)/ui', $lowerPrompt, $m)) {
            $p = $this->parseVietnamesePriceAmount($m[1]);
            if ($p) {
                $result['price_max'] = $p;
                $explanationParts[] = 'Giá ≤ ' . number_format($p, 0, ',', '.') . ' đ';
                $extractedSegments[] = $m[0];
            }
        }

        // 1C. Giá tối thiểu (Trên X / > X / lớn hơn X / từ X trở lên / tối thiểu X)
        if (!$result['price_min'] && preg_match('/(?:trên|tren|>|lớn hơn|lon hon|tối thiểu|toi thieu|>=)\s*([0-9]+(?:[\.,][0-9]+)?\s*(?:triệu|trieu|tr|củ|cu|nghìn|ngàn|nghin|ngan|k|đ|dong|đồng|vnd)?|[0-9]+\s*tr\s*[0-9]+|[0-9]{1,3}(?:[\.,][0-9]{3})+)/ui', $lowerPrompt, $m)) {
            $p = $this->parseVietnamesePriceAmount($m[1]);
            if ($p) {
                $result['price_min'] = $p;
                $explanationParts[] = 'Giá ≥ ' . number_format($p, 0, ',', '.') . ' đ';
                $extractedSegments[] = $m[0];
            }
        }

        // 1D. Mức giá đơn / giá cụ thể: "giá 3 triệu", "giá 3tr", "3 triệu", "3tr", "tầm 3tr", "khoảng 3 triệu", "phòng 3tr", "3.000.000"
        if (!$result['price_max'] && !$result['price_min']) {
            if (preg_match('/(?:giá|gia|tầm|tam|khoảng|khoang|quanh|mức|muc|phòng|phong|nhà|nha)?\s*(?:là|la)?\s*([0-9]+(?:[\.,][0-9]+)?\s*(?:triệu|trieu|tr|củ|cu|nghìn|ngàn|nghin|ngan|k)|[0-9]+\s*tr\s*[0-9]+|[0-9]{1,3}(?:[\.,][0-9]{3})+)\s*(?:\/tháng|\/thang|đ|vnd|dong|đồng)?/ui', $lowerPrompt, $m)) {
                $p = $this->parseVietnamesePriceAmount($m[1]);
                if ($p && $p >= 100000) {
                    $result['price_max'] = $p;
                    $explanationParts[] = 'Giá ≤ ' . number_format($p, 0, ',', '.') . ' đ';
                    $extractedSegments[] = $m[0];
                }
            }
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
            $extractedSegments[] = $m[0];
        }

        // 3. Phân tích Diện tích (Area size)
        if (preg_match('/(?:dưới|<)\s*([0-9]+)\s*(?:m2|m²|mét vuông|met vuong)/ui', $lowerPrompt, $m)) {
            $result['area_max'] = floatval($m[1]);
            $explanationParts[] = 'Diện tích < ' . $result['area_max'] . 'm²';
            $extractedSegments[] = $m[0];
        } elseif (preg_match('/(?:trên|>)\s*([0-9]+)\s*(?:m2|m²|mét vuông|met vuong)/ui', $lowerPrompt, $m)) {
            $result['area_min'] = floatval($m[1]);
            $explanationParts[] = 'Diện tích > ' . $result['area_min'] . 'm²';
            $extractedSegments[] = $m[0];
        } elseif (preg_match('/([0-9]+)\s*(?:đến|-)\s*([0-9]+)\s*(?:m2|m²)/ui', $lowerPrompt, $m)) {
            $result['area_min'] = floatval($m[1]);
            $result['area_max'] = floatval($m[2]);
            $explanationParts[] = 'Diện tích ' . $result['area_min'] . ' - ' . $result['area_max'] . 'm²';
            $extractedSegments[] = $m[0];
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
                $extractedSegments[] = $cleanAreaName;
                $extractedSegments[] = $areaName;
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
                $extractedSegments[] = $catName;
                break;
            }
        }

        // 6. Phân tích Tiện ích (Amenities matching)
        $matchedAmenityIds = [];
        $matchedAmenityNames = [];
        
        $amenityAliases = [
            'thú cưng' => ['thú cưng', 'thu cung', 'chó mèo', 'cho meo', 'pet', 'nuôi pet', 'nuoi pet', 'nuôi chó', 'nuôi mèo', 'thucung'],
            'gác xép' => ['gác xép', 'gac xep', 'gác lửng', 'gac lung'],
            'điều hòa' => ['điều hòa', 'dieu hoa', 'điều hoà', 'máy lạnh', 'may lanh'],
            'nóng lạnh' => ['nóng lạnh', 'nong lanh', 'bình nóng lạnh', 'nuoc nong'],
            'máy giặt' => ['máy giặt', 'may giat'],
            'tủ lạnh' => ['tủ lạnh', 'tu lanh'],
            'wifi' => ['wifi', 'mạng', 'internet'],
            'ban công' => ['ban công', 'ban cong', 'cửa sổ', 'cua so', 'thoáng mát'],
            'bếp' => ['bếp', 'bep', 'nấu ăn', 'nau an', 'tu bep', 'ke bep'],
            'camera' => ['camera', 'an ninh', 'bảo vệ'],
            'chỗ để xe' => ['chỗ để xe', 'để xe', 'de xe', 'gui xe', 'gửi xe', 'nhà xe', 'bãi xe', 'bai xe'],
        ];

        foreach ($amenities as $amenity) {
            $amId = is_array($amenity) ? ($amenity['id'] ?? null) : ($amenity->id ?? null);
            $amName = is_array($amenity) ? ($amenity['name'] ?? '') : ($amenity->name ?? '');
            if (!$amName) continue;

            $amNorm = $this->removeVietnameseTones(mb_strtolower($amName, 'UTF-8'));
            $isMatched = false;

            if (stripos($normalizedPrompt, $amNorm) !== false || stripos($lowerPrompt, mb_strtolower($amName, 'UTF-8')) !== false) {
                $isMatched = true;
                $extractedSegments[] = $amName;
            } else {
                foreach ($amenityAliases as $key => $aliases) {
                    if (stripos($amNorm, $this->removeVietnameseTones($key)) !== false) {
                        foreach ($aliases as $alias) {
                            $normAlias = $this->removeVietnameseTones($alias);
                            if (mb_strlen($normAlias, 'UTF-8') <= 3) {
                                if (preg_match('/(?:\b|\s|^)' . preg_quote($normAlias, '/') . '(?:\b|\s|$)/iu', $normalizedPrompt)) {
                                    $isMatched = true;
                                    $extractedSegments[] = $alias;
                                    break 2;
                                }
                            } else {
                                if (stripos($normalizedPrompt, $normAlias) !== false) {
                                    $isMatched = true;
                                    $extractedSegments[] = $alias;
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

        // 7. Đặc tính bổ sung ngoài bảng Amenities (vd: Gác xép, Thú cưng)
        if (preg_match('/\b(gác xép|gác lửng|gac xep|gac lung)\b/iu', $lowerPrompt, $featM)) {
            if (!in_array('Gác xép', $result['amenity_names'])) {
                $explanationParts[] = 'Đặc điểm: Gác xép';
                $extractedSegments[] = $featM[0];
            }
        }
        if (preg_match('/\b(thú cưng|chó mèo|nuôi pet|nuoi pet|pet)\b/iu', $lowerPrompt, $featM)) {
            if (!in_array('Cho nuôi thú cưng', $result['amenity_names'])) {
                $explanationParts[] = 'Đặc điểm: Cho nuôi thú cưng';
                $extractedSegments[] = $featM[0];
            }
        }

        // 8. Tinh lọc từ khóa còn lại (Loại bỏ stop words và các cụm từ đã nhận diện)
        $cleanPrompt = $lowerPrompt;
        foreach ($extractedSegments as $seg) {
            $cleanPrompt = preg_replace('/' . preg_quote($seg, '/') . '/ui', ' ', $cleanPrompt);
        }

        // Danh sách từ dừng phổ biến trong câu tìm kiếm phòng trọ
        $stopWords = [
            'phòng trọ', 'phong tro', 'phòng', 'phong', 'nhà trọ', 'nha tro', 'nhà', 'nha',
            'tìm kiếm', 'tim kiem', 'tìm phòng', 'tim phong', 'tìm', 'tim', 'cần tìm', 'can tim', 'cần', 'can',
            'cho thuê', 'cho thue', 'thuê phòng', 'thue phong', 'thuê trọ', 'thue tro', 'thuê', 'thue',
            'ở ghép', 'o ghep', 'ở', 'o', 'tại', 'tai', 'quanh khu', 'quanh', 'khu vực', 'khu vuc', 'gần', 'gan',
            'giá rẻ', 'gia re', 'giá', 'gia', 'rẻ', 're', 'đẹp', 'dep', 'xinh',
            'khoảng', 'khoang', 'tầm', 'tam', 'mức', 'muc', 'dưới', 'duoi', 'trên', 'tren', 'từ', 'tu', 'đến', 'den',
            'có', 'co', 'và', 'va', 'với', 'voi', 'được', 'duoc', 'cho', 'nào', 'nao', 'là', 'la',
            'tháng', 'thang', 'người', 'nguoi', 'sinh viên', 'sinh vien', 'diện tích', 'dien tich',
            'tầng', 'tang', 'lầu', 'lau', 'm2', 'm²', 'triệu', 'trieu', 'tr', 'k', 'củ', 'cu', 'đồng', 'dong', 'đ', 'vnd'
        ];

        // Sắp xếp stop words theo độ dài giảm dần để regex replace các cụm dài trước
        usort($stopWords, fn($a, $b) => mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8'));
        foreach ($stopWords as $sw) {
            $cleanPrompt = preg_replace('/(?<![\p{L}\p{N}])' . preg_quote($sw, '/') . '(?![\p{L}\p{N}])/ui', ' ', $cleanPrompt);
        }

        // Làm sạch dấu câu và khoảng trắng thừa
        $cleanPrompt = trim(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $cleanPrompt));
        $cleanPrompt = trim(preg_replace('/\s+/', ' ', $cleanPrompt));

        if (mb_strlen($cleanPrompt, 'UTF-8') >= 2) {
            $result['keyword'] = $cleanPrompt;
            $explanationParts[] = 'Từ khóa: "' . $cleanPrompt . '"';
        } else {
            $result['keyword'] = null;
        }

        // Tóm tắt kết quả giải thích
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
