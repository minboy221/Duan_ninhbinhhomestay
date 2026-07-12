<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, computed, watch, onMounted } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    boardingHouses: { type: Array, default: () => [] },
    currentAvailabilities: { type: Array, default: () => [] },
});

const page = usePage();

//danh sách các thứ trong tuần
const daysOfWeekList = [
    { day: 1, label: "Thứ Hai" },
    { day: 2, label: "Thứ Ba" },
    { day: 3, label: "Thứ Tư" },
    { day: 4, label: "Thứ Năm" },
    { day: 5, label: "Thứ Sáu" },
    { day: 6, label: "Thứ Bảy" },
    { day: 0, label: "Chủ Nhật" },
];

// Selection of boarding house
const selectedBoardingHouseId = ref(props.boardingHouses[0]?.id || null);

//hàm lấy số phút tự huỷ
const getCancelMinutes = (boardingHouseId) => {
    const house = props.boardingHouses.find((h) => h.id === boardingHouseId);
    return house ? (house.cancel_after_minutes ?? 30) : 30;
};

const initAvailabilities = (boardingHouseId) => {
    return daysOfWeekList.map((dayObj) => {
        // Find existing configuration
        const existing = props.currentAvailabilities.find(
            (a) => a.boarding_house_id === boardingHouseId && a.day_of_week === dayObj.day
        );

        if (existing) {
            return {
                day_of_week: dayObj.day,
                is_active: true,
                start_time: existing.start_time ? existing.start_time.substring(0, 5) : "08:00",
                end_time: existing.end_time ? existing.end_time.substring(0, 5) : "17:00",
            };
        } else {
            return {
                day_of_week: dayObj.day,
                is_active: false,
                start_time: "08:00",
                end_time: "17:00",
            };
        }
    });
};

// Inertia Form
const form = useForm({
    boarding_house_id: selectedBoardingHouseId.value,
    availabilities: initAvailabilities(selectedBoardingHouseId.value),
    cancel_after_minutes: getCancelMinutes(selectedBoardingHouseId.value),
});

watch(selectedBoardingHouseId, (newId) => {
    form.boarding_house_id = newId;
    form.availabilities = initAvailabilities(newId);
    form.cancel_after_minutes = getCancelMinutes(newId);
    form.clearErrors();
});

// Helper: Copy times from one day to all other active days
const copyTimeToAllActive = (sourceIndex) => {
    const source = form.availabilities[sourceIndex];
    if (!source.is_active) return;

    form.availabilities.forEach((item, idx) => {
        if (idx !== sourceIndex && item.is_active) {
            item.start_time = source.start_time;
            item.end_time = source.end_time;
        }
    });

    showToastNotification("Đã áp dụng thời gian sang các ngày hoạt động khác!", "success");
};

// Submit form
const submit = () => {
    form.post(route("landlord.availabilities.store"), {
        preserveScroll: true,
        onSuccess: () => {
            showToastNotification("Cập nhật khung giờ thành công!", "success");
        },
        onError: () => {
            showToastNotification("Vui lòng kiểm tra lại thông tin cài đặt.", "error");
        }
    });
};

// Notification Toast logic
const toast = ref({ show: false, message: "", type: "success" });
let toastTimeout = null;

const showToastNotification = (msg, type = "success") => {
    if (toastTimeout) clearTimeout(toastTimeout);
    toast.value = { show: true, message: msg, type };
    toastTimeout = setTimeout(() => {
        toast.value.show = false;
    }, 4000);
};

// Watch backend flash messages
const flash = computed(() => page.props.flash);
watch(() => flash.value, (newFlash) => {
    if (newFlash?.success) {
        showToastNotification(newFlash.success, "success");
    } else if (newFlash?.error) {
        showToastNotification(newFlash.error, "error");
    }
}, { deep: true, immediate: true });
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6 max-w-5xl mx-auto pb-12">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Cấu hình khung giờ</span>
            </div>

            <!-- Header -->
            <div
                class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-100 pb-5">
                <div class="space-y-1">
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <i class="bi bi-clock-history text-emerald-500"></i>
                        Cấu hình khung giờ xem phòng
                    </h2>
                    <p class="text-xs text-slate-400">
                        Đặt khung giờ rảnh của bạn cho từng cơ sở để khách thuê chủ động hẹn xem phòng trực tiếp
                    </p>
                </div>
            </div>

            <!-- Empty state when no Boarding House exists -->
            <div v-if="boardingHouses.length === 0"
                class="bg-white border border-slate-100 rounded-3xl p-8 text-center shadow-sm max-w-lg mx-auto mt-8">
                <div
                    class="w-16 h-16 bg-rose-50 rounded-full flex items-center justify-center text-rose-500 mx-auto mb-4">
                    <i class="bi bi-building-exclamation text-2xl"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 mb-1">Chưa có cơ sở trọ</h3>
                <p class="text-xs text-slate-400 font-semibold mb-6">
                    Bạn cần tạo ít nhất một cơ sở trọ/tòa nhà trước khi cấu hình khung giờ nhận lịch hẹn xem phòng.
                </p>
                <a :href="route('landlord.rooms')"
                    class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-all inline-flex items-center gap-1.5">
                    <i class="bi bi-plus-lg"></i> Thêm cơ sở trọ
                </a>
            </div>

            <!-- Main Config Panel -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                <!-- Left Panel - Instructions & Selection -->
                <div class="lg:col-span-1 bg-white border border-slate-150 rounded-3xl p-6 shadow-sm space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Chọn cơ sở
                            trọ</label>
                        <div class="relative">
                            <select v-model="selectedBoardingHouseId"
                                class="w-full text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-3 appearance-none focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all cursor-pointer">
                                <option v-for="house in boardingHouses" :key="house.id" :value="house.id">
                                    {{ house.name }}
                                </option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                                <i class="bi bi-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Thời gian tự động hủy lịch (phút) -->
                    <div class="mt-4">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Thời gian tự động hủy lịch (phút)
                        </label>
                        <input type="number" v-model="form.cancel_after_minutes" min="5" max="1440" required
                            class="w-full text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all" />
                        <p class="text-[10px] text-rose-500 font-bold mt-1" v-if="form.errors.cancel_after_minutes">
                            {{ form.errors.cancel_after_minutes }}
                        </p>
                        <p class="text-[10px] text-slate-400 mt-1">
                            Lịch xem phòng sẽ tự động chuyển sang trạng thái "expired" (hủy) sau khi quá số phút cấu hình
                            này kể từ giờ hẹn.
                        </p>
                    </div>

                    <div
                        class="border-t border-slate-50 pt-5 space-y-4 text-xs font-medium text-slate-500 leading-relaxed">
                        <h4 class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                            <i class="bi bi-lightbulb text-amber-500"></i> Hướng dẫn cấu hình:
                        </h4>
                        <ul class="list-disc pl-4 space-y-2.5">
                            <li>Bật nút gạt <span class="text-emerald-600 font-bold">Hoạt động</span> của những ngày bạn
                                có thể dẫn khách đi xem phòng.</li>
                            <li>Thiết lập khung giờ bắt đầu và kết thúc (Ví dụ: <span class="font-bold">08:00</span> đến
                                <span class="font-bold">18:00</span>).</li>
                            <li>Sử dụng nút <i class="bi bi-copy text-emerald-500"></i> để áp dụng nhanh giờ đó sang tất
                                cả các ngày hoạt động khác.</li>
                            <li>Bấm <span class="font-bold text-slate-800">Lưu Cấu Hình</span> để ghi nhận các thay đổi
                                lên hệ thống.</li>
                        </ul>
                    </div>
                </div>

                <!-- Right Panel - Day Schedule Settings -->
                <div class="lg:col-span-2 space-y-4">
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
                            <div
                                class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cài đặt lịch hàng
                                    tuần</h3>
                                <span
                                    class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                    {{form.availabilities.filter(a => a.is_active).length}} ngày mở cửa
                                </span>
                            </div>

                            <div class="divide-y divide-slate-100">
                                <div v-for="(item, idx) in form.availabilities" :key="item.day_of_week" :class="['p-5 transition-colors duration-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4',
                                    item.is_active ? 'bg-emerald-50/10' : 'bg-white opacity-75']">

                                    <!-- Left Section: Day Name & Toggle -->
                                    <div class="flex items-center justify-between sm:justify-start gap-4 min-w-[140px]">
                                        <div class="space-y-0.5">
                                            <span class="text-sm font-bold text-slate-800">{{ daysOfWeekList[idx].label
                                                }}</span>
                                            <p class="text-[10px] font-semibold"
                                                :class="item.is_active ? 'text-emerald-500' : 'text-slate-400'">
                                                {{ item.is_active ? 'Đang hoạt động' : 'Đang đóng' }}
                                            </p>
                                        </div>

                                        <!-- Custom Toggle Switch -->
                                        <label class="relative inline-flex items-center cursor-pointer select-none">
                                            <input type="checkbox" v-model="item.is_active" class="sr-only peer" />
                                            <div
                                                class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500">
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Middle Section: Time Selection (Visible only when active) -->
                                    <div class="flex-1 flex items-center justify-start gap-3">
                                        <Transition name="fade">
                                            <div v-if="item.is_active" class="flex flex-wrap items-center gap-2">
                                                <div class="relative">
                                                    <input type="time" v-model="item.start_time" required
                                                        class="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-250 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all" />
                                                </div>

                                                <span class="text-xs font-semibold text-slate-400">đến</span>

                                                <div class="relative">
                                                    <input type="time" v-model="item.end_time" required
                                                        class="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-250 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all" />
                                                </div>

                                                <!-- Quick Apply Time Helper -->
                                                <button type="button" @click="copyTimeToAllActive(idx)"
                                                    class="ml-2 p-2 hover:bg-slate-100 text-slate-400 hover:text-emerald-600 rounded-lg transition-colors"
                                                    title="Áp dụng thời gian này cho tất cả ngày khác">
                                                    <i class="bi bi-copy text-xs"></i>
                                                </button>
                                            </div>
                                            <div v-else class="text-xs text-slate-400 font-semibold py-2">
                                                Không nhận lịch hẹn vào ngày này
                                            </div>
                                        </Transition>
                                    </div>

                                    <!-- Error display for this day -->
                                    <div class="w-full sm:w-auto text-xs text-rose-500 font-bold block"
                                        v-if="form.errors[`availabilities.${idx}.start_time`] || form.errors[`availabilities.${idx}.end_time`]">
                                        <div v-if="form.errors[`availabilities.${idx}.start_time`]">
                                            {{ form.errors[`availabilities.${idx}.start_time`] }}
                                        </div>
                                        <div v-if="form.errors[`availabilities.${idx}.end_time`]">
                                            {{ form.errors[`availabilities.${idx}.end_time`] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Submit Button -->
                        <div class="flex justify-end gap-3">
                            <button type="submit" :disabled="form.processing"
                                class="px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-500/10 hover:shadow-emerald-500/20 transition-all flex items-center gap-2">
                                <span v-if="form.processing"
                                    class="inline-block animate-spin border-2 border-white border-t-transparent rounded-full w-3.5 h-3.5"></span>
                                <i v-else class="bi bi-check-circle-fill"></i>
                                Lưu Cấu Hình
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Floating Toast Alert -->
        <Teleport to="body">
            <Transition name="toast">
                <div v-if="toast.show"
                    class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-4 py-3.5 rounded-2xl shadow-xl border text-xs font-bold bg-white transition-all max-w-sm"
                    :class="toast.type === 'success' ? 'border-emerald-100 text-emerald-700 shadow-emerald-500/5' : 'border-rose-100 text-rose-700 shadow-rose-500/5'">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center"
                        :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'">
                        <i
                            :class="toast.type === 'success' ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-circle-fill'"></i>
                    </div>
                    <span class="flex-1">{{ toast.message }}</span>
                    <button type="button" @click="toast.show = false"
                        class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </Transition>
        </Teleport>
    </LandlordLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(2px);
}

.toast-enter-active {
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-leave-active {
    transition: all 0.2s ease-in;
}

.toast-enter-from {
    transform: translateY(20px) scale(0.95);
    opacity: 0;
}

.toast-leave-to {
    transform: translateY(20px) scale(0.95);
    opacity: 0;
}
</style>
