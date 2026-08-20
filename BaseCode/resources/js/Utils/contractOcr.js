/**
 * Client-Side Vietnamese Contract OCR Engine & Parser (Tesseract.js)
 * Zero Server Load (0% RAM / 0% CPU on backend)
 */

// Dynamic loader for Tesseract.js from CDN if not present in window
export async function loadTesseractLibrary() {
    if (window.Tesseract) {
        return window.Tesseract;
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
        script.async = true;
        script.onload = () => {
            if (window.Tesseract) {
                resolve(window.Tesseract);
            } else {
                reject(new Error('Khởi tạo Tesseract.js thất bại.'));
            }
        };
        script.onerror = () => reject(new Error('Không thể tải thư viện Tesseract.js từ CDN. Vui lòng kiểm tra kết nối mạng.'));
        document.head.appendChild(script);
    });
}

/**
 * Kiểm tra xem 1 chuỗi có phải là Tên cá nhân Việt Nam hợp lệ hay không (không chứa ký tự đặc biệt, không chứa từ rác hợp đồng)
 */
function isValidVietnameseName(str) {
    if (!str) return false;
    const trimmed = str.trim();
    
    // 1. Độ dài tối thiểu 3 ký tự và tối đa 50 ký tự
    if (trimmed.length < 3 || trimmed.length > 50) return false;

    // 2. Tuyệt đối không chứa ký tự đặc biệt như /, \, :, ., _, -, (), [], {}, @, #, $, %, ^, &, *, +, =, ?, <, >
    if (/[0-9\/\\:\.\-_\(\)\*\#\@\!\$\%\^\&\=\+\{\}\[\]\;\,\<\>\?]/g.test(trimmed)) {
        return false;
    }

    // 3. Từ khóa rác / từ khóa mẫu hợp đồng KHÔNG BAO GIỜ có trong tên người
    const forbiddenKeywords = [
        'bên a', 'bên b', 'bên cho thuê', 'bên thuê', 'khách thuê', 'chủ trọ', 'chủ nhà',
        'đại diện', 'họ và tên', 'họ tên', 'người đại diện', 'ông/bà', 'cho thuê', 'thuê nhà',
        'nhà tại', 'nhà trọ', 'phòng trọ', 'hợp đồng', 'cộng hòa', 'xã hội', 'việt nam',
        'độc lập', 'tự do', 'hạnh phúc', 'điều 1', 'điều 2', 'điều 3', 'điều khoản',
        'ngày tháng', 'năm sinh', 'số cccd', 'số cmnd', 'số điện thoại', 'địa chỉ', 'hktt'
    ];

    const lower = trimmed.toLowerCase();
    for (const kw of forbiddenKeywords) {
        if (lower.includes(kw)) return false;
    }

    // 4. Một tên người Việt Nam thực sự phải gồm ít nhất 2 từ (VD: "Nguyễn A", "Văn B"). Loại bỏ hoàn toàn các từ rác đơn lẻ như "Hi", "Aa", "Nha" do OCR đọc từ dấu chấm.
    const words = trimmed.split(/\s+/).filter(Boolean);
    if (words.length < 2) {
        return false;
    }

    // 5. Mỗi từ trong tên phải cấu thành từ các chữ cái Tiếng Việt hợp lệ
    for (const w of words) {
        if (!/^[A-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴÝỶỸa-zàáâãèéêìíòóôõùúăđĩũơưăạảấầẩẫậắằẳẵặẹẻẽềềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵýỷỹ]+$/i.test(w)) {
            return false;
        }
    }

    return true;
}

/**
 * Kiểm tra chuỗi có phải tiêu đề section (Bên A, Bên B, Cho thuê...) thay vì tên cá nhân không
 */
function isTitleKeyword(str) {
    if (!str) return true;
    const lower = str.toLowerCase().trim();
    const badKeywords = [
        'bên a', 'bên b', 'bên cho thuê', 'bên thuê', 'khách thuê', 'chủ trọ', 'chủ nhà',
        'đại diện', 'họ và tên', 'đại diện bên a', 'đại diện bên b', 'thông tin bên a', 'thông tin bên b',
        'người đại diện', 'bên cho thuê (bên a)', 'bên thuê (bên b)', 'cho thuê', 'thuê nhà'
    ];
    if (badKeywords.includes(lower)) return true;
    if (/^bên\s*[ab]\s*(?:\(.*\))?$/i.test(lower)) return true;
    if (/^bên\s*(?:cho\s*)?thuê\s*(?:\(.*\))?$/i.test(lower)) return true;
    return false;
}

/**
 * Làm sạch chuỗi Tên (Loại bỏ các nhãn tiêu đề như "Đại diện:", "Bên A:", "Họ và tên:", "Ông/Bà:")
 */
function cleanName(str) {
    if (!str) return '';
    let val = str.trim();
    
    // Loại bỏ các nhãn nhầm lẫn phía trước
    val = val.replace(/^(?:đại\s*diện\s*(?:bởi|là)?|người\s*đại\s*diện|họ\s*(?:và\s*)?tên|chủ\s*(?:hộ|trọ|nhà)|ông\/bà|ông|bà|anh|chị)[:\.\-_\s]*/i, '');
    val = val.replace(/^(?:bên\s*[ab]|đại\s*diện\s*bên\s*[ab]|bên\s*(?:cho\s*)?thuê|khách\s*thuê)\s*[:\.\-_]*\s*(?:ông\/bà|ông|bà|đại\s*diện\s*(?:bởi|là)?)?[:\.\-_\s]*/i, '');
    val = val.replace(/^(?:ông\/bà|ông|bà|anh|chị)[:\.\-_\s]*/i, '');

    // Loại bỏ ngoặc đơn tiêu đề nếu còn sót (VD: "(Bên A)", "(Bên Cho Thuê)")
    val = val.replace(/\(?(?:bên\s*(?:cho\s*)?thuê|khách\s*thuê|chủ\s*trọ|bên\s*[ab])\)?/gi, '');
    val = val.replace(/^[:\.\-_\s]+|[:\.\-_\s]+$/g, '').trim();

    if (isTitleKeyword(val)) return '';
    if (/^[\.\-_\s]*$/.test(val)) return '';
    return val;
}

function cleanVal(str) {
    return cleanName(str);
}

/**
 * Phân tích ngày tháng tiếng Việt (DD/MM/YYYY hoặc ngày DD tháng MM năm YYYY) -> YYYY-MM-DD
 */
function parseVietnameseDate(str) {
    if (!str) return '';
    
    // Mẫu: ngày 01 tháng 06 năm 2025
    const textDateMatch = str.match(/(?:ngày)?\s*(\d{1,2})\s*tháng\s*(\d{1,2})\s*năm\s*(\d{4})/i);
    if (textDateMatch) {
        const d = parseInt(textDateMatch[1], 10);
        const m = parseInt(textDateMatch[2], 10);
        const y = parseInt(textDateMatch[3], 10);
        return `${y.toString().padStart(4, '0')}-${m.toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}`;
    }

    // Mẫu: DD/MM/YYYY hoặc DD-MM-YYYY
    const parts = str.trim().split(/[\/\-\.]/);
    if (parts.length === 3) {
        let d = parseInt(parts[0], 10);
        let m = parseInt(parts[1], 10);
        let y = parseInt(parts[2], 10);
        if (y < 100) y += 2000;
        if (!isNaN(d) && !isNaN(m) && !isNaN(y) && m >= 1 && m <= 12 && d >= 1 && d <= 31) {
            return `${y.toString().padStart(4, '0')}-${m.toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}`;
        }
    }
    return '';
}

/**
 * Bóc tách ngữ nghĩa dữ liệu hợp đồng từ chuỗi thô (Raw Text)
 */
export function parseContractText(rawText) {
    const output = {
        success: true,
        has_data: false,
        is_blank: false,
        landlord_name: '',
        landlord_cccd: '',
        landlord_phone: '',
        landlord_address: '',
        tenant_name: '',
        tenant_cccd: '',
        tenant_phone: '',
        tenant_dob: '',
        tenant_address: '',
        start_date: '',
        end_date: '',
        monthly_rent: null,
        deposit_amount: null,
        raw_text: rawText || '',
        message: 'Đã hoàn tất trích xuất dữ liệu trực tiếp trên trình duyệt.'
    };

    if (!rawText || rawText.trim().length < 15) {
        output.is_blank = true;
        output.message = 'Văn bản hợp đồng trống hoặc không thể nhận diện chữ trong ảnh.';
        return output;
    }

    const lines = rawText.split('\n').map(l => l.trim()).filter(Boolean);
    const cleanedText = rawText.replace(/\.{2,}/g, ' ');

    // 1. Tìm vị trí phân vùng BÊN A và BÊN B
    let idxA = -1;
    let idxB = -1;
    let idxTerms = -1;

    for (let i = 0; i < lines.length; i++) {
        const lineLower = lines[i].toLowerCase();
        if (idxA === -1 && (lineLower.includes('bên a') || lineLower.includes('bên cho thuê') || lineLower.includes('chủ trọ'))) {
            idxA = i;
        } else if (idxB === -1 && (lineLower.includes('bên b') || lineLower.includes('bên thuê') || lineLower.includes('khách thuê'))) {
            idxB = i;
        } else if (idxTerms === -1 && (lineLower.includes('điều khoản') || lineLower.includes('thời hạn') || lineLower.includes('giá thuê') || lineLower.includes('điều 1'))) {
            idxTerms = i;
        }
    }

    if (idxA === -1) idxA = 0;
    if (idxB === -1 || idxB <= idxA) idxB = Math.floor(lines.length / 2);
    if (idxTerms === -1 || idxTerms <= idxB) idxTerms = lines.length;

    const linesA = lines.slice(idxA, idxB);
    const linesB = lines.slice(idxB, idxTerms);

    // Hàm tìm Tên người đại diện trong danh sách các dòng của 1 section (Bên A hoặc Bên B)
    const findNameInLines = (sectionLines) => {
        for (let i = 0; i < sectionLines.length; i++) {
            const line = sectionLines[i];

            // 1. Bỏ qua các dòng thuần tiêu đề section (VD: "BÊN A (BÊN CHO THUÊ)", "BÊN B (KHÁCH THUÊ)")
            if (/^\s*(?:bên\s*[ab]|bên\s*(?:cho\s*)?thuê|khách\s*thuê|chủ\s*trọ|đại\s*diện\s*bên\s*[ab])\s*(?:\([^\)]*\))?\s*[:\.\-_]*\s*$/i.test(line)) {
                continue;
            }

            // 2. Ưu tiên tìm dòng chứa nhãn "Đại diện", "Người đại diện", "Họ và tên", "Ông/Bà"
            const labelMatch = line.match(/(?:đại\s*diện\s*(?:bởi|là)?|người\s*đại\s*diện|họ\s*(?:và\s*)?tên|ông\/bà|chủ\s*hộ)\s*[:\.\-_]*\s*([A-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴÝỶỸa-zàáâãèéêìíòóôõùúăđĩũơưăạảấầẩẫậắằẳẵặẹẻẽềềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵýỷỹ\s]{2,50})/i);
            if (labelMatch && labelMatch[1]) {
                const cleaned = cleanName(labelMatch[1]);
                if (isValidVietnameseName(cleaned)) {
                    return cleaned;
                }
            }

            // 3. Nếu dòng chứa nhãn "Đại diện:" hoặc "Họ và tên:" nhưng tên nằm ở dòng tiếp theo
            if (/(?:đại\s*diện|người\s*đại\s*diện|họ\s*(?:và\s*)?tên|ông\/bà)/i.test(line) && i + 1 < sectionLines.length) {
                const nextLine = sectionLines[i + 1];
                if (!/(?:cccd|cmnd|căn\s*cước|điện\s*thoại|sđt|địa\s*chỉ|hộ\s*khẩu|ngày\s*sinh|bên\s*[ab])/i.test(nextLine)) {
                    const cleaned = cleanName(nextLine);
                    if (isValidVietnameseName(cleaned)) {
                        return cleaned;
                    }
                }
            }

            // 4. Nếu tên nằm chung dòng với tiêu đề như "Bên A: Nguyễn Văn A" hoặc "Bên B: Trần Thị B"
            const inlineMatch = line.match(/(?:bên\s*[ab]|đại\s*diện\s*bên\s*[ab])\s*(?:\([^\)]*\))?\s*[:\.\-_]+\s*([A-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴÝỶỸa-zàáâãèéêìíòóôõùúăđĩũơưăạảấầẩẫậắằẳẵặẹẻẽềềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵýỷỹ\s]{2,50})/i);
            if (inlineMatch && inlineMatch[1]) {
                const cleaned = cleanName(inlineMatch[1]);
                if (isValidVietnameseName(cleaned)) {
                    return cleaned;
                }
            }
        }

        // Fallback: Quét dòng nào có tên cá nhân hợp lệ trong section (có ít nhất 2 từ)
        for (const line of sectionLines) {
            if (/^\s*(?:bên\s*[ab]|bên\s*(?:cho\s*)?thuê|khách\s*thuê|chủ\s*trọ)\s*(?:\([^\)]*\))?\s*[:\.\-_]*\s*$/i.test(line)) {
                continue;
            }
            if (/(?:cccd|cmnd|căn\s*cước|điện\s*thoại|sđt|địa\s*chỉ|hộ\s*khẩu|ngày\s*sinh|giá\s*thuê|tiền\s*cọc)/i.test(line)) {
                continue;
            }
            const cleaned = cleanName(line);
            if (isValidVietnameseName(cleaned)) {
                return cleaned;
            }
        }

        return '';
    };

    // Hàm tìm CCCD (9 hoặc 12 chữ số)
    const findCCCDInLines = (sectionLines) => {
        for (const line of sectionLines) {
            const m = line.match(/(?:cccd|cmnd|căn\s*cước|số)\s*[:\.\-_]*\s*(\d[\d\s\.\-_]{8,15}\d)/i);
            if (m) {
                const digits = m[1].replace(/\D/g, '');
                if (digits.length === 9 || digits.length === 12) return digits;
            }
            // Fallback số 12 chữ số độc lập
            const standaloneDigits = line.match(/\b\d{12}\b/);
            if (standaloneDigits) return standaloneDigits[0];
        }
        return '';
    };

    // Hàm tìm Số điện thoại (bắt đầu bằng 0 hoặc 84 và có 10 chữ số)
    const findPhoneInLines = (sectionLines) => {
        for (const line of sectionLines) {
            const m = line.match(/(?:điện\s*thoại|sđt|tel|phone|di\s*động|đt)\s*[:\.\-_]*\s*(\+?84|0)([\d\s\.\-]{8,14}\d)/i);
            if (m) {
                const digits = (m[1] + m[2]).replace(/\D/g, '');
                if (digits.length >= 10) return '0' + digits.slice(-9);
            }
            const directPhone = line.match(/(?:\+?84|0)(?:3|5|7|8|9)\d{8}\b/);
            if (directPhone) {
                const digits = directPhone[0].replace(/\D/g, '');
                return '0' + digits.slice(-9);
            }
        }
        return '';
    };

    // Hàm tìm Địa chỉ (Hộ khẩu thường trú / Địa chỉ nhà trọ)
    const findAddressInLines = (sectionLines) => {
        for (let i = 0; i < sectionLines.length; i++) {
            const line = sectionLines[i];
            const addrMatch = line.match(/(?:địa\s*chỉ|hộ\s*khẩu|thường\s*trú|nơi\s*ở|hktt|nơi\s*đăng\s*ký)\s*[:\.\-_]*\s*([^\n\r\_]{5,120})/i);
            if (addrMatch && addrMatch[1]) {
                let addr = addrMatch[1].replace(/[\._\-]{2,}/g, '').trim();
                addr = addr.replace(/^(?:địa\s*chỉ|hộ\s*khẩu|thường\s*trú|nơi\s*ở|hktt|nơi\s*đăng\s*ký)[:\.\-_\s]*/i, '').trim();
                if (addr.length >= 5 && !/^[\.\-_\s]*$/.test(addr) && !/^(?:địa\s*chỉ|hộ\s*khẩu|thường\s*trú)$/i.test(addr)) {
                    return addr;
                }
            }

            // Nếu từ khóa nhãn "Địa chỉ:" nằm riêng ở 1 dòng, lấy dòng liền sau đó
            if (/(?:địa\s*chỉ|hộ\s*khẩu|thường\s*trú|nơi\s*ở|hktt)/i.test(line) && i + 1 < sectionLines.length) {
                let nextLine = sectionLines[i + 1].replace(/[\._\-]{2,}/g, '').trim();
                if (nextLine.length >= 5 && !/^[\.\-_\s]*$/.test(nextLine) && !/(?:cccd|cmnd|điện\s*thoại|sđt|bên\s*[ab])/i.test(nextLine)) {
                    return nextLine;
                }
            }
        }
        return '';
    };

    // 2. BÓC TÁCH BÊN A (CHỦ TRỌ)
    output.landlord_name = findNameInLines(linesA);
    output.landlord_cccd = findCCCDInLines(linesA);
    output.landlord_phone = findPhoneInLines(linesA);
    output.landlord_address = findAddressInLines(linesA);

    // 3. BÓC TÁCH BÊN B (KHÁCH THUÊ)
    output.tenant_name = findNameInLines(linesB);
    output.tenant_cccd = findCCCDInLines(linesB);
    output.tenant_phone = findPhoneInLines(linesB);
    output.tenant_address = findAddressInLines(linesB);

    // Tìm Ngày sinh Bên B
    for (const line of linesB) {
        const mDob = line.match(/(?:ngày\s*sinh|sinh\s*ngày|năm\s*sinh)\s*[:\.\-_]*\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})/i);
        if (mDob) {
            output.tenant_dob = parseVietnameseDate(mDob[1]);
            break;
        }
    }

    // 4. THỜI HẠN & GIÁ THUÊ
    // Tìm các chuỗi ngày tháng
    const allDates = [...cleanedText.matchAll(/(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})|(?:ngày\s*\d{1,2}\s*tháng\s*\d{1,2}\s*năm\s*\d{4})/gi)];
    if (allDates.length >= 1) {
        output.start_date = parseVietnameseDate(allDates[0][0]);
        if (allDates.length >= 2) {
            output.end_date = parseVietnameseDate(allDates[1][0]);
        }
    }

    // Tìm Giá thuê
    const rentMatch = cleanedText.match(/(?:giá\s*thuê|tiền\s*thuê|giá\s*phòng)\s*[:\.\-_]*\s*([\d\.,\s]+)/i);
    if (rentMatch) {
        const val = parseInt(rentMatch[1].replace(/[\.,\s]/g, ''), 10);
        if (!isNaN(val) && val > 100000) output.monthly_rent = val;
    }

    // Tìm Tiền cọc
    const depositMatch = cleanedText.match(/(?:tiền\s*cọc|đặt\s*cọc|tiền\s*thế\s*chân)\s*[:\.\-_]*\s*([\d\.,\s]+)/i);
    if (depositMatch) {
        const val = parseInt(depositMatch[1].replace(/[\.,\s]/g, ''), 10);
        if (!isNaN(val) && val > 100000) output.deposit_amount = val;
    }

    // Kiểm tra lại tính hợp lệ của các trường tên
    if (!isValidVietnameseName(output.landlord_name)) output.landlord_name = '';
    if (!isValidVietnameseName(output.tenant_name)) output.tenant_name = '';

    // Đếm số lượng dấu chấm điền tay (placeholder dots) trong hợp đồng mẫu
    const placeholderCount = (rawText.match(/[\.\-_]{5,}/g) || []).length;

    // Đánh giá xem hợp đồng có dữ liệu thực sự hay là mẫu in chưa điền
    const hasRealData = Boolean(
        output.landlord_name || 
        output.tenant_name || 
        output.landlord_cccd || 
        output.tenant_cccd || 
        output.landlord_phone || 
        output.tenant_phone || 
        output.monthly_rent
    );

    if (hasRealData && placeholderCount < 8) {
        output.has_data = true;
        output.is_blank = false;
        output.message = 'Trích xuất dữ liệu thành công từ ảnh hợp đồng!';
    } else {
        // Hợp đồng là mẫu in chưa điền hoặc chứa rác từ dấu chấm
        output.is_blank = true;
        output.has_data = false;
        output.landlord_name = '';
        output.landlord_cccd = '';
        output.landlord_phone = '';
        output.landlord_address = '';
        output.tenant_name = '';
        output.tenant_cccd = '';
        output.tenant_phone = '';
        output.tenant_dob = '';
        output.tenant_address = '';
        output.monthly_rent = null;
        output.deposit_amount = null;
        output.message = 'Phát hiện ảnh hợp đồng là mẫu in chưa điền thông tin. Tất cả các trường đã được để trống.';
    }

    return output;
}

/**
 * Thực hiện OCR ảnh hợp đồng bằng Tesseract.js trên Trình duyệt
 * @param {File|Blob} imageFile - Tệp ảnh
 * @param {Function} progressCallback - Callback nhận tiến trình (0-100%)
 */
export async function performClientOcr(imageFile, progressCallback = null) {
    try {
        const Tesseract = await loadTesseractLibrary();

        if (progressCallback) progressCallback(10, 'Đang khởi tạo Engine Tesseract.js...');

        const worker = await Tesseract.createWorker('vie');

        if (progressCallback) progressCallback(40, 'Đang phân tích hình ảnh và nhận diện văn bản tiếng Việt...');

        const ret = await worker.recognize(imageFile);

        if (progressCallback) progressCallback(85, 'Đang bóc tách thông tin chủ trọ, khách thuê và điều khoản...');

        await worker.terminate();

        const parsedData = parseContractText(ret.data.text);
        if (progressCallback) progressCallback(100, 'Quét OCR hoàn tất!');

        return parsedData;

    } catch (error) {
        console.error('Client OCR error:', error);
        return {
            success: false,
            has_data: false,
            is_blank: false,
            message: 'Lỗi khi quét OCR trên trình duyệt: ' + (error.message || 'Không thể xử lý tệp ảnh.')
        };
    }
}
