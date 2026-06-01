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

        statusColor.value = "bg-sky-600";
    } catch (error) {
        statusMsg.value = "Vui lòng cấp quyền camera";

        statusColor.value = "bg-red-500";
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

        statusColor.value = "bg-red-500";

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

        statusColor.value = "bg-red-500";

        return;
    }

    const faceMatcher = new faceapi.FaceMatcher(idDetection.descriptor, 0.5);

    const displaySize = {
        width: videoRef.value.videoWidth,
        height: videoRef.value.videoHeight,
    };

    faceapi.matchDimensions(canvasRef.value, displaySize);

    statusMsg.value = "Đưa khuôn mặt vào khung hình";

    statusColor.value = "bg-sky-600";

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

        faceapi.draw.drawDetections(canvasRef.value, resized);

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

            statusColor.value = "bg-red-500";
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
    <div class="space-y-10">
        <!-- Header -->
        <div class="text-center">
            <h1 class="text-4xl font-extrabold text-slate-800 mb-3">
                Xác minh khuôn mặt
            </h1>

            <p class="text-slate-500 text-lg">
                Đưa khuôn mặt vào khung hình để xác minh với CCCD.
            </p>
        </div>

        <div
            class="bg-white rounded-[32px] shadow-xl border border-slate-100 overflow-hidden grid lg:grid-cols-12"
        >
            <!-- LEFT -->
            <div class="lg:col-span-8 p-8 flex flex-col items-center">
                <div class="relative w-full max-w-[520px]">
                    <!-- status badge -->
                    <div
                        class="absolute top-5 left-5 z-20 px-4 py-2 rounded-full bg-white/90 backdrop-blur-md shadow-lg flex items-center gap-2"
                    >
                        <span
                            class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"
                        />

                        <span class="text-sm font-semibold text-slate-700">
                            Camera sẵn sàng
                        </span>
                    </div>

                    <!-- camera -->
                    <div
                        class="relative overflow-hidden rounded-[40px] bg-black shadow-2xl"
                    >
                        <video
                            ref="videoRef"
                            @play="onVideoPlay"
                            autoplay
                            muted
                            playsinline
                            class="w-full aspect-[4/5] object-cover"
                        />

                        <canvas
                            ref="canvasRef"
                            class="absolute inset-0 w-full h-full pointer-events-none"
                        />

                        <!-- face scan -->
                        <div
                            class="absolute inset-0 flex items-center justify-center pointer-events-none"
                        >
                            <div class="relative w-[260px] h-[340px]">
                                <!-- glow -->
                                <div
                                    class="absolute inset-0 rounded-[50%] border-[3px] border-sky-400 shadow-[0_0_40px_rgba(14,165,233,0.5)]"
                                />

                                <!-- scan line -->
                                <div
                                    class="absolute left-0 right-0 h-[3px] bg-gradient-to-r from-transparent via-sky-400 to-transparent animate-scan"
                                />

                                <!-- corners -->
                                <div
                                    class="absolute top-0 left-0 w-10 h-10 border-t-4 border-l-4 border-sky-400 rounded-tl-xl"
                                />
                                <div
                                    class="absolute top-0 right-0 w-10 h-10 border-t-4 border-r-4 border-sky-400 rounded-tr-xl"
                                />
                                <div
                                    class="absolute bottom-0 left-0 w-10 h-10 border-b-4 border-l-4 border-sky-400 rounded-bl-xl"
                                />
                                <div
                                    class="absolute bottom-0 right-0 w-10 h-10 border-b-4 border-r-4 border-sky-400 rounded-br-xl"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- status -->
                <div
                    :class="[
                        statusColor,
                        'mt-6 px-6 py-4 rounded-2xl text-white font-semibold text-center w-full max-w-lg shadow-lg',
                    ]"
                >
                    {{ statusMsg }}
                </div>
            </div>

            <!-- RIGHT -->
            <div
                class="lg:col-span-4 bg-slate-50 border-l border-slate-100 p-8"
            >
                <h3
                    class="text-sm font-bold uppercase tracking-widest text-sky-700 mb-6"
                >
                    Lưu ý xác minh
                </h3>

                <div class="space-y-5">
                    <div class="flex gap-3">
                        <span class="material-symbols-outlined text-sky-600">
                            check_circle
                        </span>

                        <p class="text-slate-600">
                            Không đeo khẩu trang hoặc kính râm
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <span class="material-symbols-outlined text-sky-600">
                            light_mode
                        </span>

                        <p class="text-slate-600">Đảm bảo đủ ánh sáng</p>
                    </div>

                    <div class="flex gap-3">
                        <span class="material-symbols-outlined text-sky-600">
                            person
                        </span>
                        <p class="text-slate-600">
                            Chỉ có một người trong khung hình
                        </p>
                    </div>
                </div>

                <div class="border-t border-slate-200 mt-10 pt-8">
                    <button
                        type="button"
                        @click="emit('prev')"
                        class="w-full h-14 rounded-full border-2 border-sky-700 text-sky-700 font-bold hover:bg-sky-50 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">
                            arrow_back
                        </span>

                        Quay lại
                    </button>

                    <button
                        disabled
                        class="w-full h-14 rounded-full bg-slate-200 text-slate-500 font-semibold mt-4"
                    >
                        Hệ thống tự động nộp sau khi xác minh
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes scan {
    0% {
        top: 10%;
        opacity: 0;
    }

    50% {
        opacity: 1;
    }

    100% {
        top: 90%;
        opacity: 0;
    }
}

.animate-scan {
    animation: scan 2.5s infinite ease-in-out;
}
</style>
