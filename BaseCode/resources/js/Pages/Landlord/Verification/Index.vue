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
form.post(route("landlord.verify.store"), {});
//hàm xử lý AI và submit ở bước cuối cùng
const submitVerification = () => {
    form.post(route("landlord.verify.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            // Chỉ báo thành công nếu KHÔNG CÓ LỖI CHÍNH TẢ/VALIDATE nào
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
    <div class="max-w-5xl mx-auto px-4 py-10">
        <!-- STEP UI -->
        <div class="mb-16">
            <div class="flex items-start justify-between relative">
                <!-- line -->
                <div class="absolute top-7 left-[7%] right-[7%] h-[3px] bg-gray-300 z-0"></div>

                <!-- Step 1 -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl transition-all duration-300"
                        :class="currentStep >= 1
                                ? 'bg-[#005F87] text-white shadow-md'
                                : 'bg-[#D9D9D9] text-gray-700'
                            ">
                        1
                    </div>
                    <p class="mt-4 text-lg" :class="currentStep >= 1
                            ? 'text-[#005F87] font-semibold'
                            : 'text-gray-700 font-medium'
                        ">
                        Xác minh
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl transition-all duration-300"
                        :class="currentStep >= 2
                                ? 'bg-[#005F87] text-white shadow-md'
                                : 'bg-[#D9D9D9] text-gray-700'
                            ">
                        2
                    </div>
                    <p class="mt-4 text-lg" :class="currentStep >= 2
                            ? 'text-[#005F87] font-semibold'
                            : 'text-gray-700 font-medium'
                        ">
                        Thông tin trọ
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl transition-all duration-300"
                        :class="currentStep >= 3
                                ? 'bg-[#005F87] text-white shadow-md'
                                : 'bg-[#D9D9D9] text-gray-700'
                            ">
                        3
                    </div>
                    <p class="mt-4 text-lg" :class="currentStep >= 3
                            ? 'text-[#005F87] font-semibold'
                            : 'text-gray-700 font-medium'
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
