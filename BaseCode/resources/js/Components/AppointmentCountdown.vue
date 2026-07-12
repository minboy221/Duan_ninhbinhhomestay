<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";

const page = usePage();
const todayApt = ref(null);
const countdownText = ref("");
let countdownInterval = null;
let apiCheckInterval = null;

//phần phục vụ cho khảo sát
const showFeedbackModal = ref(false);
const feedbackStep = ref(1); //hỏi ưng không => hỏi lý do => hiện phòng gợi ý
const selectedReason = ref("");
const recommendedRooms = ref("");

const dislikeReasons = [
    "Giá cao quá",
    "Phòng thực tế không giống với ảnh",
    "xa nơi làm việc/học tập",
    "Chủ nhà không thân thiện",
];

const checkTodayAppointment = async () => {
    if (!page.props.auth?.user) return;

    try {
        const response = await axios.get("/api/user/today-appointments");
        if (response.data) {
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
            countdownText.value = `Còn ${diffMins}phút ${diffSecs}giây`;
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
            alert(
                "StayWork chúc mừng bạn! Hệ thống đã thông báo cho chủ trọ chuẩn bị hợp đồng.",
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

// Hàm gửi lý do từ chối lên và nhận danh sách phòng gợi ý từ Backend "AI cơm"
const submitDislikeReason = async () => {
    if (!selectedReason.value)
        return alert("Vui lòng chọn một lý do để hệ thống tìm phòng tốt hơn.");

    try {
        const response = await axios.post(
            `/api/appointments/${todayApt.value.id}/feedback`,
            {
                result: "dislike",
                reason: selectedReason.value,
            },
        );

        // Nhận mảng phòng gợi ý dựa trên lý do chê từ Backend trả về
        recommendedRooms.value = response.data.recommendations;
        feedbackStep.value = 3; // Chuyển sang bước 3: Hiển thị phòng thay thế
    } catch (error) {
        console.error(error);
    }
};

const closeFeedback = () => {
    showFeedbackModal.value = false;
    todayApt.value = null; // Hoàn thành quy trình đóng vòng lặp
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
    <div v-if="todayApt && !showFeedbackModal"
        class="fixed bottom-6 left-6 z-50 bg-white border border-slate-100 shadow-2xl rounded-3xl p-4 max-w-sm flex items-start gap-3 transform transition-all duration-500 hover:scale-105">
        <div class="p-3 bg-blue-50 text-blue-500 rounded-2xl flex-shrink-0 animate-pulse">
            <i class="bi bi-geo-alt-fill text-xl"></i>
        </div>
        <div class="space-y-1">
            <h4 class="text-xs font-bold text-slate-800">
                Hôm nay bạn có lịch xem phòng!
            </h4>
            <p class="text-[11px] text-slate-500 font-semibold line-clamp-1">
                {{ todayApt.room_name }} — {{ todayApt.address }}
            </p>
            <div class="flex items-center gap-2 pt-1">
                <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">
                    Giờ: {{ todayApt.time }}
                </span>
                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                    {{ countdownText }}
                </span>
            </div>
        </div>
    </div>

    <div v-if="showFeedbackModal && todayApt"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
        <div
            class="bg-white rounded-3xl p-6 max-w-md w-full space-y-5 border border-slate-50 shadow-2xl animate-fade-in">
            <div v-if="feedbackStep === 1" class="text-center space-y-4 py-2">
                <div
                    class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto text-xl">
                    <i class="bi bi-chat-heart-fill"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-slate-800">
                        Bạn vừa đi xem phòng xong đúng không?
                    </h3>
                    <p class="text-xs text-slate-400 font-semibold">
                        Căn phòng thực tế tại
                        <span class="text-slate-600 font-bold">{{
                            todayApt.room_name
                            }}</span>
                        thế nào ạ?
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 pt-3">
                    <button @click="handleFeedbackResult('like')"
                        class="p-3 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                        👍 Mình rất ưng, thuê luôn
                    </button>
                    <button @click="handleFeedbackResult('dislike')"
                        class="p-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition-all">
                        👎 Không hợp với mình
                    </button>
                </div>
            </div>

            <div v-if="feedbackStep === 2" class="space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">
                        Home Stay rất tiếc...
                    </h3>
                    <p class="text-xs text-slate-400 font-semibold">
                        Bạn có thể chia sẻ lý do không ưng để hệ thống cải thiện
                        không?
                    </p>
                </div>

                <div class="space-y-2">
                    <label v-for="r in dislikeReasons" :key="r"
                        class="flex items-center gap-3 p-3 border rounded-xl text-xs font-bold text-slate-600 cursor-pointer hover:bg-slate-50 transition-colors"
                        :class="selectedReason === r
                                ? 'border-blue-500 bg-blue-50/20 text-blue-600'
                                : 'border-slate-100'
                            ">
                        <input type="radio" v-model="selectedReason" :value="r"
                            class="text-blue-500 focus:ring-blue-400" />
                        <span>{{ r }}</span>
                    </label>
                </div>

                <button @click="submitDislikeReason"
                    class="w-full p-3 bg-blue-500 hover:bg-blue-600 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                    Gửi phản hồi và tìm phòng mới
                </button>
            </div>

            <div v-if="feedbackStep === 3" class="space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">
                        StayWork tìm cho bạn phòng tốt hơn nè!
                    </h3>
                    <p class="text-xs text-slate-400 font-semibold">
                        Dựa vào lý do "<span class="text-blue-500 font-bold">{{
                            selectedReason
                            }}</span>", xem thử các phòng này nhé:
                    </p>
                </div>

                <div class="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                    <div v-if="recommendedRooms.length === 0"
                        class="text-center py-4 text-xs font-semibold text-slate-400">
                        Hiện tại chưa tìm thấy phòng nào phù hợp hơn lý do này.
                    </div>
                    <a v-else v-for="post in recommendedRooms" :key="post.id" :href="'/chitiettro/' + post.id"
                        class="flex items-center gap-3 p-2 border border-slate-100 hover:border-blue-200 rounded-2xl hover:bg-slate-50/50 transition-all group block">
                        <img :src="post.thumbnail || '/images/default-room.jpg'"
                            class="w-12 h-12 object-cover rounded-xl flex-shrink-0" />
                        <div class="space-y-0.5 flex-1">
                            <h4
                                class="text-xs font-bold text-slate-700 group-hover:text-blue-600 line-clamp-1 transition-colors">
                                {{ post.title }}
                            </h4>
                            <p class="text-[11px] font-bold text-rose-500">
                                {{
                                    Number(post.price).toLocaleString()
                                }}
                                đ/tháng
                            </p>
                        </div>
                        <i class="bi bi-chevron-right text-slate-300 text-sm pr-1"></i>
                    </a>
                </div>

                <button @click="closeFeedback"
                    class="w-full p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-500 font-bold text-xs rounded-xl transition-colors">
                    Đóng lại & tự tìm tiếp
                </button>
            </div>
        </div>
    </div>
</template>
