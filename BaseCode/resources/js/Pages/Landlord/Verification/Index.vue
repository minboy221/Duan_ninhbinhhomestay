<script setup>
import { ref, onMounted } from "vue";
import { useForm, router } from "@inertiajs/vue3";

//hiển thị 3 phần giao diện xác minh các bước cho chủ trọ
import Step1 from "./Step1.vue";
import Step2 from "./Step2.vue";
import Step3 from "./Step3.vue";

const currentStep = ref(1);
const isModelsLoaded = ref(false);

onMounted(async () => {
    try {
        const faceapi = await import("face-api.js");
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
    ward: "",
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
    <div class="max-w-4xl mx-auto px-4 pt-12 pb-24 relative z-10">
        <!-- Step Progress Bar -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-4">
                <!-- Step 1 -->
                <div class="flex flex-col items-center gap-2 group cursor-pointer" @click="currentStep = 1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300"
                        :class="currentStep === 1
                                ? 'bg-primary text-on-primary shadow-lg shadow-primary/20'
                                : currentStep > 1
                                    ? 'bg-primary/20 text-primary'
                                    : 'bg-surface-container-high text-on-surface-variant'
                            ">
                        <span v-if="currentStep > 1" class="material-symbols-outlined text-sm font-bold">check</span>
                        <span v-else>1</span>
                    </div>
                    <span class="text-sm transition-all duration-300"
                        :class="currentStep >= 1 ? 'font-semibold text-primary' : 'font-medium text-on-surface-variant'">
                        Xác minh
                    </span>
                </div>

                <div class="flex-grow h-[2px] mx-4 mb-6 transition-all duration-300"
                    :class="currentStep > 1 ? 'bg-primary' : 'bg-surface-container-highest'"></div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center gap-2 group cursor-pointer"
                    @click="currentStep > 1 ? currentStep = 2 : null">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300"
                        :class="currentStep === 2
                                ? 'bg-primary text-on-primary shadow-lg shadow-primary/20'
                                : currentStep > 2
                                    ? 'bg-primary/20 text-primary'
                                    : 'bg-surface-container-high text-on-surface-variant'
                            ">
                        <span v-if="currentStep > 2" class="material-symbols-outlined text-sm font-bold">check</span>
                        <span v-else>2</span>
                    </div>
                    <span class="text-sm transition-all duration-300"
                        :class="currentStep >= 2 ? 'font-semibold text-primary' : 'font-medium text-on-surface-variant'">
                        Thông tin chỗ ở
                    </span>
                </div>

                <div class="flex-grow h-[2px] mx-4 mb-6 transition-all duration-300"
                    :class="currentStep > 2 ? 'bg-primary' : 'bg-surface-container-highest'"></div>

                <!-- Step 3 -->
                <div class="flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300"
                        :class="currentStep === 3
                                ? 'bg-primary text-on-primary shadow-lg shadow-primary/20'
                                : 'bg-surface-container-high text-on-surface-variant'
                            ">
                        3
                    </div>
                    <span class="text-sm transition-all duration-300"
                        :class="currentStep === 3 ? 'font-semibold text-primary' : 'font-medium text-on-surface-variant'">
                        Hoàn tất
                    </span>
                </div>
            </div>
        </div>

        <!-- STEP 1 -->
        <Step1 v-show="currentStep === 1" :form="form" @next="nextStep" />

        <!-- STEP 2 -->
        <Step2 v-show="currentStep === 2" :form="form" @next="nextStep" @prev="prevStep" />

        <!-- STEP 3 -->
        <Step3 v-show="currentStep === 3" :form="form" :isModelsLoaded="isModelsLoaded" :currentStep="currentStep"
            @prev="prevStep" @submit="submitVerification" @go-to-step1="currentStep = 1" />

        <!-- Popup Thông Báo (Success / Error) -->
        <Teleport to="body">
            <div v-if="popup.show"
                class="fixed inset-0 bg-black/60 z-[99999] flex items-center justify-center backdrop-blur-md transition-all duration-300"
                @click.self="closePopup">
                <div
                    class="bg-white rounded-2xl p-10 max-w-[420px] w-[90%] text-center shadow-2xl scale-100 animate-popupIn border border-slate-100">
                    <!-- Icon Trạng Thái -->
                    <div :class="popup.type === 'error' ? 'bg-gradient-to-br from-red-500 to-red-600 shadow-red-500/20' : 'bg-gradient-to-br from-green-500 to-green-600 shadow-green-500/20'"
                        class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 shadow-xl">
                        <i :class="popup.type === 'error' ? 'bi bi-x-circle' : 'bi bi-check2-circle'"
                            class="text-white text-4xl"></i>
                    </div>

                    <!-- Tiêu đề & Nội dung -->
                    <h3 class="text-2xl font-extrabold text-slate-800 mb-3">{{ popup.title }}</h3>
                    <p class="text-sm font-medium text-slate-500 mb-8 leading-relaxed">{{ popup.message }}</p>

                    <!-- Nút Đóng -->
                    <div class="flex gap-4 justify-center">
                        <button @click="closePopup"
                            :class="popup.type === 'error' ? 'bg-gradient-to-br from-red-500 to-red-600 hover:scale-[1.02] active:scale-[0.98] shadow-red-500/10' : 'bg-gradient-to-br from-primary to-primary-container hover:scale-[1.02] active:scale-[0.98] shadow-primary/10'"
                            class="flex-1 py-4 rounded-xl text-white font-bold transition-all shadow-lg text-sm">
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
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }

    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-popupIn {
    animation: popupIn 0.3s ease-out forwards;
}
</style>
