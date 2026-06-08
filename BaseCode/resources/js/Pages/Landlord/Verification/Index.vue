<script setup>
import { ref, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
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
        console.error("lỗi khi tải model:", error);
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

//hàm xử lý AI và submit ở bước cuối cùng
const submitVerification = () => {
    form.post(route("landlord.verify.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (Object.keys(form.errors).length === 0) {
                alert("Hoàn tất! Đã gửi hồ sơ xác minh.");
            }
        },
        onError: (errors) => {
            console.error("LỖI DỮ LIỆU TỪ LARAVEL:", errors);
            alert(
                "Dữ liệu chưa hợp lệ! Vui lòng bấm F12 (tab Console) để xem chi tiết.",
            );
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
                <div class="absolute top-7 left-[10%] right-[10%] h-[2px] bg-slate-100 z-0"></div>

                <!-- Step 1 -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 border"
                        :class="currentStep >= 1
                                ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/10'
                                : 'bg-white text-slate-400 border-slate-200'
                            ">
                        1
                    </div>
                    <p class="mt-3 text-xs font-bold" :class="currentStep >= 1
                            ? 'text-emerald-600'
                            : 'text-slate-400'
                        ">
                        Xác minh
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 border"
                        :class="currentStep >= 2
                                ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/10'
                                : 'bg-white text-slate-400 border-slate-200'
                            ">
                        2
                    </div>
                    <p class="mt-3 text-xs font-bold" :class="currentStep >= 2
                            ? 'text-emerald-600'
                            : 'text-slate-400'
                        ">
                        Thông tin trọ
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 border"
                        :class="currentStep >= 3
                                ? 'bg-emerald-500 text-white border-emerald-500 shadow-md shadow-emerald-500/10'
                                : 'bg-white text-slate-400 border-slate-200'
                            ">
                        3
                    </div>
                    <p class="mt-3 text-xs font-bold" :class="currentStep >= 3
                            ? 'text-emerald-600'
                            : 'text-slate-400'
                        ">
                        Khuôn mặt
                    </p>
                </div>
            </div>
        </div>

        <!-- STEP 1 -->
        <Step1 v-show="currentStep === 1" :form="form" @next="nextStep" />

        <!-- STEP 2 -->
        <Step2 v-show="currentStep === 2" :form="form" @next="nextStep" @prev="prevStep" />

        <!-- STEP 3 -->
        <Step3 v-show="currentStep === 3" :form="form" :isModelsLoaded="isModelsLoaded" :currentStep="currentStep"
            @prev="prevStep" @submit="submitVerification" />
    </div>
</template>
