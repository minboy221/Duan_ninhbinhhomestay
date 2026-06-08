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
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Tổng quan</span>
            </div>

            <!-- Welcome Banner Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Welcome Banner -->
                <div class="lg:col-span-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-3xl p-6 relative overflow-hidden flex flex-col justify-between min-h-[160px] shadow-sm shadow-teal-100">
                    <div class="absolute -right-4 -bottom-6 opacity-10 text-[120px] pointer-events-none">
                        <i class="bi bi-house"></i>
                    </div>
                    <div class="space-y-2 z-10">
                        <h2 class="text-lg md:text-xl font-bold">Chào mừng 👋 {{ user?.name || 'Phạm Mạnh Dũng' }}</h2>
                        <p class="text-xs text-emerald-50 leading-relaxed max-w-md">
                            Bạn muốn tạo hóa đơn cho kỳ thanh toán này? Hãy nhanh chóng kiểm tra chỉ số và tạo hóa đơn cho khách thuê.
                        </p>
                    </div>
                    <div class="z-10 mt-4">
                        <Link href="/landlord/invoices" class="px-5 py-2.5 bg-white text-emerald-600 font-bold text-xs rounded-xl shadow-md shadow-emerald-950/10 hover:bg-emerald-50 transition-colors inline-block">
                            Tạo ngay
                        </Link>
                    </div>
                </div>

                <!-- Side Info Panel -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 flex flex-col justify-between shadow-sm">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 text-[10px] font-bold text-teal-600 bg-teal-50 border border-teal-200 rounded-md uppercase">PRO</span>
                            <span class="text-xs font-bold text-slate-800">Phân quyền dễ dàng</span>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed mt-2">
                            Thêm nhân viên vận hành, quản lý cơ sở trọ giúp bạn tiết kiệm 80% thời gian quản lý.
                        </p>
                    </div>
                    <button class="w-full mt-4 py-2.5 bg-slate-50 border border-slate-150 hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-xl transition-colors">
                        Trải nghiệm ngay
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Stat Card 1 -->
                <div class="bg-white border border-slate-100 rounded-2xl p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-2">
                        <p class="text-xs font-bold text-slate-400">Doanh thu tháng gần nhất</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ formatMoney(stats.recentRevenue) }}</h3>
                        <div class="flex items-center gap-1.5 text-xs text-slate-400 font-semibold">
                            <span class="text-slate-600 bg-slate-50 px-1.5 py-0.5 rounded">{{ stats.recentRevenueTrend }}%</span>
                            <span>So với tháng trước</span>
                        </div>
                    </div>
                    <!-- Sparkline -->
                    <div class="text-emerald-500">
                        <svg class="w-20 h-10" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M0,20 Q15,5 30,18 T60,8 T90,22" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

                <!-- Stat Card 2 -->
                <div class="bg-white border border-slate-100 rounded-2xl p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-2">
                        <p class="text-xs font-bold text-slate-400">Tổng khách hàng</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ stats.totalTenants }}</h3>
                        <div class="flex items-center gap-1.5 text-xs text-slate-400 font-semibold">
                            <span class="text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">+{{ stats.totalTenantsTrend }}%</span>
                            <span>So với tháng trước</span>
                        </div>
                    </div>
                    <!-- Sparkline -->
                    <div class="text-emerald-500">
                        <svg class="w-20 h-10" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M0,25 Q20,10 40,20 T80,5 T100,15" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

                <!-- Stat Card 3 -->
                <div class="bg-white border border-slate-100 rounded-2xl p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-2">
                        <p class="text-xs font-bold text-slate-400">Tổng doanh thu</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ formatMoney(stats.totalRevenue) }}</h3>
                        <div class="flex items-center gap-1.5 text-xs text-slate-400 font-semibold">
                            <span class="text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">+{{ stats.totalRevenueTrend }}%</span>
                            <span>Tất cả thời gian</span>
                        </div>
                    </div>
                    <!-- Sparkline -->
                    <div class="text-emerald-500">
                        <svg class="w-20 h-10" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M0,15 Q30,28 60,10 T100,5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Room Occupancy Pie Chart (SVG representation) -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-800">Tình trạng phòng</h3>
                        <p class="text-[11px] text-slate-400">Tỷ lệ phòng trống và đang cho thuê</p>
                    </div>
                    
                    <!-- SVG Donut Chart -->
                    <div class="flex items-center justify-center py-6">
                        <div class="relative w-40 h-40">
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                <!-- Background Circle (vacant - red) -->
                                <circle cx="18" cy="18" r="15.915" fill="none" stroke="#fee2e2" stroke-width="4.2"/>
                                <!-- Segment Circle (occupied - emerald) -->
                                <circle cx="18" cy="18" r="15.915" fill="none" stroke="#10b981" stroke-width="4.2"
                                    stroke-dasharray="85.7 14.3"
                                    stroke-dashoffset="0"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-black text-slate-800">85.7%</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Lấp đầy</span>
                            </div>
                        </div>
                    </div>

                    <!-- Legend list -->
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded bg-emerald-500"></span>
                                <span class="text-slate-600">Phòng Đang Cho Thuê</span>
                            </div>
                            <span class="text-slate-800">6 phòng (85.7%)</span>
                        </div>
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded bg-rose-200"></span>
                                <span class="text-slate-600">Phòng Trống</span>
                            </div>
                            <span class="text-slate-800">1 phòng (14.3%)</span>
                        </div>
                    </div>
                </div>

                <!-- Service Revenues Chart (SVG Representation) -->
                <div class="lg:col-span-2 bg-white border border-slate-100 rounded-3xl p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <h3 class="text-sm font-bold text-slate-800">Doanh thu theo dịch vụ</h3>
                            <p class="text-[11px] text-slate-400">Xu hướng biến động tiền phòng và tiền dịch vụ</p>
                        </div>
                        <select class="text-xs font-bold text-slate-500 bg-slate-50 border border-slate-250 rounded-lg px-2.5 py-1.5 outline-none cursor-pointer">
                            <option>2026</option>
                            <option>2025</option>
                        </select>
                    </div>

                    <!-- SVG Multi Line Chart -->
                    <div class="py-6 flex-1 min-h-[180px] relative flex flex-col justify-end">
                        <svg class="w-full h-40 text-slate-200" viewBox="0 0 500 120">
                            <!-- Grid lines -->
                            <line x1="0" y1="20" x2="500" y2="20" stroke="currentColor" stroke-dasharray="3,3" stroke-width="0.5"/>
                            <line x1="0" y1="50" x2="500" y2="50" stroke="currentColor" stroke-dasharray="3,3" stroke-width="0.5"/>
                            <line x1="0" y1="80" x2="500" y2="80" stroke="currentColor" stroke-dasharray="3,3" stroke-width="0.5"/>
                            <line x1="0" y1="110" x2="500" y2="110" stroke="currentColor" stroke-width="0.5"/>

                            <!-- Total Line (Emerald) -->
                            <path d="M0,90 L100,80 L200,85 L300,70 L400,60 L500,50" fill="none" stroke="#10b981" stroke-width="3"/>
                            <!-- Room Line (Blue) -->
                            <path d="M0,105 L100,105 L200,105 L300,105 L400,105 L500,105" fill="none" stroke="#3b82f6" stroke-width="2"/>
                            <!-- Electric Line (Yellow) -->
                            <path d="M0,115 L100,112 L200,114 L300,110 L400,108 L500,110" fill="none" stroke="#f59e0b" stroke-width="1.5"/>
                        </svg>
                        <div class="flex justify-between text-[10px] font-bold text-slate-400 mt-2 px-1">
                            <span>Tháng 1</span>
                            <span>Tháng 2</span>
                            <span>Tháng 3</span>
                            <span>Tháng 4</span>
                            <span>Tháng 5</span>
                            <span>Tháng 6</span>
                        </div>
                    </div>

                    <!-- Chart Legend -->
                    <div class="flex flex-wrap gap-x-4 gap-y-2 border-t border-slate-50 pt-4">
                        <div v-for="srv in servicesRevenue" :key="srv.name" class="flex items-center gap-1.5 text-xs font-semibold">
                            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: srv.color }"></span>
                            <span class="text-slate-500">{{ srv.name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table: Biến động theo dịch vụ -->
            <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm overflow-hidden">
                <div class="space-y-1 mb-5">
                    <h3 class="text-sm font-bold text-slate-800">Biến động theo dịch vụ</h3>
                    <p class="text-[11px] text-slate-400">Chi tiết doanh thu hóa đơn theo tháng</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="border-b border-slate-100 text-xs font-bold text-slate-400">
                                <th class="pb-3 pr-4">Hóa đơn</th>
                                <th class="pb-3 px-4">Tiền nhà</th>
                                <th class="pb-3 px-4">Điện</th>
                                <th class="pb-3 px-4">Nước</th>
                                <th class="pb-3 px-4">Internet</th>
                                <th class="pb-3 px-4">Rác</th>
                                <th class="pb-3 px-4">Giữ xe</th>
                                <th class="pb-3 px-4">Quản lý</th>
                                <th class="pb-3 pl-4 text-right">Tổng</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-600">
                            <tr v-for="row in serviceRevenuesTable" :key="row.invoice" class="hover:bg-slate-50/50">
                                <td class="py-4 pr-4 font-bold text-slate-800">{{ row.invoice }}</td>
                                <td class="py-4 px-4">{{ formatMoney(row.room) }}</td>
                                <td class="py-4 px-4">{{ formatMoney(row.electric) }}</td>
                                <td class="py-4 px-4">{{ formatMoney(row.water) }}</td>
                                <td class="py-4 px-4">{{ formatMoney(row.wifi) }}</td>
                                <td class="py-4 px-4">{{ formatMoney(row.garbage) }}</td>
                                <td class="py-4 px-4">{{ formatMoney(row.parking) }}</td>
                                <td class="py-4 px-4">{{ formatMoney(row.manage) }}</td>
                                <td class="py-4 pl-4 text-right font-bold text-emerald-600">{{ formatMoney(row.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottom columns list -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Hợp đồng mới -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Hợp đồng mới</h3>
                        <Link href="/landlord/contracts" class="text-xs font-bold text-emerald-500 hover:underline">Xem thêm</Link>
                    </div>
                    <div class="space-y-3">
                        <div v-for="item in recentContracts" :key="item.id" class="flex items-center justify-between p-3 bg-slate-50/50 border border-slate-100 rounded-xl hover:bg-slate-50 transition-colors">
                            <div class="space-y-1">
                                <p class="text-xs font-bold text-slate-800">{{ item.tenant }}</p>
                                <p class="text-[10px] text-slate-400 font-semibold">{{ item.room }} &middot; Ngày: {{ item.date }}</p>
                            </div>
                            <span :class="[
                                'px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider',
                                item.status === 'signed' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-amber-50 text-amber-600 border border-amber-100'
                            ]">
                                {{ item.status === 'signed' ? 'Đã ký' : 'Chờ ký' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hóa đơn mới -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Hóa đơn mới</h3>
                        <Link href="/landlord/invoices" class="text-xs font-bold text-emerald-500 hover:underline">Xem thêm</Link>
                    </div>
                    <div class="space-y-3">
                        <div v-for="item in recentInvoices" :key="item.id" class="flex items-center justify-between p-3 bg-slate-50/50 border border-slate-100 rounded-xl hover:bg-slate-50 transition-colors">
                            <div class="space-y-1">
                                <p class="text-xs font-bold text-slate-800">{{ item.code }}</p>
                                <p class="text-[10px] text-slate-400 font-semibold">{{ item.room }} &middot; {{ formatMoney(item.amount) }}</p>
                            </div>
                            <span :class="[
                                'px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider',
                                item.status === 'paid' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100'
                            ]">
                                {{ item.status === 'paid' ? 'Đã thu' : 'Chưa thu' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Khách hàng mới -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Khách hàng mới</h3>
                        <Link href="/landlord/tenants" class="text-xs font-bold text-emerald-500 hover:underline">Xem thêm</Link>
                    </div>
                    <div class="space-y-3">
                        <div v-for="item in recentTenants" :key="item.id" class="flex items-center justify-between p-3 bg-slate-50/50 border border-slate-100 rounded-xl hover:bg-slate-50 transition-colors">
                            <div class="space-y-1 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-bold text-slate-800">{{ item.name }}</p>
                                    <span class="text-[10px] text-emerald-500 font-bold bg-emerald-50/70 border border-emerald-100 px-1.5 py-0.5 rounded">{{ item.room }}</span>
                                </div>
                                <p class="text-[10px] text-slate-400 font-semibold">SĐT: {{ item.phone }} &middot; Bắt đầu: {{ item.date }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
