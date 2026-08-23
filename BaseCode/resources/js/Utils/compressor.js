/**
 * Utility nén ảnh Client-side bằng HTML5 Canvas.
 * Hỗ trợ an toàn cho ảnh .HEIC / .HEIF của iPhone.
 */
export function compressImage(
    file,
    { maxWidth = 1200, maxHeight = 1200, quality = 0.7 } = {}
) {
    return new Promise((resolve) => {
        if (!file) return resolve(file);

        const fileName = (file.name || '').toLowerCase();
        const isHeic = fileName.endsWith('.heic') || fileName.endsWith('.heif') || file.type === 'image/heic' || file.type === 'image/heif';

        // Nếu không phải file ảnh hoặc là ảnh HEIC của iPhone -> Giữ nguyên file cho Backend nén
        if ((!file.type.startsWith("image/") && !isHeic) || isHeic) {
            return resolve(file);
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement("canvas");
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((height * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        if (blob) {
                            const compressedFile = new File([blob], file.name, {
                                type: file.type || 'image/jpeg',
                                lastModified: Date.now(),
                            });
                            resolve(compressedFile);
                        } else {
                            resolve(file);
                        }
                    },
                    file.type || 'image/jpeg',
                    quality
                );
            };
            img.onerror = () => resolve(file);
        };
        reader.onerror = () => resolve(file);
    });
}

/**
 * Nén hàng loạt nhiều ảnh cùng lúc
 */
export async function compressMultipleImages(files, options = {}) {
    if (!files || !Array.isArray(files)) return [];
    return await Promise.all(files.map((file) => compressImage(file, options)));
}
