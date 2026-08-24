import Swal from 'sweetalert2';

// Cấu hình mặc định cho các Popup Modal
const CustomSwal = Swal.mixin({
    customClass: {
        popup: 'rounded-3xl p-5 shadow-2xl border border-slate-100 font-sans max-w-sm',
        title: 'text-base font-bold text-slate-800 tracking-tight',
        htmlContainer: 'text-xs text-slate-700 font-medium mt-1.5 leading-relaxed',
        confirmButton: 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition-all shadow-md mx-1',
        cancelButton: 'px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition-all mx-1',
        denyButton: 'px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl text-xs transition-all shadow-md mx-1'
    },
    buttonsStyling: false
});

/**
 * Hiển thị Popup Thông báo Thành công
 */
export const showSuccess = (title, text = '') => {
    if (!text && title && title.length > 30) {
        text = title;
        title = 'Thành Công';
    }
    return CustomSwal.fire({
        icon: 'success',
        title: title,
        text: text,
        confirmButtonText: 'Đồng ý',
        timer: 3000,
        timerProgressBar: true
    });
};

/**
 * Hiển thị Popup Thông báo Lỗi
 */
export const showError = (title, text = '') => {
    if (!text && title && title.length > 30) {
        text = title;
        title = 'Thông Báo Hạn Mức';
    }
    return CustomSwal.fire({
        icon: 'error',
        title: title,
        text: text,
        confirmButtonText: 'Đóng'
    });
};

/**
 * Hiển thị Popup Cảnh báo / Nhắc nhở
 */
export const showWarning = (title, text = '') => {
    return CustomSwal.fire({
        icon: 'warning',
        title: title,
        text: text,
        confirmButtonText: 'Hiểu rồi'
    });
};

/**
 * Hiển thị Popup Thông tin
 */
export const showInfo = (title, text = '') => {
    return CustomSwal.fire({
        icon: 'info',
        title: title,
        text: text,
        confirmButtonText: 'Đóng'
    });
};

/**
 * Hiển thị Popup Xác nhận hành động (Thay thế confirm())
 * Trả về Promise<boolean> (true nếu người dùng bấm Đồng ý, false nếu bấm Hủy)
 */
export const showConfirm = async (title, text = '', confirmButtonText = 'Đồng ý', cancelButtonText = 'Hủy') => {
    const result = await CustomSwal.fire({
        icon: 'question',
        title: title,
        text: text,
        showCancelButton: true,
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText,
        reverseButtons: true
    });
    return result.isConfirmed;
};

/**
 * Hiển thị Popup Nhập nội dung/Lý do (Prompt) - Giao diện chuẩn fit khung & căn giữa tuyệt đối
 */
export const showPrompt = async (title, text = '', inputPlaceholder = 'Nhập nội dung...') => {
    const result = await CustomSwal.fire({
        icon: 'warning',
        title: title,
        html: text ? `<p class="text-xs text-slate-500 font-medium mb-1 leading-relaxed">${text}</p>` : '',
        input: 'textarea',
        inputPlaceholder: inputPlaceholder,
        showCancelButton: true,
        confirmButtonText: 'Xác nhận',
        cancelButtonText: 'Hủy bỏ',
        reverseButtons: true,
        width: '28em',
        customClass: {
            popup: 'rounded-3xl p-6 shadow-2xl border border-slate-100 font-sans bg-white my-auto mx-auto',
            title: 'text-base font-extrabold text-slate-800 tracking-tight mb-1',
            htmlContainer: 'text-xs text-slate-600 font-medium mt-1 leading-relaxed',
            confirmButton: 'px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-rose-200 mx-1',
            cancelButton: 'px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition-all mx-1',
            input: '!w-full !m-0 !box-border rounded-2xl border border-slate-200 p-3 text-xs text-slate-800 focus:border-rose-500 focus:ring-4 focus:ring-rose-100 outline-none shadow-sm font-medium transition-all resize-none mt-3'
        },
        inputValidator: (value) => {
            if (!value || value.trim().length < 5) {
                return 'Vui lòng nhập lý do tối thiểu 5 ký tự!';
            }
        }
    });
    if (result.isConfirmed) {
        return result.value;
    }
    return null;
};

/**
 * Hiển thị thông báo Toast nhanh góc trên bên phải
 */
export const showToast = (title, icon = 'success') => {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
            popup: 'rounded-2xl p-3 shadow-lg border border-slate-100 font-sans text-xs font-semibold'
        }
    });
    return Toast.fire({
        icon: icon,
        title: title
    });
};

export default CustomSwal;
