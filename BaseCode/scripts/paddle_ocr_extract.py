import sys
import io
import json
import os
import re
import warnings

# Suppress python warnings
warnings.filterwarnings("ignore")

# Force stdout & stderr to UTF-8 on Windows
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')
else:
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8')

def clean_val(val_str):
    if not val_str:
        return ""
    val_str = val_str.strip()
    val_str = re.sub(r'^(?:họ\s*(?:và\s*)?tên|đại\s*di[ệéêe]n|ông/bà|bên\s*[ab])[:\.\-_\s]*', '', val_str, flags=re.IGNORECASE)
    val_str = re.sub(r'^[:\.\-_\s]+|[:\.\-_\s]+$', '', val_str)
    if re.match(r'^[\.\-_\s]*$', val_str):
        return ""
    return val_str

def parse_date(date_str):
    if not date_str:
        return ""
    parts = re.split(r'[\/\-\.]', date_str.strip())
    if len(parts) == 3:
        try:
            d = int(parts[0])
            m = int(parts[1])
            y = int(parts[2])
            if y < 100:
                y += 2000
            return f"{y:04d}-{m:02d}-{d:02d}"
        except ValueError:
            pass
    return date_str

def extract_from_image(image_path):
    output_result = {
        "success": True,
        "has_data": False,
        "is_blank": True,
        "landlord_name": "",
        "landlord_cccd": "",
        "landlord_phone": "",
        "landlord_address": "",
        "tenant_name": "",
        "tenant_cccd": "",
        "tenant_phone": "",
        "tenant_dob": "",
        "tenant_address": "",
        "start_date": "",
        "end_date": "",
        "monthly_rent": None,
        "deposit_amount": None,
        "raw_text": "",
        "message": "Trích xuất PaddleOCR hoàn tất."
    }

    if not os.path.exists(image_path):
        output_result["success"] = False
        output_result["message"] = "Không tìm thấy tệp ảnh hợp đồng."
        return output_result

    try:
        from paddleocr import PaddleOCR
    except ImportError:
        output_result["success"] = False
        output_result["message"] = "Chưa cài đặt thư viện 'paddleocr'."
        return output_result

    try:
        ocr = None
        init_configs = [
            {'use_angle_cls': True, 'lang': 'vi', 'show_log': False},
            {'lang': 'vi', 'show_log': False},
            {'use_angle_cls': True, 'lang': 'vi'},
            {'lang': 'vi'},
            {}
        ]

        for cfg in init_configs:
            try:
                ocr = PaddleOCR(**cfg)
                break
            except Exception:
                continue

        if not ocr:
            output_result["success"] = False
            output_result["message"] = "Không thể khởi tạo PaddleOCR Engine."
            return output_result

        try:
            ocr_res = ocr.ocr(image_path, cls=True)
        except Exception:
            try:
                ocr_res = ocr.ocr(image_path)
            except Exception as ex:
                output_result["success"] = False
                output_result["message"] = f"Lỗi quét ảnh PaddleOCR: {str(ex)}"
                return output_result

        lines = []

        if ocr_res:
            for page in ocr_res:
                if not page:
                    continue
                if isinstance(page, list):
                    for item in page:
                        if not item:
                            continue
                        if isinstance(item, (list, tuple)):
                            if len(item) >= 2 and isinstance(item[1], (list, tuple)):
                                text = item[1][0]
                                if text and str(text).strip():
                                    lines.append(str(text).strip())
                            elif len(item) >= 2 and isinstance(item[1], str):
                                lines.append(str(item[1]).strip())
                        elif isinstance(item, dict):
                            text = item.get('text') or item.get('rec_text') or ''
                            if text:
                                lines.append(str(text).strip())
                elif isinstance(page, dict):
                    texts = page.get('rec_texts') or page.get('texts') or []
                    if not texts and 'rec_text' in page:
                        texts = [page['rec_text']]
                    for t in texts:
                        if t:
                            lines.append(str(t).strip())

        raw_text = "\n".join(lines)
        output_result["raw_text"] = raw_text

        if not raw_text or len(raw_text.strip()) < 15:
            output_result["is_blank"] = True
            output_result["message"] = "Ảnh trống hoặc không nhận diện được văn bản."
            return output_result

        # Phân tách vùng BÊN A, BÊN B và ĐIỀU KHOẢN
        a_pos = re.search(r'(?:bên a|bên cho thuê|chủ trọ|ông/bà a)', raw_text, re.IGNORECASE)
        b_pos = re.search(r'(?:bên b|bên thuê|khách thuê|ông/bà b)', raw_text, re.IGNORECASE)
        t_pos = re.search(r'(?:điều khoản|thời hạn|thời gian thuê|giá thuê|đặt cọc|điều 1)', raw_text, re.IGNORECASE)

        a_idx = a_pos.start() if a_pos else 0
        b_idx = b_pos.start() if b_pos else len(raw_text)
        t_idx = t_pos.start() if t_pos else len(raw_text)

        if a_pos and b_pos and b_idx > a_idx:
            section_a = raw_text[a_idx:b_idx]
            section_b = raw_text[b_idx:t_idx if t_idx > b_idx else len(raw_text)]
        else:
            section_a = raw_text
            section_b = raw_text

        # 1. BÊN B (KHÁCH THUÊ)
        m_cccd_b = re.search(r'(?:cccd|cmnd|căn\s*cước|số)\s*[:\.\-_]*\s*(\d[\d\s]{8,14}\d)', section_b, re.IGNORECASE)
        if m_cccd_b:
            output_result["tenant_cccd"] = m_cccd_b.group(1).replace(" ", "")

        m_name_b = re.search(r'(?:họ\s*(?:và\s*)?tên|đại\s*di[ệéêe]n|ông/bà|bên\s*b)\s*[:\.\-_]*\s*([A-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴÝỶỸa-zàáâãèéêìíòóôõùúăđĩũơưăạảấầẩẫậắằẳẵặẹẻẽềềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵýỷỹ\s]{2,50})', section_b, re.IGNORECASE)
        if m_name_b:
            output_result["tenant_name"] = clean_val(m_name_b.group(1))

        m_phone_b = re.search(r'(?:điện\s*thoại|di[ệéêe]n\s*tho[ạa]i|sđt|tel|phone|di\s*động|đt)\s*[:\.\-_]*\s*(\+?84|0)([\d\s\.\-]{8,12}\d)', section_b, re.IGNORECASE)
        if not m_phone_b:
            m_phone_b = re.search(r'(?:điện\s*thoại|di[ệéêe]n\s*tho[ạa]i|sđt|tel|phone|di\s*động|đt)\s*[:\.\-_]*\s*(\+?84|0)([\d\s\.\-]{8,12}\d)', raw_text, re.IGNORECASE)
        if m_phone_b:
            phone_digits = re.sub(r'\D', '', m_phone_b.group(0))
            if len(phone_digits) >= 10:
                output_result["tenant_phone"] = "0" + phone_digits[-9:]

        m_dob_b = re.search(r'(?:ngày\s*sinh|sinh\s*ngày|năm\s*sinh)\s*[:\.\-_]*\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})', section_b, re.IGNORECASE)
        if m_dob_b:
            output_result["tenant_dob"] = m_dob_b.group(1)

        m_addr_b = re.search(r'(?:hộ\s*khẩu|thường\s*trú|địa\s*chỉ|hktt|dja\s*chi)\s*[:\.\-_]*\s*([^\n\r\.\_]{5,100})', section_b, re.IGNORECASE)
        if m_addr_b:
            output_result["tenant_address"] = clean_val(m_addr_b.group(1))

        # 2. BÊN A (CHỦ TRỌ)
        m_name_a = re.search(r'(?:họ\s*(?:và\s*)?tên|đại\s*di[ệéêe]n|ông/bà|bên\s*a)\s*[:\.\-_]*\s*([A-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴÝỶỸa-zàáâãèéêìíòóôõùúăđĩũơưăạảấầẩẫậắằẳẵặẹẻẽềềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵýỷỹ\s]{2,50})', section_a, re.IGNORECASE)
        if m_name_a:
            output_result["landlord_name"] = clean_val(m_name_a.group(1))

        m_cccd_a = re.search(r'(?:cccd|cmnd|căn\s*cước|số)\s*[:\.\-_]*\s*(\d[\d\s]{8,14}\d)', section_a, re.IGNORECASE)
        if m_cccd_a:
            output_result["landlord_cccd"] = m_cccd_a.group(1).replace(" ", "")

        m_phone_a = re.search(r'(?:điện\s*thoại|di[ệéêe]n\s*tho[ạa]i|sđt|tel|phone|di\s*động|đt)\s*[:\.\-_]*\s*(\+?84|0)([\d\s\.\-]{8,12}\d)', section_a, re.IGNORECASE)
        if m_phone_a:
            phone_digits = re.sub(r'\D', '', m_phone_a.group(0))
            if len(phone_digits) >= 10:
                output_result["landlord_phone"] = "0" + phone_digits[-9:]

        m_addr_a = re.search(r'(?:địa\s*chỉ|dja\s*chi|hộ\s*khẩu|nơi\s*ở)\s*[:\.\-_]*\s*([^\n\r\.\_]{5,100})', section_a, re.IGNORECASE)
        if m_addr_a:
            output_result["landlord_address"] = clean_val(m_addr_a.group(1))

        # 3. ĐIỀU KHOẢN & GIÁ THUÊ
        m_start = re.search(r'(?:từ\s*ngày|bắt\s*đầu|kể\s*từ|hiệu\s*lực|b[aá]t\s*d[áa]u)\s*[:\.\-_\s]*\s*(?:t[ií]r\s*ng[aà]y\s*)?(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})', raw_text, re.IGNORECASE)
        if m_start:
            output_result["start_date"] = parse_date(m_start.group(1))

        m_end = re.search(r'(?:đến\s*ngày|kết\s*thúc|hết\s*hạn|d[eé]n\s*h[eé]t)\s*[:\.\-_\s]*\s*(?:ng[aà]y\s*)?(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})', raw_text, re.IGNORECASE)
        if m_end:
            output_result["end_date"] = parse_date(m_end.group(1))

        m_rent = re.search(r'(?:giá\s*thuê|tiền\s*thuê|giá\s*phòng)\s*[:\.\-_]*\s*([\d\.,\s]+)', raw_text, re.IGNORECASE)
        if m_rent:
            try:
                val = int(re.sub(r'[\.,\s]', '', m_rent.group(1)))
                if val > 100000:
                    output_result["monthly_rent"] = val
            except ValueError:
                pass

        m_dep = re.search(r'(?:tiền\s*cọc|đặt\s*cọc|tiền\s*thế\s*chân)\s*[:\.\-_]*\s*([\d\.,\s]+)', raw_text, re.IGNORECASE)
        if m_dep:
            try:
                val = int(re.sub(r'[\.,\s]', '', m_dep.group(1)))
                if val > 100000:
                    output_result["deposit_amount"] = val
            except ValueError:
                pass

        # Đánh giá dữ liệu
        if (
            output_result["tenant_name"] or
            output_result["tenant_cccd"] or
            output_result["landlord_name"] or
            output_result["landlord_cccd"] or
            output_result["monthly_rent"] or
            output_result["start_date"]
        ):
            output_result["has_data"] = True
            output_result["is_blank"] = False
            output_result["message"] = "Bóc tách thành công dữ liệu từ PaddleOCR!"
        else:
            placeholders = len(re.findall(r'[\.\-_]{6,}', raw_text))
            if placeholders >= 5:
                output_result["is_blank"] = True
                output_result["has_data"] = False
                output_result["message"] = "Hợp đồng mẫu chưa điền thông tin."
            else:
                output_result["is_blank"] = False
                output_result["has_data"] = True
                output_result["message"] = "Ghi nhận ảnh hợp đồng từ PaddleOCR. Hãy kiểm tra lại ở Bước 3."

    except Exception as e:
        output_result["success"] = False
        output_result["message"] = f"Lỗi xử lý PaddleOCR: {str(e)}"

    return output_result

if __name__ == "__main__":
    if len(sys.argv) > 1:
        img_file = sys.argv[1]
        res = extract_from_image(img_file)
        sys.stdout.write(json.dumps(res, ensure_ascii=False))
    else:
        sys.stdout.write(json.dumps({"success": False, "message": "Thiếu tham số đường dẫn ảnh."}, ensure_ascii=False))
