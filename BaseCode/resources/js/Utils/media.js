//chuẩn hoá url ảnh đại diện người dùng
export const getAvatarUrl = (avatar) => {
    if(!avatar || typeof avatar !== 'string' || !avatar.trim())
        return '/anh/banner.png';
    const trimmed = avatar.trim();
    if(trimmed.startsWith('http://') ||trimmed.startsWith('https://') || trimmed.startsWith('data:')){
        return trimmed;
    }
    if(trimmed.startsWith('/storage/'))
        return trimmed;
    if(trimmed.startsWith('storage/'))
        return '/' + trimmed;
    if(trimmed.startsWith('/'))
        return '/storage/' + trimmed;
};

//chuẩn hoá URL ảnh bìa phòng trọ
export const getRoomImageUrl = (images) => {
    if(!images) return '/anh/banner_tro.png';
    let firstImg = Array.isArray(images) ? (images[0] || '') : images;
    if(typeof firstImg !== 'string' || !firstImg.trim())
        return '/anh/banner_tro.png';
    const trimmed = firstImg.trim();
    if(trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:')){
        return trimmed;
    }
    if(trimmed.startsWith('/storage/'))
        return trimmed;
    if(trimmed.startsWith('storage/'))
        return '/' + trimmed;
    if(trimmed.startsWith('/'))
        return trimmed;
    return '/storage/' + trimmed;
};