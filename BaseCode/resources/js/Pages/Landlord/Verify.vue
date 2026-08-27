<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import * as faceapi from 'face-api.js'; // Import thư viện AI
import { showSuccess, showError, showWarning } from '@/Utils/swal';

const currentStep = ref(1);
const isModelsLoaded = ref(false);
const faceMatchResult = ref(null); // Lưu kết quả so sánh: true/false
const form = useForm({
    //thêm các trường dữ liệu
    is_face_matched: false, //trường này sẽ gửi kết quả đến back-end
});

//tải model AI khi trang vừa load
onMounted(async () => {
    try {
        //đường dẫn '/models' trỏ vào thư mục của public/models của laravel
        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri('/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
            faceapi.nets.faceExpressionNet.loadFromUri('/models')
        ]);
        isModelsLoaded.value = true;
        console.log('Đã tải xong AI models!');
    } catch (error) {
        console.error('lỗi khi tải model:', error);
    }
});

//phần hàm xử lý so sánh 2 ảnh ở bước 3
const compareFaces = async (idCardFile, selfieFile) => {
    if (!isModelsLoaded.value) {
        showWarning('Đang khởi động', 'Hệ thống AI đang khởi động, vui lòng đợi trong giây lát!');
        return;
    }
    // Tạo các thẻ <img> ảo trong bộ nhớ để AI có thể đọc được ảnh từ File
    const img1 = await faceapi.bufferToImage(idCardFile);
    const img2 = await faceapi.bufferToImage(selfieFile);
    //phát hiện khuôn mặt
    const detection1 = await faceapi.detectSingleFace(img1).withFaceLandmarks().withFaceDescriptor();
    const detection2 = await faceapi.detectSingleFace(img2).withFaceLandmarks().withFaceDescriptor();

    if (!detection1) {
        showError('Lỗi xác minh', 'Không tìm thấy khuôn mặt trên ảnh CCCD!');
        return false;
    }
    if (!detection2) {
        showError('Lỗi xác minh', 'Không tìm thấy khuôn mặt trên ảnh chụp thực tế!');
        return false;
    }
    //tính khoảng cách giữa 2 khuôn mặt
    const distance = faceapi.euclideanDistance(detection1.descriptor, detection2.descriptor);
    //ngưỡng tiêu chuẩn của xác minh khuôn mặt
    if (distance < 0.6) {
        console.log('khuôn mặt trùng khớp!');
        return true;
    } else {
        console.log('Khuôn mặt không trùng khớp!');
        return false;
    }
};
//cập nhật hàm submit ở bước 3
const submitVerification = async () => {
    //chạy AI so sánh trước khi gửi dữ liệu lên backend
    form.processing = true;
    const isMatched = await compareFaces(form.id_card__front, form.face_auth_image);
    //cập nhật kết quả vào form
    form.is_face_matched = isMatched;
    form.processing = false;

    if (!isMatched) {
        showError('Xác minh thất bại', 'Khuôn mặt không khớp với CCCD. Vui lòng chụp lại!');
        return;
    }
    //nếu khớp, gửi toàn bộ dữ liệu xuống backend
    form.post(route('landlord.verify.store'), {
        preserveScroll: true,
        onSuccess: () => showSuccess('Thành công', 'Đã gửi hồ sơ xác minh thành công!'),
    });
};
</script>