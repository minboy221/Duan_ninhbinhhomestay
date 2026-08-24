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

        // Tự động giải nghĩa & mở rộng các từ viết tắt / tiếng lóng tìm phòng
        $expandedPrompt = $this->expandVietnameseAbbreviations($prompt);

        // Lấy danh mục thực tế từ Database để làm ngữ cảnh chuẩn (Ground Truth)
        $metaData = $this->categoryService->getActiveData();
        $areas = $metaData['areas']->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->toArray();
        $categories = $metaData['types']->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray();
        $amenities = $metaData['amenities']->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->toArray();

        // 1. Thử gọi Gemini AI nếu có cấu hình GEMINI_API_KEY
        $apiKey = env('GEMINI_API_KEY');
        if (!empty($apiKey)) {
            $aiResult = $this->parseWithGemini($expandedPrompt, $apiKey, $areas, $categories, $amenities);
            if (!empty($aiResult) && !empty($aiResult['success'])) {
                $aiResult['original_prompt'] = $prompt;
                return $aiResult;
            }
        }

        // 2. Fallback: Dùng bộ phân tích thông minh NLP Regex (Offline 100%, không phụ thuộc API)
        $fallbackResult = $this->parseWithRuleBasedFallback($expandedPrompt, $areas, $categories, $amenities);
        $fallbackResult['original_prompt'] = $prompt;
        return $fallbackResult;
    }

    /**
     * Mở rộng từ viết tắt, tiếng lóng và ký hiệu thường gặp trong tìm phòng trọ tại Việt Nam / Ninh Bình
     */
    public function expandVietnameseAbbreviations(string $prompt): string
    {
        $text = ' ' . trim($prompt) . ' ';

        // 1. Ký hiệu so sánh giá & diện tích: < 2tr -> dưới 2tr, > 3tr -> trên 3tr, <=, >=
        $text = preg_replace('/<=\s*/u', ' dưới ', $text);
        $text = preg_replace('/>=\s*/u', ' trên ', $text);
        $text = preg_replace('/<\s*([0-9])/u', ' dưới $1', $text);
        $text = preg_replace('/>\s*([0-9])/u', ' trên $1', $text);

        // 2. Viết tắt số tầng: t1, t2, t3, t4, t5, t.1, t.2 -> tầng 1, tầng 2...
        $text = preg_replace('/\bt\.?\s*([1-9])\b/ui', ' tầng $1 ', $text);
        $text = preg_replace('/\b(?:tang\s*trệt|trệt|tret)\b/ui', ' tầng 1 ', $text);

        // 3. Viết tắt giá tiền: 2củ5 -> 2.5 triệu, 2củ -> 2 triệu, 1tr5 -> 1.5 triệu, 1500k -> 1.5 triệu
        $text = preg_replace('/\b([0-9]+)\s*củ\s*([1-9])\b/ui', '$1.$2 triệu', $text);
        $text = preg_replace('/\b([0-9]+(?:\.[0-9]+)?)\s*củ\b/ui', '$1 triệu', $text);
        $text = preg_replace('/\b([0-9]+)\s*tr\s*([1-9])\b/ui', '$1.$2 triệu', $text);
        $text = preg_replace('/\b([0-9]+(?:\.[0-9]+)?)\s*tr\b/ui', '$1 triệu', $text);
        $text = preg_replace('/\b([0-9]{3,4})\s*k\b/ui', '$1 nghìn', $text);
        $text = preg_replace('/\b([1-9])\s*k\b/ui', '$1 nghìn', $text);
        $text = preg_replace('/\b(?:giá\s*sv|gia\s*sv|sv)\b/ui', ' sinh viên giá rẻ ', $text);

        // Cụm từ chỉ ngưỡng giá trên (từ X trở lên / X đổ lên / X trở đi) -> trên X
        $text = preg_replace('/\b(?:từ|tu)?\s*([0-9]+(?:\.[0-9]+)?\s*(?:triệu|trieu|tr|củ|cu|k|nghìn|ngàn|vnd|đ|đồng)?)\s*(?:trở lên|tro len|đổ lên|do len|trở đi|tro di|hất lên|hat len)\b/ui', ' trên $1 ', $text);

        // Cụm từ chỉ ngưỡng giá dưới (X trở xuống / X đổ lại / X quay đầu) -> dưới X
        $text = preg_replace('/\b([0-9]+(?:\.[0-9]+)?\s*(?:triệu|trieu|tr|củ|cu|k|nghìn|ngàn|vnd|đ|đồng)?)\s*(?:trở xuống|tro xuong|đổ lại|do lai|quay đầu|quay dau|trở lại|tro lai|đổ về|do ve)\b/ui', ' dưới $1 ', $text);

        // 4. Viết tắt địa danh cụ thể & trường học/bệnh viện/KCN (xử lý TRƯỚC từ viết tắt đơn lẻ):
        $text = preg_replace('/\b(?:đh\s*hoa\s*lư|dh\s*hoa\s*lu|dhhl|đhhl|dhhlu)\b/ui', ' Đại học Hoa Lư ', $text);
        $text = preg_replace('/\b(?:kcn\s*gián\s*khẩu|kcn\s*gian\s*khau)\b/ui', ' Khu công nghiệp Gián Khẩu ', $text);
        $text = preg_replace('/\b(?:kcn\s*khánh\s*phú|kcn\s*khanh\s*phu)\b/ui', ' Khu công nghiệp Khánh Phú ', $text);
        $text = preg_replace('/\b(?:kcn\s*phúc\s*sơn|kcn\s*phuc\s*son)\b/ui', ' Khu công nghiệp Phúc Sơn ', $text);
        $text = preg_replace('/\b(?:kcn\s*tam\s*điệp|kcn\s*tam\s*diep)\b/ui', ' Khu công nghiệp Tam Điệp ', $text);
        $text = preg_replace('/\bkcn\b/ui', ' Khu công nghiệp ', $text);
        $text = preg_replace('/\b(?:tp\s*ninh\s*bình|tp\s*nb)\b/ui', ' Ninh Bình ', $text);
        $text = preg_replace('/\bhl\b/ui', ' Hoa Lư ', $text);
        $text = preg_replace('/\btd\b/ui', ' Tam Điệp ', $text);
        $text = preg_replace('/\b(?:bv\s*đa\s*khoa|bv\s*da\s*khoa)\b/ui', ' Bệnh viện Đa khoa ', $text);
        $text = preg_replace('/\b(?:bv\s*sản\s*nhi|bv\s*san\s*nhi)\b/ui', ' Bệnh viện Sản Nhi ', $text);
        $text = preg_replace('/\bbv\b/ui', ' Bệnh viện ', $text);
        $text = preg_replace('/\btt\b/ui', ' Thị trấn ', $text);
        $text = preg_replace('/\bp\.\s*([a-zA-Z\p{L}]+)/ui', ' Phường $1 ', $text);
        $text = preg_replace('/\bx\.\s*([a-zA-Z\p{L}]+)/ui', ' Xã $1 ', $text);
        $text = preg_replace('/\bh\.\s*([a-zA-Z\p{L}]+)/ui', ' Huyện $1 ', $text);

        // 5. Viết tắt loại phòng & phòng ở:
        $text = preg_replace('/\b(?:tìm|tim)\s+p\b/ui', ' tìm phòng ', $text);
        $text = preg_replace('/\b(?:thuê|thue)\s+p\b/ui', ' thuê phòng ', $text);
        $text = preg_replace('/\b(?:cần|can)\s+p\b/ui', ' cần phòng ', $text);
        $text = preg_replace('/\bp\s*([0-9]+)\b/ui', ' phòng $1 ', $text);
        $text = preg_replace('/\bp\s+(?:trọ|tro)\b/ui', ' phòng trọ ', $text);
        $text = preg_replace('/\bp\s+(?:đôi|doi)\b/ui', ' phòng đôi ', $text);
        $text = preg_replace('/\bp\s+(?:đơn|don)\b/ui', ' phòng đơn ', $text);
        $text = preg_replace('/\bp\s+(?:khép\s*kín|khep\s*kin|kk)\b/ui', ' phòng khép kín ', $text);
        $text = preg_replace('/\bp\s+([0-9]+)\s*(?:ng|người|nguoi)\b/ui', ' phòng $1 người ', $text);
        $text = preg_replace('/\bphg\b/ui', ' phòng ', $text);
        $text = preg_replace('/\bktx\b/ui', ' ký túc xá ', $text);
        $text = preg_replace('/\bccmn\b/ui', ' chung cư mini ', $text);
        $text = preg_replace('/\bcc\b/ui', ' chung cư ', $text);
        $text = preg_replace('/\bhs\b/ui', ' homestay ', $text);
        $text = preg_replace('/\b(?:oghep|o\s*ghep)\b/ui', ' ở ghép ', $text);
        $text = preg_replace('/\bkk\b/ui', ' khép kín ', $text);

        // 6. Viết tắt tiện ích:
        $text = preg_replace('/\b(?:cho\s*nuôi\s*pet|nuôi\s*pet|nuoi\s*pet|cho\s*nuoi\s*pet|nuôi\s*chó|nuoi\s*cho|nuôi\s*mèo|nuoi\s*meo|pet)\b/ui', ' cho nuôi thú cưng ', $text);
        $text = preg_replace('/\b(?:đh|dh|máy\s*lạnh|may\s*lanh)\b/ui', ' điều hòa ', $text);
        $text = preg_replace('/\b(?:nl|bình\s*nl|binh\s*nl)\b/ui', ' nóng lạnh ', $text);
        $text = preg_replace('/\b(?:gx|gác|gac|gác\s*lửng|gac\s*lung)\b/ui', ' gác xép ', $text);
        $text = preg_replace('/\b(?:mg|may\s*giat)\b/ui', ' máy giặt ', $text);
        $text = preg_replace('/\b(?:tl|tu\s*lanh)\b/ui', ' tủ lạnh ', $text);
        $text = preg_replace('/\b(?:wf|mạng\s*net|wifi\s*net)\b/ui', ' wifi ', $text);
        $text = preg_replace('/\b(?:bc|ban\s*cong)\b/ui', ' ban công ', $text);
        $text = preg_replace('/\b(?:tm|thang\s*may)\b/ui', ' thang máy ', $text);
        $text = preg_replace('/\b(?:full\s*đồ|full\s*do|full\s*nt|full\s*nội\s*thất|full\s*noi\s*that)\b/ui', ' đầy đủ nội thất ', $text);

        // 7. Đại từ & liên từ chat thông dụng:
        $text = preg_replace('/\b(?:dc|đc)\b/ui', ' được ', $text);
        $text = preg_replace('/\b(?:ko|k|hông|hok)\b/ui', ' không ', $text);
        $text = preg_replace('/\b(?:vs)\b/ui', ' với ', $text);
        $text = preg_replace('/\b(?:ng)\b/ui', ' người ', $text);
        $text = preg_replace('/\b(?:mn)\b/ui', ' mọi người ', $text);
        $text = preg_replace('/\b(?:sdt)\b/ui', ' số điện thoại ', $text);
        $text = preg_replace('/\b(?:tam|tầm)\s+/ui', ' khoảng ', $text);

        // Chuẩn hóa khoảng trắng
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
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
Bạn là Trợ lý AI chuyên gia tư vấn và tìm kiếm phòng trọ tại Ninh Bình (Ninh Bình HomeStay).
Nhiệm vụ của bạn:
1. Đọc và phân tích kỹ câu của khách thuê, hiểu rõ các từ viết tắt và tiếng lóng thường gặp (VD: "t1 hl < 2tr5" nghĩa là tầng 1 tại Hoa Lư giá dưới 2.5 triệu, "đh nl gx" nghĩa là điều hòa, nóng lạnh, gác xép, "p đôi" là phòng đôi, "tp nb" là Thành phố Ninh Bình...).
2. XÁC ĐỊNH MỤC ĐÍCH TÌM PHÒNG (is_related_to_room_search):
   - Đặt "is_related_to_room_search": true nếu người dùng đang tìm kiếm, hỏi đáp, tư vấn về phòng trọ, homestay, nhà trọ, căn hộ, phòng ở ghép, thuê phòng, mức giá, vị trí khu vực, tiện ích (điều hòa, gác xép, thú cưng...), số tầng, diện tích hoặc các câu chào hỏi/giao tiếp liên quan đến tìm phòng tại Ninh Bình.
   - Đặt "is_related_to_room_search": false nếu câu nói/câu hỏi:
     + Yêu cầu THAO TÁC / THAY ĐỔI / XÓA DỮ LIỆU HỆ THỐNG (ví dụ: "xóa phòng 102 khỏi hệ thống", "sửa giá phòng", "cập nhật thông tin", "xóa bài đăng", "hủy hợp đồng", "thêm phòng mới", "đổi mật khẩu", "drop table", "delete from"...).
     + Hỏi về HỆ THỐNG / MÃ NGUỒN / CÔNG NGHỆ / WEBSITE / LẬP TRÌNH (ví dụ: "code mà hệ thống sử dụng là gì", "website này viết bằng gì", "ai tạo ra website này", "source code", "công nghệ sử dụng"...).
     + Hoặc hoàn toàn KHÔNG liên quan đến tìm phòng trọ (thời tiết, chính trị, danh nhân, lập trình code, toán học, ẩm thực, đố vui, dịch thuật, thơ ca, kiến thức đời sống tổng quát...).
   - Khi "is_related_to_room_search" là false, đặt "refusal_message": "Tôi không thể trả lời câu hỏi này. Trợ lý AI chỉ hỗ trợ tìm kiếm và tư vấn thông tin phòng trọ, không hỗ trợ thao tác ảnh hưởng đến website hoặc trả lời các câu hỏi không liên quan đến gợi ý và tìm kiếm phòng.", và để tất cả các trường lọc khác là null hoặc rỗng.

DỮ LIỆU THỰC TẾ TRONG HỆ THỐNG:
- Danh sách Khu vực hành chính (Areas): {$areasJson}
- Danh sách Loại phòng (Categories): {$categoriesJson}
- Danh sách Tiện ích (Amenities): {$amenitiesJson}

QUY TẮC BÓC TÁCH CHÍNH XÁC (khi is_related_to_room_search là true):
1. "area_id" & "area_name": Đọc kỹ từng từ trong câu của người dùng, tìm xem có nhắc đến Tỉnh, Huyện, Phường, Xã, Khu công nghiệp hoặc Thị trấn nào trong danh sách Areas không (kể cả người dùng gõ tắt, viết hoa/thường, không dấu). Khớp đúng ID và Name tương ứng.
2. "keyword": Bóc tách tên đường phố, địa danh du lịch, trường học, bệnh viện hoặc khu vực cụ thể khác nếu người dùng có nhắc tới (ví dụ: "đường Lê Hồng Phong", "Lê Thái Tổ", "đại học Hoa Lư", "Tam Chúc", "chợ Rồng"...). TUYỆT ĐỐI KHÔNG để các từ chung chung như "phòng", "phường", "xã", "giá rẻ", "tìm", "thuê" làm keyword.
3. "price_min", "price_max": Số tiền VND chính xác:
   - Khi tìm giá tối thiểu / trên ngưỡng ("3tr trở lên", "trên 3tr", "từ 3 triệu", "tối thiểu 3tr", "hơn 3tr", ">= 3tr") -> "price_min": 3000000, "price_max": null.
   - Khi tìm giá tối đa / dưới ngưỡng ("dưới 3tr", "3tr trở xuống", "3tr đổ lại", "tối đa 3tr", "<= 3tr") -> "price_max": 3000000, "price_min": null.
   - Khi tìm khoảng giá ("từ 2tr đến 3tr", "2tr - 3tr") -> "price_min": 2000000, "price_max": 3000000.
   - Khi nói mức giá cụ thể ("giá 3tr", "phòng 3 triệu") -> "price_max": 3000000, "price_min": null. Nếu không nhắc giá thì để cả 2 là null.
4. "is_budget_friendly": true nếu người dùng tìm phòng "giá rẻ", "sinh viên", "giá sinh viên", "tiết kiệm", "bình dân", "giá thấp", "rẻ nhất"... ngược lại để false.
5. "area_min", "area_max": Diện tích m2 (ví dụ "trên 30m2" -> area_min: 30). Nếu không nói thì để null.
6. "floor_number": Số tầng nguyên (ví dụ "tầng 1" -> 1, "tầng trệt" -> 1, "tầng 2" -> 2). Nếu không nói thì để null.
7. "category_id" & "category_name": Chọn đúng ID và Name trong danh sách Loại phòng nếu có.
8. "amenity_ids" & "amenity_names": Mảng các ID và Tên tiện ích tìm thấy trong danh sách Tiện ích (ví dụ gác xép, thú cưng, wifi, điều hòa, nóng lạnh, máy giặt...).
9. "explanation": Một câu tóm tắt ngắn gọn, thân thiện bằng tiếng Việt giải thích những gì AI đã lọc (VD: "Đã tìm phòng tầng 1 tại Xã Bình Mỹ, ưu tiên giá rẻ nhất có gác xép.").

Trả về DUY NHẤT 1 JSON object theo định dạng:
{
  "is_related_to_room_search": true,
  "refusal_message": null,
  "keyword": null,
  "area_id": null,
  "area_name": null,
  "price_min": null,
  "price_max": null,
  "is_budget_friendly": false,
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

        // 0A. Kiểm tra Yêu cầu Thao tác Dữ liệu / Chỉnh sửa / Xóa / Quản trị hệ thống (Data Mutation & Admin Actions)
        $dataMutationPatterns = [
            '/(?:xóa|xoa|delete|remove|gỡ|go|hủy|huy|drop|truncate|hủy bỏ|huy bo)\s+(?:phòng|phong|trọ|tro|bài|bai|tin|hợp đồng|hop dong|tài khoản|tai khoan|người|nguoi|dữ liệu|du lieu|cơ sở|co so|khỏi hệ thống|khoi he thong|khỏi database|khoi database|khỏi web|khoi web|hệ thống|he thong)/ui',
            '/(?:xóa|xoa|delete|remove|gỡ|go|hủy|huy)\s+(?:phòng\s*)?[0-9a-zA-Z\._\-]+/ui',
            '/(?:thay đổi|thay doi|chỉnh sửa|chinh sua|sửa|sua|cập nhật|cap nhat|update|edit|modify|alter)\s+(?:giá|gia|phòng|phong|trọ|tro|thông tin|thong tin|trạng thái|trang thai|hợp đồng|hop dong|mật khẩu|mat khau|dữ liệu|du lieu)/ui',
            '/(?:thay đổi|thay doi|chỉnh sửa|chinh sua|sửa|sua|cập nhật|cap nhat|update|edit)\s+(?:phòng\s*)?[0-9a-zA-Z\._\-]+/ui',
            '/(?:thêm|them|tạo|tao|create|insert|add|nhập|nhap)\s+(?:phòng mới|phong moi|cơ sở mới|co so moi|hợp đồng mới|hop dong moi|dữ liệu|du lieu|bài đăng mới|bai dang moi)/ui',
            '/(?:đổi mật khẩu|doi mat khau|phân quyền|phan quyen|cấp quyền|cap quyen|khóa tài khoản|khoa tai khoan|mở khóa|mo khoa)/ui',
            '/(?:drop\s+table|drop\s+database|truncate\s+table|delete\s+from|insert\s+into|update\s+[a-z_]+\s+set)/ui'
        ];

        foreach ($dataMutationPatterns as $dmp) {
            if (preg_match($dmp, $lowerPrompt)) {
                $result['is_related_to_room_search'] = false;
                $result['refusal_message'] = 'Tôi không thể trả lời câu hỏi này. Trợ lý AI chỉ hỗ trợ tìm kiếm và tư vấn thông tin phòng trọ, không hỗ trợ thao tác ảnh hưởng đến website hoặc trả lời các câu hỏi không liên quan đến gợi ý và tìm kiếm phòng.';
                $result['explanation'] = 'Tôi không thể trả lời câu hỏi này.';
                return $result;
            }
        }

        if (preg_match('/^(?:xóa|xoa|hủy|huy|gỡ|go|sửa|sua|thay đổi|thay doi|chỉnh sửa|chinh sua|cập nhật|cap nhat|update|delete|drop|insert)\b/ui', trim($lowerPrompt))) {
            $result['is_related_to_room_search'] = false;
            $result['refusal_message'] = 'Tôi không thể trả lời câu hỏi này. Trợ lý AI chỉ hỗ trợ tìm kiếm và tư vấn thông tin phòng trọ, không hỗ trợ thao tác ảnh hưởng đến website hoặc trả lời các câu hỏi không liên quan đến gợi ý và tìm kiếm phòng.';
            $result['explanation'] = 'Tôi không thể trả lời câu hỏi này.';
            return $result;
        }

        // 0B. Kiểm tra Ý định (Intent Check) - Phát hiện các câu hỏi không liên quan đến tìm phòng / thuê trọ
        $isOffTopic = false;

        $offTopicKeywords = [
            'code', 'mã nguồn', 'ma nguon', 'source code', 'hệ thống', 'he thong', 'website', 'web này', 'trang web', 'công nghệ', 'cong nghe', 'framework', 'ngôn ngữ lập trình', 'ngon ngu lap trinh', 'ngôn ngữ', 'ngon ngu', 'backend', 'frontend', 'database', 'csdl', 'server', 'máy chủ', 'may chu', 'admin', 'api', 'token', 'key', 'ai viết', 'ai viet', 'ai làm', 'ai lam', 'ai code', 'ai sáng lập', 'ai sang lap', 'người tạo', 'nguoi tao', 'tác giả', 'tac gia', 'phần mềm', 'phan mem', 'github', 'git', 'laravel', 'vue', 'inertia', 'vite', 'mysql',
            'thời tiết', 'thoi tiet', 'nhiệt độ', 'nhiet do', 'dự báo', 'du bao', 'trời mưa', 'troi mua', 'trời nắng', 'troi nang', 'bão', 'bao',
            'tổng thống', 'tong thong', 'thủ tướng', 'thu tuong', 'chính trị', 'chinh tri', 'chính phủ', 'chinh phu', 'bầu cử', 'bau cu', 'quốc hội', 'quoc hoi',
            'viết code', 'viet code', 'lập trình', 'lap trinh', 'python', 'javascript', 'php', 'java', 'c++', 'html', 'css', 'sql', 'function', 'react',
            'toán học', 'toan hoc', 'phương trình', 'phuong trinh', 'tính tổng', 'tinh tong', 'tính tích', 'bài toán', 'bai toan',
            'nấu ăn', 'nau an', 'cách nấu', 'cach nau', 'món ăn', 'mon an', 'công thức nấu', 'ẩm thực', 'am thuc', 'quán ăn ngon', 'quan an ngon',
            'kể chuyện', 'ke chuyen', 'truyện cười', 'truyen cuoi', 'đố vui', 'do vui', 'bài thơ', 'bai tho', 'làm thơ', 'lam tho', 'ca sĩ', 'ca si', 'bài hát', 'bai hat',
            'dịch giúp', 'dich giup', 'dịch sang', 'dich sang', 'tiếng anh là gì', 'tieng anh la gi',
            'bạn là ai', 'ban la ai', 'ai tạo ra bạn', 'ai tao ra ban', 'bạn tên gì', 'ban ten gi', 'mấy tuổi', 'may tuoi',
            'tin tức', 'tin tuc', 'chứng khoán', 'chung khoan', 'tiền ảo', 'crypto', 'bitcoin', 'bóng đá', 'bong da', 'kết quả bóng đá', 'thể thao', 'the thao'
        ];

        foreach ($offTopicKeywords as $otk) {
            if (preg_match('/\b' . preg_quote($otk, '/') . '\b/ui', $lowerPrompt) || preg_match('/\b' . preg_quote($this->removeVietnameseTones($otk), '/') . '\b/ui', $normalizedPrompt)) {
                $isOffTopic = true;
                break;
            }
        }

        // Kiểm tra phép tính toán học (vd: 1+1, 2*5, 100/4)
        if (preg_match('/^[0-9\s\+\-\*\/\=\?]+$/', trim($prompt)) || preg_match('/[0-9]+\s*[\+\*\/]\s*[0-9]+/', $prompt)) {
            $isOffTopic = true;
        }

        // Kiểm tra các tín hiệu cốt lõi về tìm trọ / nhà ở / tiện ích / địa điểm
        $coreRoomPatterns = [
            '/\b(?:phòng\s*trọ|phong\s*tro|nhà\s*trọ|nha\s*tro|homestay|studio|căn\s*hộ|can\s*ho|chung\s*cư|chung\s*cu|ký\s*túc\s*xá|ky\s*tuc\s*xa|ktx|ở\s*ghép|o\s*ghep|thuê\s*trọ|thue\s*tro|thuê\s*phòng|thue\s*phong)\b/ui',
            '/\b(?:tìm\s*phòng|tim\s*phong|cần\s*phòng|can\s*phong|ở\s*trọ|o\s*tro|chỗ\s*ở|cho\s*o|người\s*ở|nguoi\s*o|còn\s*phòng|con\s*phong|có\s*phòng|co\s*phong|thuê\s*nhà|thue\s*nha|thuê|thue|phòng|phong|trọ|tro)\b/ui',
            '/\b(?:gác\s*xép|gac\s*xep|điều\s*hòa|dieu\s*hoa|nóng\s*lạnh|nong\s*lanh|máy\s*giặt|may\s*giat|thú\s*cưng|thu\s*cung|ban\s*công|ban\s*cong|thang\s*máy|thang\s*may)\b/ui',
            '/\b(?:hoa\s*lư|hoa\s*lu|ninh\s*bình|ninh\s*binh|tam\s*điệp|tam\s*diep|gia\s*viễn|gia\s*vien|yên\s*khánh|yen\s*khanh|nho\s*quan|kim\s*sơn|kim\s*son|yên\s*mô|yen\s*mo)\b/ui',
            '/\b(?:giá\s*rẻ|gia\s*re|sinh\s*viên|sinh\s*vien|bình\s*dân|binh\s*dan|tiết\s*kiệm|tiet\s*kiem)\b/ui',
            '/\b[0-9]+(?:\.[0-9]+)?\s*(?:triệu|trieu|tr|nghìn|ngan|vnd|đ|đồng)\b/ui',
            '/\b(?:tầng|tang|lầu|lau)\s*[0-9]+/ui',
            '/\b[0-9]+\s*(?:m2|m²)\b/ui',
            '/\b(?:chào|xin\s*chào|hello|hi|alo|hey)\b/ui'
        ];

        $hasCoreRoomIntent = false;
        foreach ($coreRoomPatterns as $pattern) {
            if (preg_match($pattern, $lowerPrompt) || preg_match($pattern, $normalizedPrompt)) {
                $hasCoreRoomIntent = true;
                break;
            }
        }

        // Nếu câu chứa từ khóa ngoài lề / hệ thống và không có từ khóa tìm phòng rõ ràng, hoặc câu hoàn toàn không có bất kỳ từ khóa liên quan nào
        if ($isOffTopic) {
            $result['is_related_to_room_search'] = false;
            $result['refusal_message'] = 'Tôi không thể trả lời câu hỏi này. Trợ lý AI chỉ hỗ trợ tìm kiếm và tư vấn thông tin phòng trọ, không hỗ trợ thao tác ảnh hưởng đến website hoặc trả lời các câu hỏi không liên quan đến gợi ý và tìm kiếm phòng.';
            $result['explanation'] = 'Tôi không thể trả lời câu hỏi này.';
            return $result;
        }

        if (!$hasCoreRoomIntent && mb_strlen($prompt, 'UTF-8') > 3) {
            $result['is_related_to_room_search'] = false;
            $result['refusal_message'] = 'Tôi không thể trả lời câu hỏi này. Trợ lý AI chỉ hỗ trợ tìm kiếm và tư vấn thông tin phòng trọ, không hỗ trợ thao tác ảnh hưởng đến website hoặc trả lời các câu hỏi không liên quan đến gợi ý và tìm kiếm phòng.';
            $result['explanation'] = 'Tôi không thể trả lời câu hỏi này.';
            return $result;
        }

        $result['is_related_to_room_search'] = true;
        $result['refusal_message'] = null;

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

        // 1B. Giá tối thiểu (Trên X / > X / lớn hơn X / từ X trở lên / X trở lên / X đổ lên / tối thiểu X)
        if (!$result['price_min'] && preg_match('/(?:trên|tren|>|lớn hơn|lon hon|cao hơn|cao hon|hơn|hon|tối thiểu|toi thieu|ít nhất|it nhat|từ|tu|>=)\s*([0-9]+(?:[\.,][0-9]+)?\s*(?:triệu|trieu|tr|củ|cu|nghìn|ngàn|nghin|ngan|k|đ|dong|đồng|vnd)?|[0-9]+\s*tr\s*[0-9]+|[0-9]{1,3}(?:[\.,][0-9]{3})+)(?:\s*(?:trở lên|tro len|đổ lên|do len|trở đi|tro di|hất lên|hat len))?/ui', $lowerPrompt, $m)) {
            $p = $this->parseVietnamesePriceAmount($m[1]);
            if ($p) {
                $result['price_min'] = $p;
                $explanationParts[] = 'Giá ≥ ' . number_format($p, 0, ',', '.') . ' đ';
                $extractedSegments[] = $m[0];
            }
        } elseif (!$result['price_min'] && preg_match('/([0-9]+(?:[\.,][0-9]+)?\s*(?:triệu|trieu|tr|củ|cu|nghìn|ngàn|nghin|ngan|k|đ|dong|đồng|vnd)?|[0-9]+\s*tr\s*[0-9]+|[0-9]{1,3}(?:[\.,][0-9]{3})+)\s*(?:trở lên|tro len|đổ lên|do len|trở đi|tro di|hất lên|hat len|cộng|\+)/ui', $lowerPrompt, $m)) {
            $p = $this->parseVietnamesePriceAmount($m[1]);
            if ($p) {
                $result['price_min'] = $p;
                $explanationParts[] = 'Giá ≥ ' . number_format($p, 0, ',', '.') . ' đ';
                $extractedSegments[] = $m[0];
            }
        }

        // 1C. Giá tối đa (Dưới X / <= X / tối đa X / nhỏ hơn X / X trở xuống / X đổ lại / X quay đầu)
        if (!$result['price_max'] && preg_match('/(?:dưới|duoi|<|nhỏ hơn|nho hon|thấp hơn|thap hon|tối đa|toi da|khong qua|không quá|<=|ít hơn|it hon)\s*([0-9]+(?:[\.,][0-9]+)?\s*(?:triệu|trieu|tr|củ|cu|nghìn|ngàn|nghin|ngan|k|đ|dong|đồng|vnd)?|[0-9]+\s*tr\s*[0-9]+|[0-9]{1,3}(?:[\.,][0-9]{3})+)(?:\s*(?:trở xuống|tro xuong|đổ lại|do lai|quay đầu|quay dau|trở lại|tro lai|đổ về|do ve))?/ui', $lowerPrompt, $m)) {
            $p = $this->parseVietnamesePriceAmount($m[1]);
            if ($p) {
                $result['price_max'] = $p;
                $explanationParts[] = 'Giá ≤ ' . number_format($p, 0, ',', '.') . ' đ';
                $extractedSegments[] = $m[0];
            }
        } elseif (!$result['price_max'] && preg_match('/([0-9]+(?:[\.,][0-9]+)?\s*(?:triệu|trieu|tr|củ|cu|nghìn|ngàn|nghin|ngan|k|đ|dong|đồng|vnd)?|[0-9]+\s*tr\s*[0-9]+|[0-9]{1,3}(?:[\.,][0-9]{3})+)\s*(?:trở xuống|tro xuong|đổ lại|do lai|quay đầu|quay dau|trở lại|tro lai|đổ về|do ve|là cùng|la cung)/ui', $lowerPrompt, $m)) {
            $p = $this->parseVietnamesePriceAmount($m[1]);
            if ($p) {
                $result['price_max'] = $p;
                $explanationParts[] = 'Giá ≤ ' . number_format($p, 0, ',', '.') . ' đ';
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

        // 1E. Nhận diện Nhu cầu Giá rẻ / Sinh viên / Bình dân / Tiết kiệm
        if (preg_match('/(?:giá rẻ|gia re|giá sinh viên|gia sinh vien|sinh viên|sinh vien|tiết kiệm|tiet kiem|bình dân|binh dan|giá thấp|gia thap|giá rẻ nhất|gia re nhat|rẻ nhất|re nhat|rẻ|re|rẻ rẻ|ở ghép|o ghep|phòng ghép|phong ghep)/ui', $lowerPrompt, $m)) {
            $result['is_budget_friendly'] = true;
            $explanationParts[] = 'Ưu tiên giá rẻ nhất';
            $extractedSegments[] = $m[0];
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
                $extractedSegments[] = 'Phường ' . $cleanAreaName;
                $extractedSegments[] = 'Xã ' . $cleanAreaName;
                $extractedSegments[] = 'Thị trấn ' . $cleanAreaName;
                $extractedSegments[] = 'Huyện ' . $cleanAreaName;
                $extractedSegments[] = 'Thành phố ' . $cleanAreaName;
                $extractedSegments[] = 'phuong ' . $cleanAreaName;
                $extractedSegments[] = 'xa ' . $cleanAreaName;
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
            'gác xép' => ['gác xép', 'gac xep', 'gác lửng', 'gac lung', 'gx', 'gác'],
            'điều hòa' => ['điều hòa', 'dieu hoa', 'điều hoà', 'máy lạnh', 'may lanh', 'đh', 'dh'],
            'nóng lạnh' => ['nóng lạnh', 'nong lanh', 'bình nóng lạnh', 'nuoc nong', 'nl'],
            'máy giặt' => ['máy giặt', 'may giat', 'mg'],
            'tủ lạnh' => ['tủ lạnh', 'tu lanh', 'tl'],
            'wifi' => ['wifi', 'mạng', 'internet', 'wf'],
            'ban công' => ['ban công', 'ban cong', 'cửa sổ', 'cua so', 'thoáng mát', 'bc'],
            'thang máy' => ['thang máy', 'thang may', 'tm'],
            'bếp' => ['bếp', 'bep', 'nấu ăn', 'nau an', 'tu bep', 'ke bep'],
            'camera' => ['camera', 'an ninh', 'bảo vệ'],
            'chỗ để xe' => ['chỗ để xe', 'để xe', 'de xe', 'gui xe', 'gửi xe', 'nhà xe', 'bãi xe', 'bai xe', 'xe'],
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
                // Add aliases if present
                foreach ($amenityAliases as $key => $aliases) {
                    if (stripos($amNorm, $this->removeVietnameseTones($key)) !== false) {
                        foreach ($aliases as $alias) {
                            $extractedSegments[] = $alias;
                        }
                    }
                }
            } else {
                foreach ($amenityAliases as $key => $aliases) {
                    if (stripos($amNorm, $this->removeVietnameseTones($key)) !== false) {
                        foreach ($aliases as $alias) {
                            $normAlias = $this->removeVietnameseTones($alias);
                            if (mb_strlen($normAlias, 'UTF-8') <= 3) {
                                if (preg_match('/(?:\b|\s|^)' . preg_quote($normAlias, '/') . '(?:\b|\s|$)/iu', $normalizedPrompt)) {
                                    $isMatched = true;
                                    foreach ($aliases as $al) {
                                        $extractedSegments[] = $al;
                                    }
                                    break 2;
                                }
                            } else {
                                if (stripos($normalizedPrompt, $normAlias) !== false) {
                                    $isMatched = true;
                                    foreach ($aliases as $al) {
                                        $extractedSegments[] = $al;
                                    }
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
            'phường', 'phuong', 'xã', 'xa', 'thị trấn', 'thi tran', 'thành phố', 'thanh pho',
            'tỉnh', 'tinh', 'huyện', 'huyen', 'quận', 'quan', 'đường', 'duong', 'phố', 'pho',
            'khu vực', 'khu vuc', 'tại', 'tai', 'ở đâu', 'o dau', 'ở', 'o', 'quanh', 'gần', 'gan',
            'phòng trọ', 'phong tro', 'phòng ốc', 'phong oc', 'chỗ ở', 'cho o', 'phòng', 'phong', 'nhà trọ', 'nha tro', 'nhà', 'nha', 'trọ', 'tro',
            'tìm kiếm', 'tim kiem', 'tìm phòng', 'tim phong', 'tìm', 'tim', 'cần tìm', 'can tim', 'cần', 'can',
            'cho thuê', 'cho thue', 'thuê phòng', 'thue phong', 'thuê trọ', 'thue tro', 'thuê', 'thue',
            'ở ghép', 'o ghep', 'phòng ghép', 'phong ghep',
            'giá rẻ', 'gia re', 'giá sinh viên', 'gia sinh vien', 'sinh viên', 'sinh vien',
            'giá tiền', 'gia tien', 'mức giá', 'muc gia', 'giá', 'gia', 'tiền', 'tien', 'rẻ nhất', 're nhat', 'rẻ', 're', 'tiết kiệm', 'tiet kiem', 'bình dân', 'binh dan',
            'giá thấp', 'gia thap', 'tốt nhất', 'tot nhat', 'đẹp', 'dep', 'xinh',
            'khoảng', 'khoang', 'trong khoảng', 'trong khoang', 'tầm', 'tam', 'mức', 'muc', 'dưới', 'duoi', 'trên', 'tren', 'từ', 'tu', 'đến', 'den', 'tới', 'toi',
            'trở lên', 'tro len', 'đổ lên', 'do len', 'trở đi', 'tro di', 'hất lên', 'hat len',
            'trở xuống', 'tro xuong', 'đổ lại', 'do lai', 'quay đầu', 'quay dau', 'trở lại', 'tro lai', 'đổ về', 'do ve', 'là cùng', 'la cung',
            'các', 'cac', 'những', 'nhung', 'mấy', 'may', 'tất cả', 'tat ca', 'toàn bộ', 'toan bo', 'danh sách', 'danh sach', 'xem', 'top', 'tất', 'tat', 'mọi', 'moi', 'mỗi', 'từng', 'tung', 'cả', 'ca',
            'cho tôi', 'cho toi', 'cho mình', 'cho minh', 'cho em', 'cho anh', 'hỏi', 'hoi', 'tư vấn', 'tu van', 'gợi ý', 'goi y', 'chỉ', 'chi', 'xin', 'còn', 'con', 'đang', 'dang', 'hiện tại', 'hien tai', 'hiện', 'hien', 'bên', 'ben', 'phía', 'phia', 'chỗ', 'nơi', 'noi',
            'có', 'co', 'và', 'va', 'với', 'voi', 'vs', 'được', 'duoc', 'dc', 'đc', 'nào', 'nao', 'là', 'la', 'nhé', 'nhe', 'nha', 'ạ', 'a', 'ơi', 'oi', 'nè', 'ne', 'được không', 'duoc khong', 'dc ko', 'ko', 'k', 'hông', 'hong', 'không', 'khong',
            'giúp', 'giup', 'hộ', 'ho', 'mình', 'minh', 'tôi', 'toi', 'em', 'anh', 'chị', 'chi', 'bạn', 'ban',
            'loại', 'loai', 'kiểu', 'kieu', 'dạng', 'dang', 'cái', 'cai', 'chiếc', 'chiec',
            'tháng', 'thang', 'người', 'nguoi', 'ng', 'diện tích', 'dien tich',
            'tầng', 'tang', 'lầu', 'lau', 'm2', 'm²', 'triệu', 'trieu', 'tr', 'củ', 'cu', 'k', 'nghìn', 'ngan', 'đồng', 'dong', 'đ', 'vnd'
        ];

        // Sắp xếp stop words theo độ dài giảm dần để regex replace các cụm dài trước
        usort($stopWords, fn($a, $b) => mb_strlen($b, 'UTF-8') - mb_strlen($a, 'UTF-8'));
        foreach ($stopWords as $sw) {
            $cleanPrompt = preg_replace('/(?<![\p{L}\p{N}])' . preg_quote($sw, '/') . '(?![\p{L}\p{N}])/ui', ' ', $cleanPrompt);
        }

        // Làm sạch dấu câu và khoảng trắng thừa
        $cleanPrompt = trim(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $cleanPrompt));
        $cleanPrompt = trim(preg_replace('/\s+/', ' ', $cleanPrompt));

        // Kiểm tra xem từ khóa có phải chỉ là từ hành chính / rác không
        $adminOnlyWords = ['phuong', 'phường', 'xa', 'xã', 'thi tran', 'thị trấn', 'thanh pho', 'thành phố', 'tinh', 'tỉnh', 'huyen', 'huyện', 'quan', 'quận', 'duong', 'đường', 'pho', 'phố', 'gia re', 'giá rẻ', 'sinh vien', 'sinh viên', 're', 'rẻ', 'tai', 'tại', 'o', 'ở', 'cac', 'các', 'nhung', 'những', 'may', 'mấy', 'co', 'có', 'gia', 'giá', 'phong', 'phòng', 'tro', 'trọ', 'tro len', 'trở lên', 'do len', 'đổ lên', 'tro xuong', 'trở xuống', 'do lai', 'đổ lại', 'quay dau', 'quay đầu'];
        if (in_array(mb_strtolower($cleanPrompt, 'UTF-8'), $adminOnlyWords) || in_array($this->removeVietnameseTones(mb_strtolower($cleanPrompt, 'UTF-8')), $adminOnlyWords)) {
            $cleanPrompt = '';
        }

        $hasConcreteFilter = !empty($result['area_id']) 
            || !empty($result['price_max']) 
            || !empty($result['price_min']) 
            || !empty($result['amenity_ids']) 
            || !empty($result['category_id']) 
            || !empty($result['floor_number']) 
            || !empty($result['is_budget_friendly']);

        if (mb_strlen($cleanPrompt, 'UTF-8') >= 2) {
            if (!in_array(mb_strtolower($cleanPrompt, 'UTF-8'), $adminOnlyWords) && !in_array($this->removeVietnameseTones(mb_strtolower($cleanPrompt, 'UTF-8')), $adminOnlyWords)) {
                $result['keyword'] = $cleanPrompt;
                $explanationParts[] = 'Từ khóa: "' . $cleanPrompt . '"';
            } else {
                $result['keyword'] = null;
            }
        } else {
            $result['keyword'] = null;
        }

        // Tóm tắt kết quả giải thích
        if (!empty($explanationParts)) {
            $result['explanation'] = 'Đã lọc theo: ' . implode(' • ', $explanationParts);
        } else {
            if (preg_match('/(?:chào|xin chào|hello|hi|alo)/ui', $lowerPrompt)) {
                $result['explanation'] = 'Chào bạn! Mình là Trợ lý AI Ninh Bình HomeStay. Mình có thể giúp bạn tìm phòng theo khu vực, mức giá, tiện ích hoặc số người ở.';
            } else {
                $result['is_related_to_room_search'] = false;
                $result['refusal_message'] = 'Tôi không thể trả lời câu hỏi này. Trợ lý AI chỉ hỗ trợ tìm kiếm và tư vấn thông tin phòng trọ, không hỗ trợ thao tác ảnh hưởng đến website hoặc trả lời các câu hỏi không liên quan đến gợi ý và tìm kiếm phòng.';
                $result['explanation'] = 'Tôi không thể trả lời câu hỏi này.';
            }
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
            'is_related_to_room_search' => isset($data['is_related_to_room_search']) ? (bool)$data['is_related_to_room_search'] : true,
            'refusal_message' => !empty($data['refusal_message']) ? (string)$data['refusal_message'] : null,
            'keyword' => !empty($data['keyword']) ? (string)$data['keyword'] : null,
            'area_id' => !empty($data['area_id']) ? (int)$data['area_id'] : null,
            'area_name' => !empty($data['area_name']) ? (string)$data['area_name'] : null,
            'price_min' => !empty($data['price_min']) ? (int)$data['price_min'] : null,
            'price_max' => !empty($data['price_max']) ? (int)$data['price_max'] : null,
            'is_budget_friendly' => !empty($data['is_budget_friendly']),
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
            'is_related_to_room_search' => true,
            'refusal_message' => null,
            'keyword' => null,
            'area_id' => null,
            'area_name' => null,
            'price_min' => null,
            'price_max' => null,
            'is_budget_friendly' => false,
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
