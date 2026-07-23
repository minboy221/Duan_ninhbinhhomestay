import Swal from 'sweetalert2';

// Cấu hình mặc định cho các Popup Modal
const CustomSwal = Swal.mixin({
    customClass: {
        popup: 'rounded-3xl p-6 shadow-2xl border border-slate-100 font-sans',
        title: 'text-xl font-bold text-slate-800 tracking-tight',
        htmlContainer: 'text-sm text-slate-600 font-medium mt-2 leading-relaxed',
        confirmButton: 'px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition-all shadow-md mx-1',
        cancelButton: 'px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all mx-1',
        denyButton: 'px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl text-sm transition-all shadow-md mx-1'
    },
    buttonsStyling: false
});

/**
 * Hiển thị Popup Thông báo Thành công
 */
export const showSuccess = (title, text = '') => {
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
