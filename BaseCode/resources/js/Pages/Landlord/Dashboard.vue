<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { computed, onMounted } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import { showSuccess } from "@/Utils/swal";

const page = usePage();
const user = computed(() => page.props.auth?.user);

const formatMoney = (n) => new Intl.NumberFormat("vi-VN").format(n || 0) + "đ";

// nhận dữ liệu từ controller
const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recentContracts: { type: Array, default: () => [] },
    recentInvoices: { type: Array, default: () => [] },
});

onMounted(() => {
    if (page.props.flash?.is_new_registration) {
        showSuccess(
            "🎉 Chúc Mừng Bạn Đăng Ký Thành Công!",
            "Hệ thống đã tự động kích hoạt 60 Ngày Dùng Thử MIỄN PHÍ 100% Gói Full VIP với đầy đủ tính năng cao cấp cho bạn!"
        );
    }
});

const occupancyRate = computed(() => {
    if (!props.stats?.totalRooms) return 0;
    return Math.min(100, Math.round((props.stats.rentedRooms / props.stats.totalRooms) * 100));
});

const vacantRooms = computed(() => {
    return Math.max(0, (props.stats?.totalRooms || 0) - (props.stats?.rentedRooms || 0));
});

const contractStatusConfig = (status) => {
    const map = {
        active: { label: "Hiệu lực", cls: "bg-emerald-50 text-emerald-700 border-emerald-200" },
        signed: { label: "Đã ký", cls: "bg-blue-50 text-blue-700 border-blue-200" },
        pending: { label: "Chờ duyệt", cls: "bg-amber-50 text-amber-700 border-amber-200" },
        awaiting_upload: { label: "Chờ upload", cls: "bg-amber-50 text-amber-700 border-amber-200" },
        expiring: { label: "Sắp hết hạn", cls: "bg-orange-50 text-orange-700 border-orange-200" },
        expired: { label: "Đã hết hạn", cls: "bg-rose-50 text-rose-700 border-rose-200" },
        terminated: { label: "Thanh lý", cls: "bg-slate-100 text-slate-600 border-slate-200" },
        cancelled: { label: "Đã hủy", cls: "bg-slate-100 text-slate-600 border-slate-200" },
    };
    return map[status] || { label: status || "Khác", cls: "bg-slate-50 text-slate-600 border-slate-200" };
};

const invoiceStatusConfig = (status) => {
    const map = {
        paid: { label: "Đã thu", cls: "bg-emerald-50 text-emerald-700 border-emerald-200" },
        partially_paid: { label: "Thu 1 phần", cls: "bg-blue-50 text-blue-700 border-blue-200" },
        unpaid: { label: "Chưa thu", cls: "bg-rose-50 text-rose-700 border-rose-200" },
        overdue: { label: "Quá hạn", cls: "bg-red-50 text-red-700 border-red-200" },
    };
    return map[status] || { label: status || "Khác", cls: "bg-slate-50 text-slate-600 border-slate-200" };
};
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6 sm:space-y-8 pb-12 px-2 sm:px-0">
            <!-- Breadcrumbs Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-[11px] sm:text-xs text-slate-400 font-extrabold uppercase tracking-widest">
                    <span>Bảng điều khiển</span>
                    <i class="bi bi-chevron-right text-[10px] text-slate-300"></i>
                    <span class="text-emerald-600 font-extrabold">Tổng quan kinh doanh</span>
                </div>
                <div class="self-start sm:self-auto text-[11px] sm:text-xs text-slate-500 font-semibold flex items-center gap-2 bg-white px-3 py-1.5 rounded-full border border-slate-200 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Hệ thống hoạt động bình thường</span>
                </div>
            </div>

            <!-- Welcome Banner Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
                <!-- Main Welcome Banner -->
                <div class="lg:col-span-2 bg-gradient-to-tr from-slate-950 via-[#064e3b] to-emerald-900 text-white rounded-3xl p-5 sm:p-8 relative overflow-hidden flex flex-col justify-between min-h-[200px] shadow-xl shadow-emerald-950/20 border border-emerald-800/30">
                    <div class="absolute -right-6 -bottom-10 opacity-10 text-[120px] sm:text-[180px] pointer-events-none text-emerald-300">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="space-y-3 z-10">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] sm:text-xs font-bold border border-emerald-400/20 backdrop-blur-md">
                                <i class="bi bi-stars text-amber-300"></i>
                                <span>Quản Lý Trọ 4.0</span>
                            </div>
                            <!-- Badge Gói đang có -->
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-[11px] sm:text-xs font-bold border border-amber-400/30 backdrop-blur-md">
                                <i class="bi bi-gem"></i>
                                <span>Gói: {{ props.stats?.currentPlanName }}</span>
                                <span v-if="props.stats?.planDaysRemaining !== null" class="opacity-80 font-normal">({{ props.stats?.planDaysRemaining }} ngày còn lại)</span>
                            </div>
                        </div>

                        <h2 class="text-xl sm:text-2xl md:text-3xl font-black tracking-tight text-white leading-tight">
                            Xin chào, {{ user?.name || "Chủ trọ" }}! 👋
                        </h2>
                        <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed max-w-xl font-medium">
                            Bạn đang vận hành <strong class="text-white font-black">{{ props.stats?.totalBoardingHouses || 0 }} cơ sở trọ</strong> với tổng doanh thu tích lũy đạt <strong class="text-amber-300 font-black">{{ formatMoney(props.stats?.totalRevenue) }}</strong>.
                        </p>
                    </div>

                    <div class="z-10 mt-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <Link href="/landlord/invoices"
                              class="px-5 py-2.5 bg-white text-emerald-950 font-extrabold text-xs rounded-xl shadow-lg hover:bg-emerald-50 active:scale-95 transition-all inline-flex items-center justify-center gap-2 text-center">
                            <i class="bi bi-file-earmark-plus-fill text-emerald-600 text-sm"></i>
                            Tạo Hóa Đơn Ngay
                        </Link>
                        <Link href="/landlord/contracts"
                              class="px-5 py-2.5 bg-emerald-800/60 hover:bg-emerald-800 text-white font-extrabold text-xs rounded-xl border border-emerald-700/60 transition-all inline-flex items-center justify-center gap-2 backdrop-blur-md text-center">
                            <i class="bi bi-file-text"></i>
                            Quản Lý Hợp Đồng
                        </Link>
                    </div>
                </div>

                <!-- Pro Utility & Subscription Plan Panel -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 flex flex-col justify-between shadow-[0_8px_30px_rgb(0,0,0,0.03)] relative overflow-hidden group">
                    <div class="space-y-3 sm:space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 text-[10px] font-extrabold text-amber-700 bg-amber-50 border border-amber-200 rounded-md uppercase tracking-wider flex items-center gap-1">
                                <i class="bi bi-shield-check text-amber-500"></i> GÓI DỊCH VỤ
                            </span>
                            <span class="text-[11px] text-emerald-600 font-extrabold flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Đang Kích Hoạt
                            </span>
                        </div>

                        <div>
                            <h4 class="text-base sm:text-lg font-black text-slate-900">
                                {{ props.stats?.currentPlanName }}
                            </h4>
                            <p class="text-[11px] sm:text-xs text-slate-400 font-semibold mt-0.5">
                                Hạn sử dụng: {{ props.stats?.planEndDate || 'Dùng thử không giới hạn' }}
                            </p>
                        </div>

                        <div class="p-3 sm:p-3.5 rounded-2xl bg-amber-50/60 border border-amber-100/80 text-xs text-amber-900 font-semibold space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span>Thời hạn còn lại:</span>
                                <span class="font-extrabold text-amber-700">
                                    {{ props.stats?.planDaysRemaining !== null ? props.stats.planDaysRemaining + ' ngày' : 'Đang hoạt động' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Số cơ sở đang quản lý:</span>
                                <span class="font-extrabold text-slate-800">{{ props.stats?.totalBoardingHouses || 0 }} cơ sở</span>
                            </div>
                        </div>
                    </div>

                    <Link href="/landlord/subscriptions"
                          class="w-full mt-4 py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white text-center font-extrabold text-xs rounded-xl transition-all duration-300 shadow-md inline-flex items-center justify-center gap-2">
                        <i class="bi bi-gem"></i>
                        Gia Hạn & Nâng Cấp Gói
                    </Link>
                </div>
            </div>

            <!-- Main Metric Stat Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <!-- Card 1: Month Revenue -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 flex flex-col justify-between shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:-translate-y-1 hover:shadow-lg hover:border-emerald-200 transition-all duration-300">
                    <div class="space-y-2">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold border border-emerald-100">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">
                            Doanh Thu Tháng Này
                        </p>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            {{ formatMoney(props.stats?.thisMonthRevenue) }}
                        </h3>
                    </div>
                    <p class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-3 pt-2 border-t border-slate-100">
                        <i class="bi bi-check-circle-fill text-[10px]"></i>
                        Đã đóng tháng {{ new Date().getMonth() + 1 }}
                    </p>
                </div>

                <!-- Card 2: Total Accumulative Revenue -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 flex flex-col justify-between shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:-translate-y-1 hover:shadow-lg hover:border-teal-200 transition-all duration-300">
                    <div class="space-y-2">
                        <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg font-bold border border-teal-100">
                            <i class="bi bi-piggy-bank-fill"></i>
                        </div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">
                            Tổng Doanh Thu Tích Lũy
                        </p>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            {{ formatMoney(props.stats?.totalRevenue) }}
                        </h3>
                    </div>
                    <p class="text-[11px] font-semibold text-teal-600 flex items-center gap-1 mt-3 pt-2 border-t border-slate-100">
                        <i class="bi bi-graph-up-arrow text-[10px]"></i>
                        Tất cả hóa đơn đã đóng
                    </p>
                </div>

                <!-- Card 3: Total Boarding Houses & Rooms -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 flex flex-col justify-between shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:-translate-y-1 hover:shadow-lg hover:border-purple-200 transition-all duration-300">
                    <div class="space-y-2">
                        <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg font-bold border border-purple-100">
                            <i class="bi bi-houses-fill"></i>
                        </div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">
                            Cơ Sở Trọ Đang Có
                        </p>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            {{ props.stats?.totalBoardingHouses || 0 }} <span class="text-xs font-bold text-slate-500">cơ sở</span>
                        </h3>
                    </div>
                    <p class="text-[11px] font-semibold text-purple-600 flex items-center gap-1 mt-3 pt-2 border-t border-slate-100">
                        <i class="bi bi-door-open-fill text-[10px]"></i>
                        Tổng {{ props.stats?.totalRooms || 0 }} phòng trọ
                    </p>
                </div>

                <!-- Card 4: Rented Rooms & Tenants -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 flex flex-col justify-between shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:-translate-y-1 hover:shadow-lg hover:border-amber-200 transition-all duration-300">
                    <div class="space-y-2">
                        <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold border border-amber-100">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">
                            Cư Dân Đang Ở
                        </p>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            {{ props.stats?.totalTenants || 0 }} <span class="text-xs font-bold text-slate-500">cư dân</span>
                        </h3>
                    </div>
                    <p class="text-[11px] font-semibold text-amber-600 flex items-center gap-1 mt-3 pt-2 border-t border-slate-100">
                        <i class="bi bi-pie-chart-fill text-[10px]"></i>
                        Đã thuê {{ props.stats?.rentedRooms || 0 }}/{{ props.stats?.totalRooms || 0 }} phòng ({{ occupancyRate }}%)
                    </p>
                </div>
            </div>

            <!-- Donut Occupancy & Quick Shortcuts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-6">
                <!-- Dynamic Donut Chart -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.015)] flex flex-col justify-between">
                    <div class="space-y-1">
                        <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <i class="bi bi-pie-chart-fill text-emerald-600"></i>
                            Tình Trạng Phòng
                        </h3>
                        <p class="text-xs text-slate-400 font-medium">
                            Tỷ lệ lấp đầy phòng trọ thực tế
                        </p>
                    </div>

                    <!-- SVG Donut Chart with Dynamic Percent -->
                    <div class="flex items-center justify-center py-6">
                        <div class="relative w-36 h-36 sm:w-44 sm:h-44">
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                <!-- Background Circle (vacant) -->
                                <circle cx="18" cy="18" r="15.915" fill="none" stroke="#f1f5f9" stroke-width="4" />
                                <!-- Segment Circle (occupied) -->
                                <circle cx="18" cy="18" r="15.915" fill="none" stroke="url(#emeraldGradient)"
                                        stroke-width="4" stroke-linecap="round"
                                        :stroke-dasharray="`${occupancyRate} ${100 - occupancyRate}`"
                                        stroke-dashoffset="0" />
                                <defs>
                                    <linearGradient id="emeraldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#10b981" />
                                        <stop offset="100%" stop-color="#047857" />
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ occupancyRate }}%</span>
                                <span class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">Lấp Đầy</span>
                            </div>
                        </div>
                    </div>

                    <!-- Legend list -->
                    <div class="space-y-3 pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span class="text-slate-600 font-semibold">Đang cho thuê</span>
                            </div>
                            <span class="text-slate-900 font-extrabold">{{ props.stats?.rentedRooms || 0 }} phòng ({{ occupancyRate }}%)</span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-200"></span>
                                <span class="text-slate-600 font-semibold">Phòng còn trống</span>
                            </div>
                            <span class="text-slate-900 font-extrabold">{{ vacantRooms }} phòng ({{ props.stats?.totalRooms ? (100 - occupancyRate) : 0 }}%)</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Action Shortcuts -->
                <div class="lg:col-span-2 bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.015)] flex flex-col justify-between">
                    <div class="space-y-1 mb-4">
                        <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <i class="bi bi-grid-fill text-indigo-600"></i>
                            Lối Tắt Thao Tác Nhanh
                        </h3>
                        <p class="text-xs text-slate-400 font-medium">
                            Các tính năng thường dùng trong quản lý trọ
                        </p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 py-2">
                        <Link href="/landlord/invoices"
                              class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50/60 border border-emerald-100 hover:bg-emerald-100/60 transition-all flex flex-col items-center text-center gap-2 group">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-base sm:text-lg font-bold shadow-md shadow-emerald-600/20 group-hover:scale-110 transition-transform">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <span class="text-xs font-extrabold text-slate-800">Lập Hóa Đơn</span>
                        </Link>

                        <Link href="/landlord/contracts"
                              class="p-3.5 sm:p-4 rounded-2xl bg-indigo-50/60 border border-indigo-100 hover:bg-indigo-100/60 transition-all flex flex-col items-center text-center gap-2 group">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-base sm:text-lg font-bold shadow-md shadow-indigo-600/20 group-hover:scale-110 transition-transform">
                                <i class="bi bi-journal-check"></i>
                            </div>
                            <span class="text-xs font-extrabold text-slate-800">Tạo Hợp Đồng</span>
                        </Link>

                        <Link href="/landlord/rooms"
                              class="p-3.5 sm:p-4 rounded-2xl bg-amber-50/60 border border-amber-100 hover:bg-amber-100/60 transition-all flex flex-col items-center text-center gap-2 group">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-base sm:text-lg font-bold shadow-md shadow-amber-500/20 group-hover:scale-110 transition-transform">
                                <i class="bi bi-door-open-fill"></i>
                            </div>
                            <span class="text-xs font-extrabold text-slate-800">Sơ Đồ Phòng</span>
                        </Link>

                        <Link href="/landlord/services"
                              class="p-3.5 sm:p-4 rounded-2xl bg-purple-50/60 border border-purple-100 hover:bg-purple-100/60 transition-all flex flex-col items-center text-center gap-2 group">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center text-base sm:text-lg font-bold shadow-md shadow-purple-600/20 group-hover:scale-110 transition-transform">
                                <i class="bi bi-gear-wide-connected"></i>
                            </div>
                            <span class="text-xs font-extrabold text-slate-800">Bảng Giá Dịch Vụ</span>
                        </Link>
                    </div>

                    <div class="mt-4 p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-sm flex-shrink-0">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Dữ liệu lưu trữ bảo mật</p>
                                <p class="text-[11px] text-slate-400 font-medium">Sao lưu lịch sử hóa đơn & hợp đồng 24/7</p>
                            </div>
                        </div>
                        <Link href="/landlord/tenants" class="text-xs font-extrabold text-emerald-600 hover:underline">
                            Cư dân &rarr;
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Recent Lists Columns Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
                <!-- Hợp đồng gần đây -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.015)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                            <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <i class="bi bi-file-earmark-text-fill text-emerald-600"></i>
                                Hợp Đồng Mới Nhất
                            </h3>
                            <Link href="/landlord/contracts"
                                  class="text-xs font-extrabold text-emerald-600 hover:text-emerald-700 transition-colors flex items-center gap-1">
                                Xem tất cả <i class="bi bi-arrow-right"></i>
                            </Link>
                        </div>

                        <div class="space-y-3">
                            <div v-if="!props.recentContracts?.length" class="text-xs text-slate-400 font-medium text-center py-8 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                                <i class="bi bi-inbox text-2xl text-slate-300 block mb-1"></i>
                                Chưa có dữ liệu hợp đồng
                            </div>
                            <div v-for="item in props.recentContracts" :key="item.id"
                                 class="flex flex-col sm:flex-row sm:items-center justify-between p-3.5 bg-slate-50/60 border border-slate-200/60 rounded-2xl hover:bg-emerald-50/30 hover:border-emerald-200 transition-all duration-200 gap-2 sm:gap-0">
                                <div class="space-y-0.5">
                                    <p class="text-sm font-bold text-slate-800">
                                        {{ item.tenant }}
                                    </p>
                                    <p class="text-xs text-slate-400 font-semibold flex items-center gap-2">
                                        <span class="text-slate-600 font-extrabold">{{ item.room }}</span>
                                        <span>&bull;</span>
                                        <span>Ký: {{ item.date }}</span>
                                    </p>
                                </div>
                                <span :class="[
                                    'self-start sm:self-auto px-2.5 py-0.5 sm:py-1 rounded-lg text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider border',
                                    contractStatusConfig(item.status).cls,
                                ]">
                                    {{ contractStatusConfig(item.status).label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hóa đơn gần đây -->
                <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-[0_8px_30px_rgb(0,0,0,0.015)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                            <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <i class="bi bi-receipt-cutoff text-indigo-600"></i>
                                Hóa Đơn Mới Nhất
                            </h3>
                            <Link href="/landlord/invoices"
                                  class="text-xs font-extrabold text-indigo-600 hover:text-indigo-700 transition-colors flex items-center gap-1">
                                Xem tất cả <i class="bi bi-arrow-right"></i>
                            </Link>
                        </div>

                        <div class="space-y-3">
                            <div v-if="!props.recentInvoices?.length" class="text-xs text-slate-400 font-medium text-center py-8 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                                <i class="bi bi-receipt text-2xl text-slate-300 block mb-1"></i>
                                Chưa có dữ liệu hóa đơn
                            </div>
                            <div v-for="item in props.recentInvoices" :key="item.id"
                                 class="flex flex-col sm:flex-row sm:items-center justify-between p-3.5 bg-slate-50/60 border border-slate-200/60 rounded-2xl hover:bg-indigo-50/30 hover:border-indigo-200 transition-all duration-200 gap-2 sm:gap-0">
                                <div class="space-y-0.5">
                                    <p class="text-sm font-bold text-slate-800">
                                        {{ item.code }}
                                    </p>
                                    <p class="text-xs text-slate-400 font-semibold flex items-center gap-2">
                                        <span class="text-slate-600 font-extrabold">{{ item.room }}</span>
                                        <span>&bull;</span>
                                        <span class="text-emerald-700 font-black">{{ formatMoney(item.amount) }}</span>
                                    </p>
                                </div>
                                <span :class="[
                                    'self-start sm:self-auto px-2.5 py-0.5 sm:py-1 rounded-lg text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider border',
                                    invoiceStatusConfig(item.status).cls,
                                ]">
                                    {{ invoiceStatusConfig(item.status).label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
