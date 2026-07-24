//Phần xử lý nén ảnh
export function compressImage(file,{maxWidth = 1200, maxHeight = 1200, quanlity = 0.7} = {}) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                // Tính toán tỷ lệ co giãn để không vượt quá kích thước tối đa
                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                // Chuyển canvas thành file Blob chất lượng mong muốn (ví dụ 0.7 = 70%)
                canvas.toBlob(
                    (blob) => {
                        if (blob) {
                            const compressedFile = new File([blob], file.name, {
                                type: file.type,
                                lastModified: Date.now(),
                            });
                            resolve(compressedFile);
                        } else {
                            reject(new Error('Nén ảnh thất bại'));
                        }
                    },
                    file.type || 'image/jpeg',
                    quality
                );
            };
        };
        reader.onerror = (error) => reject(error);
    });
}