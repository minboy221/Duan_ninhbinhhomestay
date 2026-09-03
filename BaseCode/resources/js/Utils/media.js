export const DEFAULT_AVATAR = "data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2394a3b8'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z'/%3E%3C/svg%3E";

const R2_PUBLIC_URL = (import.meta.env.VITE_CLOUDFLARE_R2_PUBLIC_URL || "https://pub-92575b53d7cd45c9bbfa66443ccd4f2f.r2.dev").replace(/\/$/, "");

// Hàm chuyển hóa bất kỳ đường dẫn ảnh nào sang URL công khai của Cloudflare R2
export const getImageUrl = (path, fallback = "") => {
    if (!path || typeof path !== "string" || !path.trim()) return fallback;
    const trimmed = path.trim();
    if (trimmed.startsWith("http://") || trimmed.startsWith("https://") || trimmed.startsWith("data:")) {
        return trimmed;
    }
    // Nếu là file tĩnh cục bộ trong thư mục public/anh/
    if (trimmed.startsWith("/anh/") || trimmed.startsWith("anh/")) {
        return trimmed.startsWith("/") ? trimmed : "/" + trimmed;
    }
    // Chuẩn hóa loại bỏ prefix storage/
    const cleanPath = trimmed.replace(/^\/?(storage\/)?/, "");
    if (R2_PUBLIC_URL && cleanPath) {
        return `${R2_PUBLIC_URL}/${cleanPath}`;
    }
    return trimmed.startsWith("/") ? trimmed : "/storage/" + cleanPath;
};

//chuẩn hoá url ảnh đại diện người dùng
export const getAvatarUrl = (avatar) => {
    if (!avatar || typeof avatar !== 'string' || !avatar.trim())
        return DEFAULT_AVATAR;
    return getImageUrl(avatar, DEFAULT_AVATAR);
};

//chuẩn hoá URL ảnh bìa phòng trọ
export const getRoomImageUrl = (images) => {
    if (!images) return '/anh/banner_tro.png';
    let firstImg = Array.isArray(images) ? (images[0] || '') : images;
    if (typeof firstImg !== 'string' || !firstImg.trim())
        return '/anh/banner_tro.png';
    return getImageUrl(firstImg, '/anh/banner_tro.png');
};