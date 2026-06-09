<script setup>
import {
    ref,
    onMounted,
    onUnmounted,
    defineProps,
    defineEmits,
    watch,
} from "vue";

import * as faceapi from "face-api.js";

const props = defineProps({
    form: Object,
    isModelsLoaded: Boolean,
    currentStep: Number,
});

const emit = defineEmits(["prev", "submit"]);

const videoRef = ref(null);
const canvasRef = ref(null);

const statusMsg = ref("Đang chờ khởi tạo hệ thống...");
const statusColor = ref("bg-slate-500");
const isMatched = ref(false);

let scanInterval = null;

/**
 * Mở camera
 */
const startCamera = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: "user",
            },
            audio: false,
        });

        videoRef.value.srcObject = stream;
        statusMsg.value = "Đang khởi động camera...";
        statusColor.value = "bg-emerald-600";
    } catch (error) {
        statusMsg.value = "Vui lòng cấp quyền camera";
        statusColor.value = "bg-rose-500";
    }
};

/**
 * Tắt camera
 */
const stopCamera = () => {
    if (scanInterval) {
        clearInterval(scanInterval);
    }

    if (videoRef.value?.srcObject) {
        videoRef.value.srcObject.getTracks().forEach((track) => track.stop());
    }
};

/**
 * Chụp ảnh & submit
 */
const captureAndSubmit = () => {
    const canvas = document.createElement("canvas");
    canvas.width = videoRef.value.videoWidth;
    canvas.height = videoRef.value.videoHeight;
    canvas.getContext("2d").drawImage(videoRef.value, 0, 0);

    canvas.toBlob((blob) => {
        const file = new File([blob], "face_capture.jpg", {
            type: "image/jpeg",
        });

        props.form.face_auth_image = file;
        props.form.is_face_matched = true;
        stopCamera();
        emit("submit");
    }, "image/jpeg");
};

/**
 * AI xử lý khuôn mặt
 */
const onVideoPlay = async () => {
    if (!props.isModelsLoaded) {
        statusMsg.value = "AI đang tải dữ liệu...";
        return;
    }

    if (!props.form.id_card_front) {
        statusMsg.value = "Bạn chưa tải CCCD ở bước 1";
        statusColor.value = "bg-rose-500";
        return;
    }

    statusMsg.value = "Đang phân tích CCCD...";
    statusColor.value = "bg-amber-500";

    const idImg = await faceapi.bufferToImage(props.form.id_card_front);
    const idDetection = await faceapi
        .detectSingleFace(idImg, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();

    if (!idDetection) {
        statusMsg.value = "Không nhận diện được khuôn mặt trên CCCD";
        statusColor.value = "bg-rose-500";
        return;
    }

    const faceMatcher = new faceapi.FaceMatcher(idDetection.descriptor, 0.4);


    const displaySize = {
        width: videoRef.value.videoWidth,
        height: videoRef.value.videoHeight,
    };

    faceapi.matchDimensions(canvasRef.value, displaySize);
    statusMsg.value = "Đưa khuôn mặt vào khung hình";
    statusColor.value = "bg-emerald-600";

    scanInterval = setInterval(async () => {
        if (isMatched.value) {
            return;
        }

        const detections = await faceapi
            .detectAllFaces(
                videoRef.value,
                new faceapi.TinyFaceDetectorOptions(),
            )
            .withFaceLandmarks()
            .withFaceDescriptors();

        const resized = faceapi.resizeResults(detections, displaySize);
        const ctx = canvasRef.value.getContext("2d");
        ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);

        // faceapi.draw.drawDetections(canvasRef.value, resized);

        if (resized.length === 0) {
            statusMsg.value = "Không tìm thấy khuôn mặt";
            statusColor.value = "bg-amber-500";
            return;
        }

        let foundMatch = false;
        for (const detection of resized) {
            const match = faceMatcher.findBestMatch(detection.descriptor);
            if (match.label !== "unknown") {
                foundMatch = true;
                isMatched.value = true;
                statusMsg.value = "Xác minh thành công";
                statusColor.value = "bg-emerald-500";
                clearInterval(scanInterval);
                captureAndSubmit();
                break;
            }
        }

        if (!foundMatch && !isMatched.value) {
            statusMsg.value = "Khuôn mặt không khớp CCCD";
            statusColor.value = "bg-rose-500";
        }
    }, 1000);
};

watch(
    () => props.currentStep,
    (step) => {
        if (step === 3) {
            startCamera();
        } else {
            stopCamera();
        }
    },
);

watch(
    () => props.isModelsLoaded,
    (loaded) => {
        if (loaded && props.currentStep === 3) {
            onVideoPlay();
        }
    },
);

onMounted(() => {
    if (props.currentStep === 3) {
        startCamera();
    }
});

onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div class="text-center space-y-2">
            <h1 class="text-lg font-bold text-slate-800">Xác minh khuôn mặt</h1>
            <p class="text-xs text-slate-400">Đưa khuôn mặt của bạn đối chiếu song song với hình ảnh trên CCCD</p>
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden grid grid-cols-1 md:grid-cols-12 shadow-sm">
            <!-- Left Camera Stream -->
            <div class="md:col-span-8 p-6 flex flex-col items-center justify-center space-y-4">
                <div class="relative w-full max-w-[420px] aspect-[4/5] bg-black rounded-2xl overflow-hidden shadow-inner">
                    <!-- camera tag -->
                    <video
                        ref="videoRef"
                        @play="onVideoPlay"
                        autoplay
                        muted
                        playsinline
                        class="w-full h-full object-cover"
                    />

                    <canvas
                        ref="canvasRef"
                        class="absolute inset-0 w-full h-full pointer-events-none"
                    />

                    <!-- Face scanning guide overlay -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="relative w-[200px] h-[260px] rounded-full border-2 border-emerald-400/50 shadow-[0_0_20px_rgba(16,185,129,0.3)]">
                            <!-- corner indicators -->
                            <div class="absolute top-0 left-0 w-6 h-6 border-t-2 border-l-2 border-emerald-400 rounded-tl-lg"></div>
                            <div class="absolute top-0 right-0 w-6 h-6 border-t-2 border-r-2 border-emerald-400 rounded-tr-lg"></div>
                            <div class="absolute bottom-0 left-0 w-6 h-6 border-b-2 border-l-2 border-emerald-400 rounded-bl-lg"></div>
                            <div class="absolute bottom-0 right-0 w-6 h-6 border-b-2 border-r-2 border-emerald-400 rounded-br-lg"></div>
                            
                            <!-- scanning line -->
                            <div class="absolute left-0 right-0 h-[1.5px] bg-emerald-400 animate-scan"></div>
                        </div>
                    </div>
                </div>

                <!-- status notifier -->
                <div :class="[statusColor, 'px-4 py-2.5 rounded-xl text-white font-bold text-xs text-center w-full max-w-[420px] transition-all']">
                    {{ statusMsg }}
                </div>
            </div>

            <!-- Right Notice Column -->
            <div class="md:col-span-4 bg-slate-50/50 p-6 flex flex-col justify-between border-t md:border-t-0 md:border-l border-slate-100">
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-2 flex items-center gap-1">
                        <i class="bi bi-info-circle text-emerald-500"></i>
                        Lưu ý quan trọng
                    </h3>
                    <ul class="space-y-3 text-xs text-slate-500 font-semibold">
                        <li class="flex items-center gap-2">
                            <i class="bi bi-check-circle text-emerald-500"></i> Không đeo kính, khẩu trang
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="bi bi-check-circle text-emerald-500"></i> Đảm bảo môi trường đủ sáng
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="bi bi-check-circle text-emerald-500"></i> Giữ điện thoại cố định
                        </li>
                    </ul>
                </div>

                <div class="pt-6 border-t border-slate-100 space-y-2">
                    <button
                        type="button"
                        @click="emit('prev')"
                        class="w-full px-4 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1"
                    >
                        <span class="material-symbols-outlined">
                            arrow_back
                        </span>

                        Quay lại
                    </button>

                    <button disabled class="w-full px-4 py-2.5 bg-slate-100 text-slate-400 font-bold text-xs rounded-xl cursor-not-allowed">
                        Tự động nộp hồ sơ khi khớp ảnh
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes scan {
    0% { top: 10%; opacity: 0; }
    50% { opacity: 1; }
    100% { top: 90%; opacity: 0; }
}
.animate-scan {
    animation: scan 2.5s infinite ease-in-out;
}
</style>
