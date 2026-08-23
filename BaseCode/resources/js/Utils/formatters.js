//định dạng tiền sang chuẩn việt nam
export const formatMoney = (n) => {
    return new Intl.NumberFormat("vi-VN").format(n || 0) + "đ";
};

//tính khoảng thời gian tương đối
export const timeAgo = (date) => {
    if(!date) return '';
    const diff = Math.floor((new Date() - new Date(date)) / 1000);
    if(diff < 60) return `${diff} giây trước`;
    if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`;
    return `${Math.floor(diff / 86400)} ngày trước`;
};