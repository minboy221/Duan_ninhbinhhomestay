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
 * Làm sạch chuỗi lấy giá trị Tên/Địa chỉ
 */
function cleanVal(str) {
    if (!str) return '';
    let val = str.trim();
    val = val.replace(/^(?:họ\s*(?:và\s*)?tên|đại\s*diện|ông\/bà|bên\s*[ab])[:\.\-_\s]*/i, '');
    val = val.replace(/^[:\.\-_\s]+|[:\.\-_\s]+$/g, '');
    if (/^[\.\-_\s]*$/.test(val)) return '';
    return val;
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

    // Hàm tìm Tên trong danh sách các dòng (quét cả cùng dòng lẫn dòng kế tiếp)
    const findNameInLines = (sectionLines) => {
        for (let i = 0; i < sectionLines.length; i++) {
            const line = sectionLines[i];
            const nameMatch = line.match(/(?:họ\s*(?:và\s*)?tên|đại\s*diện|ông\/bà|bên\s*[ab])\s*[:\.\-_]*\s*([A-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴÝỶỸa-zàáâãèéêìíòóôõùúăđĩũơưăạảấầẩẫậắằẳẵặẹẻẽềềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵýỷỹ\s]{2,50})/i);
            if (nameMatch && nameMatch[1]) {
                const cleaned = cleanVal(nameMatch[1]);
                if (cleaned.length >= 3) return cleaned;
            }

            // Nếu dòng chứa từ khóa nhãn (Họ và tên:) nhưng tên ở dòng tiếp theo
            if (/(?:họ\s*(?:và\s*)?tên|ông\/bà|đại\s*diện)/i.test(line) && i + 1 < sectionLines.length) {
                const nextLine = sectionLines[i + 1];
                if (/^[A-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴÝỶỸa-zàáâãèéêìíòóôõùúăđĩũơưăạảấầẩẫậắằẳẵặẹẻẽềềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵýỷỹ\s]{3,40}$/i.test(nextLine)) {
                    return cleanVal(nextLine);
                }
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
                let addr = addrMatch[1].replace(/\.{2,}/g, '').trim();
                addr = addr.replace(/^(?:địa\s*chỉ|hộ\s*khẩu|thường\s*trú|nơi\s*ở|hktt|nơi\s*đăng\s*ký)[:\.\-_\s]*/i, '');
                if (addr.length >= 5) return addr;
            }

            // Nếu từ khóa nhãn "Địa chỉ:" nằm riêng ở 1 dòng, lấy dòng liền sau đó
            if (/(?:địa\s*chỉ|hộ\s*khẩu|thường\s*trú|nơi\s*ở|hktt)/i.test(line) && i + 1 < sectionLines.length) {
                let nextLine = sectionLines[i + 1].replace(/\.{2,}/g, '').trim();
                if (nextLine.length >= 5 && !/(?:cccd|cmnd|điện\s*thoại|sđt|bên\s*[ab])/i.test(nextLine)) {
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

    // Đánh giá dữ liệu
    if (output.tenant_name || output.tenant_cccd || output.landlord_name || output.landlord_cccd || output.monthly_rent || output.tenant_address) {
        output.has_data = true;
        output.is_blank = false;
        output.message = 'Trích xuất dữ liệu thành công từ ảnh hợp đồng!';
    } else {
        const placeholders = (rawText.match(/[\.\-_]{6,}/g) || []).length;
        if (placeholders >= 5) {
            output.is_blank = true;
            output.has_data = false;
            output.message = 'Ảnh hợp đồng là mẫu in chưa điền thông tin.';
        } else {
            output.has_data = true;
            output.message = 'Đã nhận diện văn bản. Hãy kiểm tra các thông tin ở Bước 3.';
        }
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
