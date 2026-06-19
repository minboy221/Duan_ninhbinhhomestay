<script setup>
import { ref, onMounted } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import * as faceapi from "face-api.js";

//hiển thị 3 phần giao diện xác minh các bước cho chủ trọ
import Step1 from "./Step1.vue";
import Step2 from "./Step2.vue";
import Step3 from "./Step3.vue";

const currentStep = ref(1);
const isModelsLoaded = ref(false);

onMounted(async () => {
    try {
        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri("/models"),
            faceapi.nets.faceLandmark68Net.loadFromUri("/models"),
            faceapi.nets.faceRecognitionNet.loadFromUri("/models"),
            faceapi.nets.tinyFaceDetector.loadFromUri("/models"),
        ]);
        isModelsLoaded.value = true;
    } catch (error) {
        console.error("Lỗi khi tải model:", error);
    }
});

//khởi tại form chứa toàn bộ dữ liệu của 3 bước
const form = useForm({
    phone: "",
    id_card_number: "",
    id_card_front: null,
    id_card_back: null,
    property_name: "",
    district: "",
    address_detail: "",
    contract_detail: "",
    contract_images: [],
    room_images: [],
    latitude: null,
    longitude: null,
    face_auth_image: null,
    is_face_matched: false,
});

//phần điều hướng
const nextStep = () => {
    if (currentStep.value < 3) currentStep.value++;
};
const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

// Custom Popup State
const popup = ref({
    show: false,
    type: 'success', // 'success' or 'error'
    title: '',
    message: '',
    onClose: null
});

const closePopup = () => {
    popup.value.show = false;
    if (popup.value.onClose) {
        popup.value.onClose();
    }
};

//hàm xử lý AI và submit ở bước cuối cùng
const submitVerification = () => {
    form.post(route("landlord.verify.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (Object.keys(form.errors).length === 0) {
                popup.value = {
                    show: true,
                    type: 'success',
                    title: 'Thành Công!',
                    message: 'Hoàn tất! Đã gửi hồ sơ xác minh. Vui lòng chờ Admin phê duyệt.',
                    onClose: () => {
                        router.visit(route('home'));
                    }
                };
            }
        },
        onError: (errors) => {
            console.error("LỖI DỮ LIỆU TỪ LARAVEL:", errors);
            
            // Lấy lỗi đầu tiên để hiển thị cho user
            let errorMessage = "Dữ liệu chưa hợp lệ! Vui lòng kiểm tra lại thông tin bạn đã nhập.";
            const firstErrorKey = Object.keys(errors)[0];
            if (firstErrorKey) {
                errorMessage = errors[firstErrorKey];
            }

            popup.value = {
                show: true,
                type: 'error',
                title: 'Lỗi Xác Minh!',
                message: errorMessage
            };
            form.processing = false;
        },
    });
};
</script>

<template>
    <div class="max-w-4xl mx-auto px-4 py-12">
        <!-- STEP UI -->
        <div class="mb-12">
            <div class="flex items-start justify-between relative">
                <!-- line -->
                <div
                    class="absolute top-7 left-[10%] right-[10%] h-[2px] bg-slate-100 z-0"
                ></div>

                <!-- Step 1 -->
                <div class="flex flex-col items-center relative z-10">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 border"
                        :class="
                            currentStep >= 1
                                ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/10'
                                : 'bg-white text-slate-400 border-slate-200'
                        "
                    >
                        1
                    </div>
                    <p
                        class="mt-3 text-xs font-bold"
                        :class="
                            currentStep >= 1
                                ? 'text-emerald-600'
                                : 'text-slate-400'
                        "
                    >
                        Xác minh
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center relative z-10">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 border"
                        :class="
                            currentStep >= 2
                                ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/10'
                                : 'bg-white text-slate-400 border-slate-200'
                        "
                    >
                        2
                    </div>
                    <p
                        class="mt-3 text-xs font-bold"
                        :class="
                            currentStep >= 2
                                ? 'text-emerald-600'
                                : 'text-slate-400'
                        "
                    >
                        Thông tin trọ
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="flex flex-col items-center relative z-10">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 border"
                        :class="
                            currentStep >= 3
                                ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/10'
                                : 'bg-white text-slate-400 border-slate-200'
                        "
                    >
                        3
                    </div>
                    <p
                        class="mt-3 text-xs font-bold"
                        :class="
                            currentStep >= 3
                                ? 'text-emerald-600'
                                : 'text-slate-400'
                        "
                    >
                        Khuôn mặt
                    </p>
                </div>
            </div>
        </div>

        <!-- STEP 1 -->
        <Step1 v-show="currentStep === 1" :form="form" @next="nextStep" />

        <!-- STEP 2 -->
        <Step2
            v-show="currentStep === 2"
            :form="form"
            @next="nextStep"
            @prev="prevStep"
        />

        <!-- STEP 3 -->
        <Step3
            v-show="currentStep === 3"
            :form="form"
            :isModelsLoaded="isModelsLoaded"
            :currentStep="currentStep"
            @prev="prevStep"
            @submit="submitVerification"
        />
        
        <!-- Popup Thông Báo (Success / Error) -->
        <Teleport to="body">
            <div v-if="popup.show" class="modal-overlay" style="position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);" @click.self="closePopup">
                <div style="background: white; border-radius: 16px; padding: 40px 30px; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: popupIn 0.3s ease-out;">
                    <!-- Icon Trạng Thái -->
                    <div :style="popup.type === 'error' ? 'width: 80px; height: 80px; background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 10px 15px -3px rgba(239,68,68,0.3);' : 'width: 80px; height: 80px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 10px 15px -3px rgba(34,197,94,0.3);'">
                        <i :class="popup.type === 'error' ? 'bi bi-x-circle' : 'bi bi-check2-circle'" style="color: white; font-size: 40px;"></i>
                    </div>
                    
                    <!-- Tiêu đề & Nội dung -->
                    <h3 style="font-size: 24px; font-weight: 800; color: #1e293b; margin-bottom: 12px; font-family: 'Inter', sans-serif;">{{ popup.title }}</h3>
                    <p style="font-size: 15px; color: #64748b; margin-bottom: 30px; line-height: 1.6;">{{ popup.message }}</p>
                    
                    <!-- Nút Đóng -->
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button @click="closePopup" :style="popup.type === 'error' ? 'flex: 1; padding: 12px 0; border-radius: 10px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; font-weight: 600; cursor: pointer; transition: all 0.2s;' : 'flex: 1; padding: 12px 0; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; font-weight: 600; cursor: pointer; transition: all 0.2s;'">
                            Đóng Lại
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@keyframes popupIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
