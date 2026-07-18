<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, computed, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
const props = defineProps({
    dbAppointments: { type: Array, default: () => [] },
});

const isRejectModalOpen = ref(false);
const selectedAptId = ref(null);
const rejectForm = useForm({
    cancellation_reason: "",
});
const appointments = computed(() => props.dbAppointments);

const now = new Date();
const currentYear = now.getFullYear();
const currentMonth = now.getMonth() + 1;
const viewMode = ref("month"); // 'month' | 'list'

// Conflict detection logic
const hasConflict = (apt) => {
    return appointments.value.some((other) => {
        if (other.id === apt.id || other.status === "rejected") return false;
        if (other.room !== apt.room || other.date !== apt.date) return false;
        const t1 = timeToMin(apt.time);
        const t2 = timeToMin(other.time);
        return Math.abs(t1 - t2) < 45 && t1 !== t2;
    });
};
const timeToMin = (t) => {
    const [h, m] = t.split(":").map(Number);
    return h * 60 + m;
};

// Calendar calculations
const daysInMonth = computed(() =>
    new Date(currentYear, currentMonth, 0).getDate(),
);
const firstDay = computed(() =>
    new Date(currentYear, currentMonth - 1, 1).getDay(),
);
const calendarDays = computed(() => {
    const days = [];
    for (let i = 0; i < (firstDay.value || 7) - 1; i++) days.push(null);
    for (let d = 1; d <= daysInMonth.value; d++) days.push(d);
    return days;
});

const aptsForDay = (day) => {
    if (!day) return [];
    const dateStr = `${currentYear}-${String(currentMonth).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
    return appointments.value.filter((a) => a.date === dateStr);
};

const statusMap = {
    pending: {
        label: "Chờ Duyệt",
        cls: "bg-amber-50 text-amber-600 border-amber-100",
        dot: "bg-amber-500",
    },
    approved: {
        label: "Đã Duyệt",
        cls: "bg-emerald-50 text-emerald-600 border-emerald-100",
        dot: "bg-emerald-500",
    },
    rejected: {
        label: "Từ Chối",
        cls: "bg-slate-50 text-slate-500 border-slate-100",
        dot: "bg-slate-500",
    },
    expired: {
        label: "Quá giờ hẹn",
        cls: "bg-rose-50 text-rose-500 border-rose-100",
        dot: "bg-rose-500",
    },
    success_matched: {
        label: "Đã thuê trọ",
        cls: "bg-teal-50 text-teal-600 border-teal-100",
        dot: "bg-teal-500",
    },
    false_matched: {
        label: "Không thuê",
        cls: "bg-gray-50 text-gray-500 border-gray-100",
        dot: "bg-gray-400",
    },
};

const approveApt = (apt) => {
    router.post(
        route("landlord.appointments.approve", apt.id),
        {},
        { preserveScroll: true },
    );
};
const openRejectModal = (apt) => {
    selectedAptId.value = apt.id;
    rejectForm.cancellation_reason = "";
    rejectForm.clearErrors();
    isRejectModalOpen.value = true;
};

//hàm lấy trạng thái an toàn
const getStatusData = (status) => {
    return statusMap[status] || {
        label: status || "Không rõ",
        cls: 'bg-slate-50 text-slate-500 border-slate-100',
        dot: "bg-slate-500"
    };
};

//hàm sử lý gửi form từ chối kèm lý do lên Server
const submitRejection = () => {
    rejectForm.post(
        route("landlord.appointments.reject", selectedAptId.value),
        {
            preserveScroll: true,
            onSuccess: () => {
                isRejectModalOpen.value = false;
                rejectForm.reset();
            },
        },
    );
};

// --- PHÂN TRANG CHO YÊU CẦU CHỜ DUYỆT ---
const pendingPage = ref(1);
const pendingPageSize = 5;
const pendingList = computed(() =>
    appointments.value.filter((a) => a.status === "pending"),
);
const pendingTotalPages = computed(() => Math.ceil(pendingList.value.length / pendingPageSize));
const paginatedPendingList = computed(() => {
    const start = (pendingPage.value - 1) * pendingPageSize;
    return pendingList.value.slice(start, start + pendingPageSize);
});
watch(pendingList, (newVal) => {
    if (pendingPage.value > 1 && newVal.length <= (pendingPage.value - 1) * pendingPageSize) {
        pendingPage.value = Math.max(1, Math.ceil(newVal.length / pendingPageSize));
    }
});

// --- PHÂN TRANG CHO TẤT CẢ LỊCH HẸN ---
const allPage = ref(1);
const allPageSize = 10;
const allTotalPages = computed(() => Math.ceil(appointments.value.length / allPageSize));
const paginatedAllAppointments = computed(() => {
    const start = (allPage.value - 1) * allPageSize;
    return appointments.value.slice(start, start + allPageSize);
});

// Helper hiển thị số trang có dấu ba chấm
const getVisiblePages = (currentPage, totalPages) => {
    const pages = [];
    const maxVisible = 5;
    if (totalPages <= maxVisible) {
        for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
        pages.push(1);
        if (currentPage > 3) pages.push('...');

        const start = Math.max(2, currentPage - 1);
        const end = Math.min(totalPages - 1, currentPage + 1);

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        if (currentPage < totalPages - 2) pages.push('...');
        pages.push(totalPages);
    }
    return pages;
};
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Lịch hẹn xem phòng</span>
            </div>

            <!-- Page Title -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">
                        Lịch hẹn khách hàng
                    </h2>
                    <p class="text-xs text-slate-400">
                        Xem và sắp xếp thời gian dẫn khách đi xem thực tế căn
                        hộ/phòng trọ
                    </p>
                </div>
            </div>

            <!-- Summary Chips -->
            <div class="flex flex-wrap items-center gap-3">
                <div
                    class="px-4 py-2 border border-slate-200 bg-white rounded-xl text-xs font-bold text-slate-600 flex items-center gap-1.5 shadow-sm">
                    <i class="bi bi-calendar-event text-emerald-500"></i> Tổng
                    lịch: {{ appointments.length }}
                </div>
                <div
                    class="px-4 py-2 border border-amber-200 bg-amber-50/10 rounded-xl text-xs font-bold text-amber-600 flex items-center gap-1.5 shadow-sm">
                    <i class="bi bi-hourglass-split"></i> Chờ duyệt:
                    {{ pendingList.length }}
                </div>
                <div
                    class="px-4 py-2 border border-emerald-250 bg-emerald-50/10 rounded-xl text-xs font-bold text-emerald-600 flex items-center gap-1.5 shadow-sm">
                    <i class="bi bi-check-circle-fill"></i> Đã duyệt:
                    {{
                        appointments.filter((a) => a.status === "approved")
                            .length
                    }}
                </div>
            </div>

            <!-- Main Layout Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left Calendar Panel (4 cols on lg) -->
                <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm lg:col-span-4 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-50 pb-3">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="bi bi-calendar3 text-emerald-500"></i>
                            <span>Tháng {{ currentMonth }} /
                                {{ currentYear }}</span>
                        </h3>
                    </div>

                    <!-- Calendar Headers -->
                    <div
                        class="grid grid-cols-7 text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        <span>T2</span><span>T3</span><span>T4</span><span>T5</span><span>T6</span><span>T7</span><span>CN</span>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="grid grid-cols-7 gap-1.5 text-xs font-semibold text-slate-600">
                        <div v-for="(day, idx) in calendarDays" :key="idx" :class="[
                            'min-h-[48px] rounded-xl flex flex-col items-center justify-between p-1.5 border border-transparent transition-all',
                            day
                                ? 'bg-slate-50/50 hover:bg-slate-100/60 cursor-pointer'
                                : '',
                            aptsForDay(day).length > 0
                                ? 'bg-emerald-50/30 border-emerald-200'
                                : '',
                        ]">
                            <span v-if="day" class="text-[11px] font-bold text-slate-800">{{ day }}</span>
                            <div v-if="day" class="flex gap-0.5 justify-center">
                                <span v-for="apt in aptsForDay(day).slice(0, 3)" :key="apt.id" :class="[
                                    'w-1.5 h-1.5 rounded-full',
                                    apt.status === 'pending'
                                        ? 'bg-amber-400'
                                        : apt.status === 'approved'
                                            ? 'bg-emerald-500'
                                            : 'bg-rose-400',
                                ]"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar Legend -->
                    <div
                        class="border-t border-slate-50 pt-3 flex justify-between text-[10px] font-bold text-slate-400">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            Chờ duyệt</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Đã duyệt</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Từ chối</span>
                    </div>
                </div>

                <!-- Right Request/Table List (8 cols on lg) -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Pending Requests Cards -->
                    <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-bell-fill text-amber-500"></i>
                            <span>Yêu cầu xem phòng chờ duyệt ({{
                                pendingList.length
                                }})</span>
                        </h3>

                        <div v-if="pendingList.length === 0"
                            class="p-6 text-center text-xs text-slate-400 font-semibold bg-slate-50/50 rounded-2xl border border-slate-100">
                            <i class="bi bi-check2-all text-2xl text-emerald-500 block mb-1"></i>
                            Tuyệt vời! Không còn lịch hẹn nào chưa xử lý.
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="apt in paginatedPendingList" :key="apt.id" :class="[
                                'p-4 border rounded-2xl flex flex-col md:flex-row justify-between md:items-center gap-4 transition-all',
                                hasConflict(apt)
                                    ? 'bg-rose-50/20 border-rose-200'
                                    : 'border-slate-100',
                            ]">
                                <div class="space-y-2">
                                    <!-- Warning badge if conflict exists -->
                                    <div v-if="hasConflict(apt)"
                                        class="px-2 py-0.5 bg-rose-50 text-rose-600 border border-rose-100 rounded text-[9px] font-bold w-fit flex items-center gap-1">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Trùng lịch xem phòng khác!
                                    </div>

                                    <div class="space-y-0.5">
                                        <h4 class="text-xs font-bold text-slate-800">
                                            {{ apt.name }}
                                            <span class="text-slate-400 font-semibold">({{ apt.phone }})</span>
                                        </h4>
                                        <div
                                            class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-400 font-semibold">
                                            <span><i class="bi bi-house"></i>
                                                {{ apt.room }}</span>
                                            <span><i class="bi bi-clock"></i>
                                                {{ apt.time }} ·
                                                {{
                                                    new Date(
                                                        apt.date,
                                                    ).toLocaleDateString(
                                                        "vi-VN",
                                                    )
                                                }}</span>
                                        </div>
                                    </div>
                                    <p v-if="apt.note"
                                        class="text-xs text-slate-400 font-semibold bg-slate-50 p-2 rounded-xl border border-slate-100">
                                        <i class="bi bi-chat-left-text"></i> Ghi
                                        chú: {{ apt.note }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button @click="approveApt(apt)"
                                        class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 font-bold text-xs rounded-xl transition-colors flex items-center gap-1">
                                        <i class="bi bi-check-lg"></i> Duyệt
                                    </button>

                                    <button @click="openRejectModal(apt)"
                                        class="px-3.5 py-2 hover:bg-rose-50 text-rose-500 rounded-xl transition-colors">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination for Pending Requests -->
                        <div v-if="pendingTotalPages > 1"
                            class="flex items-center justify-between border-t border-slate-100 pt-4 mt-2">
                            <span class="text-[11px] text-slate-400 font-semibold">
                                Hiển thị {{ (pendingPage - 1) * pendingPageSize + 1 }} - {{ Math.min(pendingPage *
                                    pendingPageSize, pendingList.length) }} trong số {{ pendingList.length }}
                            </span>
                            <div class="flex items-center gap-1">
                                <button @click="pendingPage = Math.max(1, pendingPage - 1)"
                                    :disabled="pendingPage === 1"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-transparent transition-all">
                                    <i class="bi bi-chevron-left text-[9px]"></i>
                                </button>
                                <button v-for="p in pendingTotalPages" :key="p" @click="pendingPage = p" :class="[
                                    'w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-bold transition-all border',
                                    pendingPage === p
                                        ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm shadow-emerald-500/10'
                                        : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                                ]">
                                    {{ p }}
                                </button>
                                <button @click="pendingPage = Math.min(pendingTotalPages, pendingPage + 1)"
                                    :disabled="pendingPage === pendingTotalPages"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-transparent transition-all">
                                    <i class="bi bi-chevron-right text-[9px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- All Schedule Table -->
                    <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="bi bi-list-stars text-emerald-500"></i>
                            <span>Tất cả lịch hẹn sắp tới</span>
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr
                                        class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="py-3 px-4">Tên khách</th>
                                        <th class="py-3 px-4">Phòng xem</th>
                                        <th class="py-3 px-4">Ngày & Giờ</th>
                                        <th class="py-3 px-4">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-600">
                                    <tr v-for="apt in paginatedAllAppointments" :key="apt.id"
                                        class="hover:bg-slate-50/30">
                                        <td class="py-3 px-4">
                                            <div class="flex flex-col">
                                                <span>{{ apt.name }}</span>
                                                <span class="text-[10px] text-slate-400 font-semibold">{{ apt.phone
                                                }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 font-bold text-emerald-600">
                                            {{ apt.room }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-500">
                                            {{ apt.time }} ·
                                            {{
                                                new Date(
                                                    apt.date,
                                                ).toLocaleDateString("vi-VN")
                                            }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span :class="[
                                                'px-2.5 py-1 rounded-md text-[10px] font-bold border flex items-center gap-1.5 w-fit',
                                                getStatusData(apt.status).cls,
                                            ]">
                                                <span class="w-1.5 h-1.5 rounded-full"
                                                    :class="getStatusData(apt.status).dot"></span>
                                                {{ getStatusData(apt.status).label }}
                                            </span>
                                        </td>

                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination for All Appointments -->
                        <div v-if="allTotalPages > 1"
                            class="flex items-center justify-between border-t border-slate-100 pt-4 mt-2">
                            <span class="text-[11px] text-slate-400 font-semibold">
                                Hiển thị {{ (allPage - 1) * allPageSize + 1 }} - {{ Math.min(allPage * allPageSize,
                                    appointments.length) }} trong số {{ appointments.length }}
                            </span>
                            <div class="flex items-center gap-1">
                                <button @click="allPage = Math.max(1, allPage - 1)" :disabled="allPage === 1"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-transparent transition-all">
                                    <i class="bi bi-chevron-left text-[9px]"></i>
                                </button>
                                <button v-for="p in getVisiblePages(allPage, allTotalPages)" :key="p"
                                    @click="typeof p === 'number' ? allPage = p : null" :class="[
                                        'w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-bold transition-all border',
                                        allPage === p
                                            ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm shadow-emerald-500/10'
                                            : p === '...'
                                                ? 'border-transparent text-slate-400 cursor-default'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                                    ]">
                                    {{ p }}
                                </button>
                                <button @click="allPage = Math.min(allTotalPages, allPage + 1)"
                                    :disabled="allPage === allTotalPages"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-transparent transition-all">
                                    <i class="bi bi-chevron-right text-[9px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Phần popup khoá lý do từ chối -->
        <div v-if="isRejectModalOpen"
            class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity">
            <div class="bg-white rounded-3xl p-6 w-full max-w-md shadow-xl border border-slate-100 mx-4">
                <div class="flex items-center gap-2 text-rose-500 mb-3">
                    <i class="bi bi-exclamation-octagon-fill text-lg"></i>
                    <h3 class="text-sm font-bold text-slate-800">
                        Xác nhận lý do từ chối lịch
                    </h3>
                </div>
                <p class="text-xs text-slate-400 font-semibold mb-4">
                    Vui lòng cung cấp lý do cụ thể để hệ thống gửi thông báo
                    phản hồi chính xác đến khách thuê phòng.
                </p>

                <form @submit.prevent="submitRejection" class="space-y-4">
                    <div>
                        <textarea v-model="rejectForm.cancellation_reason" rows="4"
                            class="w-full text-xs font-semibold..." placeholder="Nhập lý do hủy..."></textarea>

                        <!-- Hiển thị lỗi từ Backend gửi về nếu có -->
                        <p v-if="rejectForm.errors.cancellation_reason"
                            class="text-rose-500 text-[11px] font-bold mt-1">
                            {{ rejectForm.errors.cancellation_reason }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="isRejectModalOpen = false">Hủy bỏ</button>
                        <!-- Nút submit phải có type="submit" -->
                        <button type="submit" :disabled="rejectForm.processing">
                            Xác nhận hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </LandlordLayout>
</template>
