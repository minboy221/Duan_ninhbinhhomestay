<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)

const formatMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'

// Mock Data matching the screenshot reference
const stats = ref({
    recentRevenue: 3545000,
    recentRevenueTrend: 0,
    totalTenants: 1,
    totalTenantsTrend: 100,
    totalRevenue: 3545000,
    totalRevenueTrend: '∞'
})

const servicesRevenue = ref([
    { name: 'Tổng', color: '#00b58a' },
    { name: 'Tiền nhà', color: '#3b82f6' },
    { name: 'Điện', color: '#f59e0b' },
    { name: 'Nước', color: '#06b6d4' },
    { name: 'Internet', color: '#8b5cf6' },
    { name: 'Rác', color: '#64748b' },
    { name: 'Giữ xe', color: '#ec4899' },
    { name: 'Quản lý', color: '#10b981' }
])

const serviceRevenuesTable = ref([
    { invoice: '06/2026', room: 2500000, electric: 350000, water: 100000, wifi: 50000, garbage: 30000, parking: 50000, manage: 50000, total: 3180000 },
    { invoice: '05/2026', room: 2500000, electric: 420000, water: 120000, wifi: 50000, garbage: 30000, parking: 50000, manage: 50000, total: 3270000 },
    { invoice: '04/2026', room: 2500000, electric: 380000, water: 110000, wifi: 50000, garbage: 30000, parking: 50000, manage: 50000, total: 3220000 }
])

const recentContracts = ref([
    { id: 1, tenant: 'Phạm Văn Khoa', room: 'P.101', date: '01/06/2026', status: 'signed' },
    { id: 2, tenant: 'Lê Thị Mai', room: 'P.103', date: '28/05/2026', status: 'pending' }
])

const recentInvoices = ref([
    { id: 1, code: 'HD-202606-001', room: 'P.101', amount: 3180000, status: 'unpaid' },
    { id: 2, code: 'HD-202605-002', room: 'P.102', amount: 3270000, status: 'paid' }
])

const recentTenants = ref([
    { id: 1, name: 'Phạm Văn Khoa', phone: '0911 222 333', room: 'P.101', date: '01/06/2026' },
    { id: 2, name: 'Lê Thị Mai', phone: '0944 555 666', room: 'P.103', date: '28/05/2026' }
])
</script>

<template>
    <LandlordLayout>
        <div class="space-y-8 pb-12">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-[10px] text-slate-400 font-extrabold uppercase tracking-widest">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[8px] text-slate-300"></i>
                <span class="text-slate-600">Tổng quan</span>
            </div>

            <!-- Welcome Banner Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Welcome Banner -->
                <div class="lg:col-span-2 bg-gradient-to-tr from-slate-950 via-[#064e3b] to-emerald-950 text-white rounded-3xl p-8 relative overflow-hidden flex flex-col justify-between min-h-[180px] shadow-lg shadow-emerald-950/10">
                    <div class="absolute -right-6 -bottom-10 opacity-5 text-[160px] pointer-events-none">
                        <i class="bi bi-house-door-fill"></i>
                    </div>
                    <div class="space-y-2 z-10">
                        <h2 class="text-xl md:text-2xl font-black font-headline tracking-tight">Chào mừng quay lại, {{ user?.name || 'Phạm Mạnh Dũng' }} 👋</h2>
                        <p class="text-xs text-emerald-100/80 leading-relaxed max-w-lg font-medium">
                            Bạn muốn tạo hóa đơn cho kỳ thanh toán này? Hãy nhanh chóng kiểm tra chỉ số và tạo hóa đơn cho khách thuê để duy trì vận hành trơn tru.
                        </p>
                    </div>
                    <div class="z-10 mt-6">
                        <Link href="/landlord/invoices" class="px-6 py-3 bg-white text-emerald-900 font-extrabold text-xs rounded-xl shadow-lg hover:shadow-white/10 hover:-translate-y-0.5 active:scale-95 transition-all inline-block">
                            Tạo hóa đơn ngay
                        </Link>
                    </div>
                </div>

                <!-- Side Info Panel -->
                <div class="bg-white border border-slate-100 rounded-3xl p-8 flex flex-col justify-between shadow-[0_8px_30px_rgb(0,0,0,0.015)] relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-amber-500/10 to-transparent rounded-bl-full pointer-events-none"></div>
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 text-[8px] font-extrabold text-amber-700 bg-amber-50 border border-amber-200/50 rounded-md uppercase tracking-wider">PRO</span>
                            <span class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Phân quyền dễ dàng</span>
                        </div>
                        <p class="text-xs text-slate-400 font-semibold leading-relaxed mt-2">
                            Thêm nhân viên vận hành, đồng quản lý các cơ sở trọ giúp bạn tiết kiệm đến 80% thời gian quản trị trực tiếp.
                        </p>
                    </div>
                    <button class="w-full mt-6 py-3 bg-slate-50 border border-slate-200/80 hover:bg-slate-100/80 text-slate-700 font-extrabold text-xs rounded-xl transition-all duration-300">
                        Trải nghiệm Premium
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Stat Card 1 -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 flex items-center justify-between shadow-[0_8px_30px_rgb(0,0,0,0.01)] hover:-translate-y-1 hover:shadow-md hover:border-slate-200/60 transition-all duration-300">
                    <div class="space-y-3">
                        <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Doanh thu tháng gần nhất</p>
                        <h3 class="text-2xl font-black font-headline tracking-tight text-slate-900">{{ formatMoney(stats.recentRevenue) }}</h3>
                        <div class="flex items-center gap-1.5 text-xs text-slate-400 font-bold">
                            <span class="text-slate-600 bg-slate-50 px-2 py-0.5 rounded-lg border border-slate-100">{{ stats.recentRevenueTrend }}%</span>
                            <span>So với tháng trước</span>
                        </div>
                    </div>
                    <!-- Sparkline -->
                    <div class="text-emerald-500 filter drop-shadow-[0_2px_8px_rgba(16,185,129,0.15)]">
                        <svg class="w-24 h-12" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M0,22 Q15,6 30,19 T60,9 T90,23" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

                <!-- Stat Card 2 -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 flex items-center justify-between shadow-[0_8px_30px_rgb(0,0,0,0.01)] hover:-translate-y-1 hover:shadow-md hover:border-slate-200/60 transition-all duration-300">
                    <div class="space-y-3">
                        <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Tổng khách hàng</p>
                        <h3 class="text-2xl font-black font-headline tracking-tight text-slate-900">{{ stats.totalTenants }}</h3>
                        <div class="flex items-center gap-1.5 text-xs text-slate-400 font-bold">
                            <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-100/60">+{{ stats.totalTenantsTrend }}%</span>
                            <span>So với tháng trước</span>
                        </div>
                    </div>
                    <!-- Sparkline -->
                    <div class="text-emerald-500 filter drop-shadow-[0_2px_8px_rgba(16,185,129,0.15)]">
                        <svg class="w-24 h-12" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M0,25 Q20,10 40,20 T80,5 T100,15" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

                <!-- Stat Card 3 -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 flex items-center justify-between shadow-[0_8px_30px_rgb(0,0,0,0.01)] hover:-translate-y-1 hover:shadow-md hover:border-slate-200/60 transition-all duration-300">
                    <div class="space-y-3">
                        <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Tổng doanh thu</p>
                        <h3 class="text-2xl font-black font-headline tracking-tight text-slate-900">{{ formatMoney(stats.totalRevenue) }}</h3>
                        <div class="flex items-center gap-1.5 text-xs text-slate-400 font-bold">
                            <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-100/60">+{{ stats.totalRevenueTrend }}%</span>
                            <span>Tất cả thời gian</span>
                        </div>
                    </div>
                    <!-- Sparkline -->
                    <div class="text-emerald-500 filter drop-shadow-[0_2px_8px_rgba(16,185,129,0.15)]">
                        <svg class="w-24 h-12" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M0,16 Q30,29 60,11 T100,6" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Room Occupancy Pie Chart -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.01)] flex flex-col justify-between">
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-800">Tình trạng phòng</h3>
                        <p class="text-[11px] text-slate-400 font-medium">Tỷ lệ phòng trống và đang cho thuê</p>
                    </div>
                    
                    <!-- SVG Donut Chart -->
                    <div class="flex items-center justify-center py-6">
                        <div class="relative w-40 h-40">
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                <!-- Background Circle (vacant - light grey) -->
                                <circle cx="18" cy="18" r="15.915" fill="none" stroke="#f1f5f9" stroke-width="4.2"/>
                                <!-- Segment Circle (occupied - emerald) -->
                                <circle cx="18" cy="18" r="15.915" fill="none" stroke="url(#emeraldGradient)" stroke-width="4.2"
                                    stroke-linecap="round"
                                    stroke-dasharray="85.7 14.3"
                                    stroke-dashoffset="0"/>
                                <defs>
                                    <linearGradient id="emeraldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#10b981"/>
                                        <stop offset="100%" stop-color="#059669"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-3xl font-black text-slate-900 tracking-tight">85.7%</span>
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest mt-0.5">Lấp đầy</span>
                            </div>
                        </div>
                    </div>

                    <!-- Legend list -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between text-xs font-bold border-b border-slate-50 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span class="text-slate-500 font-semibold">Đang cho thuê</span>
                            </div>
                            <span class="text-slate-800">6 phòng (85.7%)</span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-bold">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-200"></span>
                                <span class="text-slate-500 font-semibold">Phòng trống</span>
                            </div>
                            <span class="text-slate-800">1 phòng (14.3%)</span>
                        </div>
                    </div>
                </div>

                <!-- Service Revenues Chart -->
                <div class="lg:col-span-2 bg-white border border-slate-100 rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.01)] flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <h3 class="text-sm font-bold text-slate-800">Doanh thu theo dịch vụ</h3>
                            <p class="text-[11px] text-slate-400 font-medium">Xu hướng biến động tiền phòng và tiền dịch vụ</p>
                        </div>
                        <select class="text-xs font-bold text-slate-500 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 outline-none cursor-pointer hover:bg-slate-100 transition-colors">
                            <option>Năm 2026</option>
                            <option>Năm 2025</option>
                        </select>
                    </div>

                    <!-- SVG Multi Line Chart -->
                    <div class="py-6 flex-1 min-h-[180px] relative flex flex-col justify-end">
                        <svg class="w-full h-40 text-slate-100" viewBox="0 0 500 120">
                            <!-- Grid lines -->
                            <line x1="0" y1="20" x2="500" y2="20" stroke="currentColor" stroke-dasharray="4,4" stroke-width="0.75"/>
                            <line x1="0" y1="50" x2="500" y2="50" stroke="currentColor" stroke-dasharray="4,4" stroke-width="0.75"/>
                            <line x1="0" y1="80" x2="500" y2="80" stroke="currentColor" stroke-dasharray="4,4" stroke-width="0.75"/>
                            <line x1="0" y1="110" x2="500" y2="110" stroke="currentColor" stroke-width="0.75"/>

                            <!-- Total Line (Emerald) -->
                            <path d="M0,90 L100,80 L200,85 L300,70 L400,60 L500,50" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round"/>
                            <!-- Room Line (Blue) -->
                            <path d="M0,105 L100,105 L200,105 L300,105 L400,105 L500,105" fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round"/>
                            <!-- Electric Line (Yellow) -->
                            <path d="M0,115 L100,112 L200,114 L300,110 L400,108 L500,110" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <div class="flex justify-between text-[10px] font-bold text-slate-400 mt-3 px-1 tracking-wider">
                            <span>T1</span>
                            <span>T2</span>
                            <span>T3</span>
                            <span>T4</span>
                            <span>T5</span>
                            <span>T6</span>
                        </div>
                    </div>

                    <!-- Chart Legend -->
                    <div class="flex flex-wrap gap-x-4 gap-y-2 border-t border-slate-50 pt-4">
                        <div v-for="srv in servicesRevenue" :key="srv.name" class="flex items-center gap-1.5 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: srv.color }"></span>
                            <span class="text-slate-500 font-semibold">{{ srv.name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table: Biến động theo dịch vụ -->
            <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.015)] overflow-hidden">
                <div class="space-y-1 mb-6">
                    <h3 class="text-sm font-bold text-slate-800">Biến động chi tiết dịch vụ</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Chi tiết phân bổ doanh thu hóa đơn thực tế theo tháng</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="border-b border-slate-100 text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">
                                <th class="pb-3.5 pr-4">Hóa đơn</th>
                                <th class="pb-3.5 px-4">Tiền nhà</th>
                                <th class="pb-3.5 px-4">Tiền điện</th>
                                <th class="pb-3.5 px-4">Tiền nước</th>
                                <th class="pb-3.5 px-4">Internet</th>
                                <th class="pb-3.5 px-4">Thu gom rác</th>
                                <th class="pb-3.5 px-4">Giữ xe</th>
                                <th class="pb-3.5 px-4">Quản lý</th>
                                <th class="pb-3.5 pl-4 text-right">Tổng cộng</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs font-bold text-slate-600">
                            <tr v-for="row in serviceRevenuesTable" :key="row.invoice" class="hover:bg-slate-50/70 transition-colors duration-150">
                                <td class="py-4 pr-4 font-black text-slate-800">{{ row.invoice }}</td>
                                <td class="py-4 px-4 font-semibold text-slate-500">{{ formatMoney(row.room) }}</td>
                                <td class="py-4 px-4 font-semibold text-slate-500">{{ formatMoney(row.electric) }}</td>
                                <td class="py-4 px-4 font-semibold text-slate-500">{{ formatMoney(row.water) }}</td>
                                <td class="py-4 px-4 font-semibold text-slate-500">{{ formatMoney(row.wifi) }}</td>
                                <td class="py-4 px-4 font-semibold text-slate-500">{{ formatMoney(row.garbage) }}</td>
                                <td class="py-4 px-4 font-semibold text-slate-500">{{ formatMoney(row.parking) }}</td>
                                <td class="py-4 px-4 font-semibold text-slate-500">{{ formatMoney(row.manage) }}</td>
                                <td class="py-4 pl-4 text-right font-black text-emerald-600">{{ formatMoney(row.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottom columns list -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Hợp đồng mới -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.01)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Hợp đồng mới</h3>
                            <Link href="/landlord/contracts" class="text-xs font-extrabold text-emerald-600 hover:text-emerald-700 transition-colors">Xem thêm</Link>
                        </div>
                        <div class="space-y-3.5">
                            <div v-for="item in recentContracts" :key="item.id" class="flex items-center justify-between p-3.5 bg-slate-50/60 border border-slate-100 rounded-2xl hover:bg-slate-50 hover:border-slate-200/80 transition-all duration-300">
                                <div class="space-y-1">
                                    <p class="text-xs font-black text-slate-800">{{ item.tenant }}</p>
                                    <p class="text-[10px] text-slate-400 font-extrabold">{{ item.room }} &middot; Ký ngày: {{ item.date }}</p>
                                </div>
                                <span :class="[
                                    'px-2.5 py-0.5 rounded-lg text-[9px] font-extrabold uppercase tracking-wider border',
                                    item.status === 'signed' ? 'bg-emerald-50 text-emerald-700 border-emerald-100/60' : 'bg-amber-50 text-amber-700 border-amber-100/60'
                                ]">
                                    {{ item.status === 'signed' ? 'Đã ký' : 'Chờ ký' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hóa đơn mới -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.01)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Hóa đơn mới</h3>
                            <Link href="/landlord/invoices" class="text-xs font-extrabold text-emerald-600 hover:text-emerald-700 transition-colors">Xem thêm</Link>
                        </div>
                        <div class="space-y-3.5">
                            <div v-for="item in recentInvoices" :key="item.id" class="flex items-center justify-between p-3.5 bg-slate-50/60 border border-slate-100 rounded-2xl hover:bg-slate-50 hover:border-slate-200/80 transition-all duration-300">
                                <div class="space-y-1">
                                    <p class="text-xs font-black text-slate-800">{{ item.code }}</p>
                                    <p class="text-[10px] text-slate-400 font-extrabold">{{ item.room }} &middot; {{ formatMoney(item.amount) }}</p>
                                </div>
                                <span :class="[
                                    'px-2.5 py-0.5 rounded-lg text-[9px] font-extrabold uppercase tracking-wider border',
                                    item.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-100/60' : 'bg-rose-50 text-rose-700 border-rose-100/60'
                                ]">
                                    {{ item.status === 'paid' ? 'Đã thu' : 'Chưa thu' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Khách hàng mới -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.01)] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Khách hàng mới</h3>
                            <Link href="/landlord/tenants" class="text-xs font-extrabold text-emerald-600 hover:text-emerald-700 transition-colors">Xem thêm</Link>
                        </div>
                        <div class="space-y-3.5">
                            <div v-for="item in recentTenants" :key="item.id" class="flex items-center justify-between p-3.5 bg-slate-50/60 border border-slate-100 rounded-2xl hover:bg-slate-50 hover:border-slate-200/80 transition-all duration-300">
                                <div class="space-y-2 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-black text-slate-800">{{ item.name }}</p>
                                        <span class="text-[9px] text-emerald-700 font-extrabold bg-emerald-50 border border-emerald-100/60 px-2 py-0.5 rounded-lg uppercase tracking-wide">{{ item.room }}</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-extrabold">SĐT: {{ item.phone }} &middot; Thuê từ: {{ item.date }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
