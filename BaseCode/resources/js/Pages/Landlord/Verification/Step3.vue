<script setup>
import {
    ref,
    onMounted,
    onUnmounted,
    defineProps,
    defineEmits,
    watch,
} from "vue";

let faceapi = null;
const getFaceApi = async () => {
    if (!faceapi) {
        const mod = await import("face-api.js");
        faceapi = mod.default || mod;
    }
    return faceapi;
};

const props = defineProps({
    form: Object,
    isModelsLoaded: Boolean,
    currentStep: Number,
});

const emit = defineEmits(["prev", "submit", "goToStep1"]);

const videoRef = ref(null);
const canvasRef = ref(null);

const statusMsg = ref("Đang chờ khởi tạo hệ thống...");
const statusColor = ref("bg-slate-500 text-white");
const isMatched = ref(false);

const failedMatchCount = ref(0);
let scanInterval = null;

/**
 * Mở camera
 */
const startCamera = async () => {
    try {
        if (!navigator?.mediaDevices?.getUserMedia) {
            statusMsg.value = "Trình duyệt không hỗ trợ mở camera (cần dùng HTTPS)";
            statusColor.value = "bg-rose-500 text-white";
            return;
        }

        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: "user",
            },
            audio: false,
        });

        if (videoRef.value) {
            videoRef.value.srcObject = stream;
            statusMsg.value = "Đang khởi động camera...";
            statusColor.value = "bg-emerald-600 text-white";
        }
    } catch (error) {
        statusMsg.value = "Vui lòng cấp quyền camera trong Cài đặt";
        statusColor.value = "bg-rose-500 text-white";
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
        videoRef.value.srcObject = null;
    }
};

/**
 * Chụp ảnh & submit
 */
const captureAndSubmit = () => {
    if (!videoRef.value) return;

    const canvas = document.createElement("canvas");
    const width = videoRef.value.videoWidth || 640;
    const height = videoRef.value.videoHeight || 480;
    canvas.width = width;
    canvas.height = height;

    const ctx = canvas.getContext("2d");
    // Lật ngược khung vẽ canvas để ảnh selfie lưu lại đúng chiều gương
    ctx.translate(width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(videoRef.value, 0, 0, width, height);

    canvas.toBlob(
        (blob) => {
            if (!blob) return;
            const file = new File([blob], "face_capture.jpg", {
                type: "image/jpeg",
            });

            // Gán file ảnh và đánh dấu đã khớp
            props.form.face_auth_image = file;
            props.form.is_face_matched = true;
            stopCamera();

            // Phát sự kiện để Index.vue tiến hành Submit
            emit("submit");
        },
        "image/jpeg",
        0.85
    );
};


// Biến giữ faceMatcher
let faceMatcher = null;
let displaySize = { width: 0, height: 0 };

/**
 * AI xử lý khuôn mặt
 */
const onVideoPlay = async () => {
    if (!props.isModelsLoaded) {
        statusMsg.value = "AI đang tải dữ liệu...";
        statusColor.value = "bg-amber-500 text-white";
        return;
    }

    if (!props.form.id_card_front) {
        statusMsg.value = "Bạn chưa tải CCCD ở bước 1";
        statusColor.value = "bg-rose-500 text-white";
        return;
    }

    statusMsg.value = "Đang phân tích CCCD...";
    statusColor.value = "bg-amber-500 text-white";

    try {
        const faceapi = await getFaceApi();
        const idImg = await faceapi.bufferToImage(props.form.id_card_front);
        const idDetection = await faceapi
            .detectSingleFace(
                idImg,
                new faceapi.SsdMobilenetv1Options({ minConfidence: 0.7 }),
            )
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!idDetection) {
            statusMsg.value = "Không nhận diện được khuôn mặt trên CCCD";
            statusColor.value = "bg-rose-500 text-white";
            return;
        }

        faceMatcher = new faceapi.FaceMatcher(idDetection.descriptor, 0.45);

        displaySize = {
            width: videoRef.value.videoWidth || 340,
            height: videoRef.value.videoHeight || 425,
        };

        if (canvasRef.value) {
            faceapi.matchDimensions(canvasRef.value, displaySize);
        }

        statusMsg.value = "Đưa khuôn mặt vào giữa khung hình";
        statusColor.value = "bg-emerald-600 text-white";

        // Bắt đầu quét liên tục mỗi giây
        startScanning();
    } catch (e) {
        console.error(e);
        statusMsg.value = "Lỗi khi xử lý nhận dạng khuôn mặt";
        statusColor.value = "bg-rose-500 text-white";
    }
};

const startScanning = () => {
    if (scanInterval) clearInterval(scanInterval);

    scanInterval = setInterval(async () => {
        if (isMatched.value || !videoRef.value || !faceMatcher) return;

        try {
            const faceapi = await getFaceApi();
            const detections = await faceapi
                .detectAllFaces(
                    videoRef.value,
                    new faceapi.TinyFaceDetectorOptions({
<<<<<<< HEAD
                        inputSize: 320,
                        scoreThreshold: 0.4,
=======
                        inputSize: 416,
                        scoreThreshold: 0.65,
>>>>>>> a5d242909cbdb77076c294474466cef862d7a2c2
                    }),
                )
                .withFaceLandmarks()
                .withFaceDescriptors();

            const resized = faceapi.resizeResults(detections, displaySize);

            if (canvasRef.value) {
                const ctx = canvasRef.value.getContext("2d");
                ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
            }

            if (resized.length === 0) {
                statusMsg.value = "Đưa khuôn mặt vào giữa khung hình";
                statusColor.value = "bg-amber-500 text-white";
                return;
            }

            // Kiểm tra độ khớp
            for (const detection of resized) {
                const match = faceMatcher.findBestMatch(detection.descriptor);
                if (match.label !== "unknown") {
                    isMatched.value = true;
                    statusMsg.value = "Xác minh thành công!";
                    statusColor.value = "bg-emerald-600 text-white";
                    clearInterval(scanInterval);

                    // Thực hiện chụp ảnh và gửi dữ liệu
                    captureAndSubmit();
                    break;
                }
            }

            if (!foundMatch && !isMatched.value) {
                failedMatchCount.value++;
                statusMsg.value = `Không khớp CCCD (${failedMatchCount.value}/5 lần)`;
                statusColor.value = "bg-rose-500 text-white";
                //nếu khuôn mặt xác minh không khớp sẽ hiển thị số lần
                //nếu sai 5 lần trở lên
                if (failedMatchCount.value >= 5) {
                    clearInterval(scanInterval);
                    alert("Xác minih khuôn mặt không trùng khớp quá 5 lần! vui lòng quay lại các bước để kiểm tra lại thông tin.");
                    stopCamera();
                    emit('goToStep1');
                }
            }
        } catch (err) {
            console.error("Lỗi khi quét:", err);
        }
    }, 800);
};


// Chụp ảnh thủ công (kích hoạt quét lập tức)
const handleCaptureManual = async () => {
    if (!props.isModelsLoaded || !faceMatcher) {
        statusMsg.value = "Vui lòng đợi hệ thống sẵn sàng...";
        statusColor.value = "bg-amber-500 text-white";
        return;
    }

    statusMsg.value = "Đang quét khuôn mặt...";
    statusColor.value = "bg-blue-600 text-white";

    try {
        const faceapi = await getFaceApi();
        const detection = await faceapi
            .detectSingleFace(
                videoRef.value,
                new faceapi.TinyFaceDetectorOptions({
                    inputSize: 320,
                    scoreThreshold: 0.5,
                }),
            )
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            statusMsg.value = "Không phát hiện khuôn mặt";
            statusColor.value = "bg-amber-500 text-white";
            return;
        }

        const match = faceMatcher.findBestMatch(detection.descriptor);
        if (match.label !== "unknown") {
            isMatched.value = true;
            statusMsg.value = "Xác minh thành công!";
            statusColor.value = "bg-emerald-600 text-white";
            captureAndSubmit();
        } else {
            statusMsg.value = "Khuôn mặt không trùng khớp với CCCD";
            statusColor.value = "bg-rose-500 text-white";
        }
    } catch (e) {
        console.error(e);
        statusMsg.value = "Không thể phân tích ảnh";
        statusColor.value = "bg-rose-500 text-white";
    }
};

watch(
    () => props.currentStep,
    (step) => {
        if (step === 3) {
            failedMatchCount.value = 0;
            isMatched.value = false;
            faceMatcher = null;
            statusMsg.value = "Đang khởi động camera...";
            statusColor.value = "bg-emerald-600 text-white";
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
    <div class="max-w-4xl mx-auto space-y-6 relative z-10">
        <!-- Main Verification Canvas -->
        <div
            class="glass-panel w-full max-w-4xl rounded-xl overflow-hidden flex flex-col md:flex-row h-auto md:h-[650px] p-6 md:p-8 gap-6 md:gap-8"
        >
            <!-- Left Side: Camera Box & Status Bar -->
            <div class="flex-1 flex flex-col items-center justify-center gap-4">
                <!-- Camera Container -->
                <div
                    class="relative w-full aspect-[4/5] max-w-[340px] md:max-w-[400px] bg-black rounded-3xl overflow-hidden shadow-2xl"
                >
                    <!-- Camera Video Stream -->
                    <video
                        ref="videoRef"
                        @play="onVideoPlay"
                        autoplay
                        muted
                        playsinline
                        class="w-full h-full object-cover"
                    />

                    <!-- Face-api Canvas overlay -->
                    <canvas
                        ref="canvasRef"
                        class="absolute inset-0 w-full h-full pointer-events-none z-10"
                    />

                    <!-- Biometric Guide Overlay -->
                    <div class="biometric-guide">
                        <div class="scanning-line"></div>
                    </div>

                    <!-- Corner Accents -->
                    <div
                        class="absolute top-6 left-6 w-6 h-6 border-t-2 border-l-2 border-emerald-500 rounded-tl-md"
                    ></div>
                    <div
                        class="absolute top-6 right-6 w-6 h-6 border-t-2 border-r-2 border-emerald-500 rounded-tr-md"
                    ></div>
                    <div
                        class="absolute bottom-6 left-6 w-6 h-6 border-b-2 border-l-2 border-emerald-500 rounded-bl-md"
                    ></div>
                    <div
                        class="absolute bottom-6 right-6 w-6 h-6 border-b-2 border-r-2 border-emerald-500 rounded-br-md"
                    ></div>
                </div>

                <!-- Status Notifier Bar (Below Video Box) -->
                <div class="w-full max-w-[340px] md:max-w-[400px]">
                    <div
                        :class="[
                            statusColor,
                            'h-12 rounded-xl font-semibold text-sm flex items-center justify-center px-4 transition-all duration-300 shadow-sm text-center',
                        ]"
                    >
                        {{ statusMsg }}
                    </div>
                </div>
            </div>

            <!-- Right Side: Instructions & Action Buttons -->
            <div
                class="w-full md:w-80 flex flex-col justify-between py-2 gap-8"
            >
                <!-- Instructions Section -->
                <div class="space-y-6">
                    <div
                        class="flex items-center gap-2 text-primary font-bold text-xs uppercase tracking-wider"
                    >
                        <span class="material-symbols-outlined text-lg"
                            >info</span
                        >
                        Lưu ý quan trọng
                    </div>

                    <ul class="space-y-4">
                        <li
                            class="flex items-center gap-3 text-sm font-semibold text-slate-700"
                        >
                            <span
                                class="material-symbols-outlined text-emerald-500 text-lg"
                                >check_circle</span
                            >
                            Không đeo kính, khẩu trang
                        </li>
                        <li
                            class="flex items-center gap-3 text-sm font-semibold text-slate-700"
                        >
                            <span
                                class="material-symbols-outlined text-emerald-500 text-lg"
                                >check_circle</span
                            >
                            Đảm bảo môi trường đủ sáng
                        </li>
                        <li
                            class="flex items-center gap-3 text-sm font-semibold text-slate-700"
                        >
                            <span
                                class="material-symbols-outlined text-emerald-500 text-lg"
                                >check_circle</span
                            >
                            Giữ điện thoại cố định
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons (Stacked) -->
                <div class="space-y-3 w-full">
                    <!-- Quay lại Button -->
                    <button
                        type="button"
                        @click="emit('prev')"
                        class="w-full h-12 rounded-xl bg-white border border-slate-200 text-slate-700 font-bold hover:bg-slate-50 transition-all flex items-center justify-center gap-2 text-sm shadow-sm"
                    >
                        <span class="material-symbols-outlined text-base"
                            >arrow_back</span
                        >
                        Quay lại
                    </button>

                    <!-- Auto-submit / Manual capture Button -->
                    <button
                        disabled
                        class="w-full h-12 rounded-xl bg-slate-100 text-slate-400 font-bold text-xs cursor-not-allowed flex items-center justify-center gap-2 border border-slate-200 shadow-sm"
                    >
                        <span
                            class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-pulse"
                        ></span>
                        Tự động nộp hồ sơ khi khớp ảnh
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.glass-panel {
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(24px);
    border: 1.5px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 20px 40px rgba(0, 98, 140, 0.08);
}

.biometric-guide {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 220px;
    height: 280px;
    border: 2px dashed rgba(16, 185, 129, 0.5);
    /* Emerald border matching screen accents */
    border-radius: 50% 50% 45% 45%;
    pointer-events: none;
    z-index: 20;
}

.scanning-line {
    position: absolute;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #10b981, transparent);
    top: 0;
    animation: scan 3s ease-in-out infinite;
}

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
</style>
