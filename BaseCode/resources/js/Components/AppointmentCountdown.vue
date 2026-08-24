<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import axios from "axios";
import { showSuccess, showError, showWarning } from "@/Utils/swal";

const page = usePage();
const todayApt = ref(null);
const countdownText = ref("");
let countdownInterval = null;
let apiCheckInterval = null;

// Phần phục vụ cho khảo sát & gợi ý AI
const showFeedbackModal = ref(false);
const feedbackStep = ref(1);
const selectedReason = ref("");
const recommendedRooms = ref([]);
const aiMessage = ref("");
const viewedRoomData = ref(null);
const loadingDislike = ref(false);

const dislikeReasons = computed(() => {
    const configuredReasons = page.props.settings?.not_interested_reasons;
    if (Array.isArray(configuredReasons) && configuredReasons.length > 0) {
        return configuredReasons;
    }
    return [
        "Giá cao quá",
        "Phòng thực tế không giống với ảnh",
        "xa nơi làm việc/học tập",
        "Chủ nhà không thân thiện",
    ];
});

const checkTodayAppointment = async () => {
    if (!page.props.auth?.user) return;

    try {
        const response = await axios.get("/api/user/today-appointments");
        if (response.data && response.data.id) {
            todayApt.value = response.data;
            startCountdown(response.data.time);
        } else {
            todayApt.value = null;
            if (countdownInterval) clearInterval(countdownInterval);
        }
    } catch (error) {
        console.error("lỗi kiểm tra lịch hẹn:", error);
    }
};

const startCountdown = (apiTime) => {
    if (!apiTime) return;
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
        const now = new Date();
        const [house, minutes] = apiTime.split(":").map(Number);
        const targetTime = new Date();
        targetTime.setHours(house, minutes, 0, 0);

        const diffMs = targetTime - now;
        if (diffMs > 0) {
            // Trường hợp 1: Sắp đến giờ hẹn
            const diffMins = Math.floor(diffMs / 60000);
            const diffSecs = Math.floor((diffMs % 60000) / 1000);
            countdownText.value = `Còn ${diffMins} phút ${diffSecs} giây`;
        } else {
            // Trường hợp 2: Đang diễn ra hoặc đã quá giờ hẹn
            countdownText.value = `Đang trong giờ hẹn xem phòng`;
            clearInterval(countdownInterval);
            // KÍCH HOẠT: Tự động mở bung Modal khảo sát bắt buộc tương tác
            showFeedbackModal.value = true;
        }
    }, 1000);
};

// Hàm xử lý khi bấm Ưng hoặc Không ưng
const handleFeedbackResult = async (result) => {
    if (result === "like") {
        try {
            await axios.post(
                `/api/appointments/${todayApt.value.id}/feedback`,
                { result: "like" },
            );
            showSuccess(
                "Chúc mừng bạn!",
                "Hệ thống đã thông báo cho chủ trọ chuẩn bị hợp đồng."
            );
            showFeedbackModal.value = false;
            todayApt.value = null; // Ẩn hoàn toàn popup nhắc nhở
        } catch (error) {
            console.error(error);
        }
    } else {
        // Nếu không ưng, chuyển sang bước 2 để hỏi lý do
        feedbackStep.value = 2;
    }
};

// Hàm gửi lý do từ chối lên và nhận danh sách phòng gợi ý từ Trợ lý AI
const submitDislikeReason = async () => {
    if (!selectedReason.value) {
        showWarning("Thông báo", "Vui lòng chọn một lý do để Trợ lý AI tìm phòng tốt hơn cho bạn.");
        return;
    }

    loadingDislike.value = true;
    try {
        const response = await axios.post(
            `/api/appointments/${todayApt.value.id}/feedback`,
            {
                result: "dislike",
                reason: selectedReason.value,
            },
        );

        recommendedRooms.value = response.data.recommendations || [];
        aiMessage.value = response.data.ai_message || "";
        viewedRoomData.value = response.data.viewed_room || null;
        feedbackStep.value = 3; // Chuyển sang bước 3: Hiển thị phòng thay thế

        showSuccess(
            "Trợ lý AI đã tìm thấy phòng!",
            "Đã tự động chọn 3 phòng trọ tương tự và phù hợp nhất dành cho bạn."
        );
    } catch (error) {
        console.error(error);
        showError("Lỗi", "Không thể gửi phản hồi. Vui lòng thử lại!");
    } finally {
        loadingDislike.value = false;
    }
};

const closeFeedback = () => {
    showFeedbackModal.value = false;
    todayApt.value = null; // Hoàn thành quy trình đóng vòng lặp
    feedbackStep.value = 1;
    selectedReason.value = "";
    recommendedRooms.value = [];
};

onMounted(() => {
    checkTodayAppointment();
    apiCheckInterval = setInterval(checkTodayAppointment, 180000);
});

onUnmounted(() => {
    if (countdownInterval) clearInterval(countdownInterval);
    if (apiCheckInterval) clearInterval(apiCheckInterval);
});
</script>

<template>
    <Link v-if="todayApt && !showFeedbackModal" :href="route('profile.appointments')"
        class="fixed bottom-4 left-1/2 -translate-x-1/2 sm:left-6 sm:translate-x-0 z-[999] w-[92%] sm:w-[380px] overflow-hidden rounded-2xl sm:rounded-3xl border border-white/40 bg-white/95 backdrop-blur-xl shadow-[0_15px_40px_rgba(15,23,42,0.15)] transition-all duration-300 hover:-translate-y-1 block cursor-pointer hover:no-underline text-slate-700">
        <!-- Top gradient -->
        <div class="h-1 bg-gradient-to-r from-blue-500 via-cyan-400 to-emerald-400"></div>

        <div class="flex items-center gap-3 p-3 sm:p-5">
            <!-- Icon -->
            <div
                class="relative flex h-12 w-12 sm:h-16 sm:w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-400 text-white shadow-lg">
                <i class="bi bi-house-door-fill text-lg sm:text-2xl"></i>

                <span
                    class="absolute -top-1 -right-1 h-3 w-3 sm:h-4 sm:w-4 rounded-full bg-red-500 border-2 border-white animate-pulse"></span>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <span
                        class="rounded-full bg-blue-100 px-2 py-0.5 sm:px-3 sm:py-1 text-[9px] sm:text-[10px] font-bold uppercase text-blue-700">
                        Hôm nay
                    </span>
                </div>

                <h3 class="mt-1 text-sm sm:text-base font-bold text-slate-800 truncate">
                    Bạn có lịch xem phòng
                </h3>

                <p class="text-xs sm:text-sm font-semibold text-slate-700 truncate">
                    {{ todayApt.room_name }}
                </p>

                <p class="hidden sm:block text-xs text-slate-500 truncate">
                    <i class="bi bi-geo-alt-fill mr-1 text-blue-500"></i>
                    {{ todayApt.address }}
                </p>

                <div class="mt-2 flex flex-wrap gap-2">
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-1 text-[10px] sm:text-xs font-semibold text-blue-700">
                        <i class="bi bi-clock-fill"></i>
                        {{ todayApt.time }}
                    </span>

                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-[10px] sm:text-xs font-semibold text-emerald-700">
                        <i class="bi bi-hourglass-split"></i>
                        {{ countdownText }}
                    </span>
                </div>
            </div>
        </div>
    </Link>

    <div v-if="showFeedbackModal && todayApt" class="feedback-modal-overlay" @click.self="closeFeedback">
        <div class="feedback-modal-container" :class="{ 'step-3-container': feedbackStep === 3 }">
            <!-- Image positioned absolutely on the left -->
            <div class="popup-image-wrapper">
                <img src="/anh/popup_character.png" alt="Feedback Character" class="popup-character-img" />
            </div>

            <!-- Content container -->
            <div class="feedback-modal-content">
                <button class="close-btn" @click="closeFeedback" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>

                <!-- Bước 1: Hỏi ưng hay không -->
                <div v-if="feedbackStep === 1" class="step-content step-1">
                    <div class="step-icon">
                        <i class="bi bi-chat-heart-fill"></i>
                    </div>
                    <div class="step-header">
                        <h3>Bạn vừa đi xem phòng xong đúng không?</h3>
                        <p>
                            Căn phòng thực tế tại
                            <span class="room-highlight">{{
                                todayApt.room_name
                            }}</span>
                            thế nào ạ?
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-4">
                        <button @click="handleFeedbackResult('like')" class="feedback-btn btn-like">
                            👍 Mình rất ưng, thuê luôn
                        </button>
                        <button @click="handleFeedbackResult('dislike')" class="feedback-btn btn-dislike">
                            👎 Không hợp với mình
                        </button>
                    </div>
                </div>

                <!-- Bước 2: Hỏi lý do -->
                <div v-if="feedbackStep === 2" class="step-content step-2">
                    <div class="step-header text-left">
                        <h3>HomeStay rất tiếc...</h3>
                        <p>
                            Bạn có thể chia sẻ lý do không ưng để Trợ lý AI tìm phòng phù hợp hơn cho bạn nhé:
                        </p>
                    </div>

                    <div class="reasons-list">
                        <label v-for="r in dislikeReasons" :key="r" class="reason-card"
                            :class="{ active: selectedReason === r }">
                            <input type="radio" v-model="selectedReason" :value="r" class="reason-radio" />
                            <span>{{ r }}</span>
                        </label>
                    </div>

                    <button @click="submitDislikeReason" :disabled="loadingDislike" class="feedback-btn btn-submit w-full mt-4">
                        <span v-if="loadingDislike"><i class="bi bi-arrow-repeat animate-spin"></i> Đang tìm phòng...</span>
                        <span v-else><i class="bi bi-stars"></i> Gửi phản hồi & Nhận gợi ý phòng từ AI</span>
                    </button>
                </div>

                <!-- Bước 3: Gợi ý phòng thay thế chuẩn từ AI -->
                <div v-if="feedbackStep === 3" class="step-content step-3">
                    <div class="step-header text-left">
                        <div class="ai-popup-top-badge">
                            <i class="bi bi-robot"></i> Trợ Lý AI Ninh Bình
                        </div>
                        <h3>AI Đã Chọn Cho Bạn 3 Phòng Sát Nhất!</h3>
                        <p class="ai-popup-sub">
                            Dựa vào lý do "<span class="reason-highlight">{{ selectedReason }}</span>", xem thử các phòng này nhé:
                        </p>
                    </div>

                    <div class="recommendations-list">
                        <div v-if="recommendedRooms.length === 0" class="no-recommendations">
                            <i class="bi bi-emoji-smile text-2xl text-slate-400"></i>
                            <p style="margin: 6px 0 0 0;">Hiện tại chưa tìm thấy phòng nào khác phù hợp hơn tiêu chí này.</p>
                        </div>

                        <a v-else v-for="post in recommendedRooms" :key="post.id"
                            :href="post.url || ('/chitiettro/' + post.slug)" target="_blank" class="recommend-item">
                            <div class="recommend-img-box">
                                <img :src="post.image || '/anh/phong1.jpg'" class="recommend-img" :alt="post.title"
                                    @error="$event.target.src = '/anh/phong1.jpg'" />
                                <span v-if="post.status_label" class="recommend-occupancy-pill"
                                    :class="post.has_residents ? 'has-people' : 'empty'">
                                    {{ post.status_label }}
                                </span>
                            </div>

                            <div class="recommend-info">
                                <h4>{{ post.title }}</h4>
                                <div class="recommend-price-row">
                                    <span class="recommend-price">{{ post.price_formatted || (Number(post.price || 0).toLocaleString() + ' đ/tháng') }}</span>
                                </div>
                                <div class="recommend-tags">
                                    <span v-if="post.floor" class="tag-meta"><i class="bi bi-layers-fill"></i> {{ post.floor }}</span>
                                    <span v-if="post.area" class="tag-meta"><i class="bi bi-aspect-ratio-fill"></i> {{ post.area }} m²</span>
                                    <span v-if="post.address" class="tag-meta address-tag"><i class="bi bi-geo-alt-fill text-blue-500"></i> {{ post.address }}</span>
                                </div>
                            </div>
                            <div class="recommend-action">
                                <span class="btn-action-view">Xem <i class="bi bi-arrow-right-short"></i></span>
                            </div>
                        </a>
                    </div>

                    <div class="feedback-footer-actions">
                        <Link :href="route('timtro')" class="feedback-btn btn-explore-more">
                            <i class="bi bi-search"></i> Khám phá thêm trên bản đồ
                        </Link>
                        <button @click="closeFeedback" class="feedback-btn btn-close-bottom">
                            Đóng lại
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.feedback-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(12px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    padding: 20px;
}

.feedback-modal-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 28px;
    width: 100%;
    max-width: 860px;
    min-height: 480px;
    position: relative;
    box-shadow:
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        inset 0 0 20px rgba(255, 255, 255, 0.5);
    display: flex;
    align-items: stretch;
    font-family:
        system-ui,
        -apple-system,
        sans-serif;
}

.popup-image-wrapper {
    position: absolute;
    left: -387px;
    bottom: -1px;
    width: 100%;
    height: auto;
    pointer-events: none;
    z-index: 5;
}

.popup-character-img {
    width: 100%;
    height: auto;
    display: block;
    filter: drop-shadow(-10px 15px 25px rgba(0, 0, 0, 0.2));
}

.feedback-modal-content {
    margin-left: 260px;
    flex: 1;
    padding: 50px 50px 50px 20px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
}

.close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: none;
    background: rgba(15, 23, 42, 0.05);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    color: #64748b;
    z-index: 10;
}

.close-btn:hover {
    background: #ef4444;
    color: white;
    transform: rotate(90deg);
}

.step-content {
    animation: fadeIn 0.35s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.step-icon {
    width: 52px;
    height: 52px;
    background: #c5eaff;
    color: #45abe6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 18px;
}

.step-header h3 {
    font-size: 23px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.3;
    margin: 0 0 8px 0;
}

.step-header p {
    font-size: 15px;
    color: #64748b;
    font-weight: 500;
    margin: 0;
}

.room-highlight {
    color: #0f172a;
    font-weight: 700;
}

.feedback-btn {
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 14.5px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-like {
    background: linear-gradient(90deg, #102a6d, #45abe6);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}

.btn-like:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px #45abe6;
}

.btn-dislike {
    background: #f1f5f9;
    color: #475569;
}

.btn-dislike:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: translateY(-2px);
}

/* Step 2 reasons */
.reasons-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 16px;
}

.reason-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
}

.reason-card:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.reason-card.active {
    border-color: #3b82f6;
    background: #eff6ff;
    color: #1d4ed8;
}

.reason-radio {
    accent-color: #3b82f6;
    width: 16px;
    height: 16px;
}

.btn-submit {
    background: linear-gradient(90deg, #102a6d, #45abe6);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
}

/* Step 3 recommendations */
.ai-popup-top-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: linear-gradient(135deg, #eff6ff 0%, #ede9fe 100%);
    color: #4f46e5;
    border: 1px solid #c7d2fe;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 750;
    margin-bottom: 8px;
    box-shadow: 0 2px 6px rgba(79, 70, 229, 0.1);
}

.ai-popup-sub {
    font-size: 13.5px;
    color: #64748b;
    margin: 2px 0 0 0;
}

.reason-highlight {
    color: #2563eb;
    font-weight: 700;
}

.recommendations-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 14px;
    max-height: 260px;
    overflow-y: auto;
    padding-right: 6px;
}

.recommendations-list::-webkit-scrollbar {
    width: 5px;
}

.recommendations-list::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 6px;
}

.recommendations-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 6px;
}

.no-recommendations {
    text-align: center;
    padding: 24px 0;
    font-size: 13.5px;
    font-weight: 600;
    color: #94a3b8;
}

.recommend-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    transition: all 0.25s ease;
    text-decoration: none;
    background: #ffffff;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
}

.recommend-item:hover {
    border-color: #3b82f6;
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px -4px rgba(59, 130, 246, 0.15);
}

.recommend-img-box {
    position: relative;
    width: 68px;
    height: 68px;
    flex-shrink: 0;
    border-radius: 12px;
    overflow: hidden;
    background: #f1f5f9;
}

.recommend-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.recommend-occupancy-pill {
    position: absolute;
    bottom: 2px;
    left: 2px;
    right: 2px;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 3px;
    border-radius: 4px;
    text-align: center;
    backdrop-filter: blur(4px);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.recommend-occupancy-pill.empty {
    background: rgba(16, 185, 129, 0.9);
    color: #ffffff;
}

.recommend-occupancy-pill.has-people {
    background: rgba(37, 99, 235, 0.9);
    color: #ffffff;
}

.recommend-info {
    flex: 1;
    min-width: 0;
}

.recommend-info h4 {
    font-size: 13.5px;
    font-weight: 750;
    color: #0f172a;
    margin: 0 0 2px 0;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 1;
    overflow: hidden;
}

.recommend-price-row {
    display: flex;
    align-items: center;
    gap: 6px;
}

.recommend-price {
    font-size: 14px;
    font-weight: 800;
    color: #dc2626;
    margin: 0;
}

.recommend-tags {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
    margin-top: 3px;
}

.tag-meta {
    font-size: 10.5px;
    font-weight: 600;
    color: #475569;
    background: #f1f5f9;
    padding: 1px 6px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.address-tag {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 170px;
}

.recommend-action {
    flex-shrink: 0;
}

.btn-action-view {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.2s;
    white-space: nowrap;
}

.recommend-item:hover .btn-action-view {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
}

.feedback-footer-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 14px;
}

.btn-explore-more {
    flex: 1;
    background: #f8fafc;
    color: #2563eb;
    border: 1px solid #cbd5e1;
    text-decoration: none;
    font-size: 13px;
}

.btn-explore-more:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1d4ed8;
}

.btn-close-bottom {
    background: #f1f5f9;
    color: #475569;
    font-size: 13px;
    padding: 12px 18px;
}

.btn-close-bottom:hover {
    background: #e2e8f0;
    color: #0f172a;
}

/* RESPONSIVE */
@media (max-width: 860px) {
    .feedback-modal-container {
        max-width: 520px;
        min-height: auto;
    }

    .popup-image-wrapper {
        display: none;
    }

    .feedback-modal-content {
        margin-left: 0;
        padding: 30px 24px;
    }
}
</style>
