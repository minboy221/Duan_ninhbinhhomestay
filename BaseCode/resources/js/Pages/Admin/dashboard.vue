<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalUsers: 0,
            newUsersToday: 0,
            pendingApproval: 0,
            pendingBoardingHouses: 0,
            reports: 0,
            totalLandlords: 0,
        })
    },
    recentUsers: {
        type: Array,
        default: () => []
    },
    recentReports: {
        type: Array,
        default: () => []
    },
    monthlyChart: {
        type: Object,
        default: () => ({
            months: ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12'],
            counts: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        })
    }
})

// Tạo sparkline SVG path
function sparkline(data, w = 100, h = 36) {
    if (!data || !data.length) return ''
    const max = Math.max(...data), min = Math.min(...data)
    const range = max - min || 1
    const sx = w / (data.length - 1)
    const pts = data.map((v, i) => `${(i * sx).toFixed(1)},${(h - ((v - min) / range) * h).toFixed(1)}`)
    return `M ${pts.join(' L ')}`
}

const sparkUsers   = [20, 35, 28, 45, 38, 52, 61, 48, 70, 65, 80, 75]
const sparkPending = [5, 8, 12, 7, 14, 10, 18, 15, 22, 19, 25, 20]
const sparkReport  = [2, 4, 3, 6, 4, 7, 5, 8, 6, 9, 7, 10]

// Bar chart data (users per month from DB)
const barData = computed(() => props.monthlyChart?.counts || [])
const barMax  = computed(() => Math.max(...barData.value, 1))
const months  = computed(() => props.monthlyChart?.months || [])
</script>

<template>
    <Head title="Admin - Tổng Quan" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Tổng Quan Hệ Thống</h1>
                <p class="page-sub">Chào mừng trở lại! Đây là tổng quan hoạt động hệ thống hôm nay.</p>
            </div>
        </template>

        <!-- Stat Cards -->
        <div class="stat-grid">
            <!-- Users -->
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon" style="background:#eff6ff">
                        <i class="bi bi-people-fill" style="color:#3b82f6"></i>
                    </div>
                    <span class="stat-badge badge-green">
                        <i class="bi bi-arrow-up-short"></i>+{{ stats.newUsersToday }} hôm nay
                    </span>
                </div>
                <p class="stat-num">{{ stats.totalUsers }}</p>
                <p class="stat-label">Tổng Người Dùng</p>
                <svg class="sparkline" viewBox="0 0 100 36" preserveAspectRatio="none">
                    <path :d="sparkline(sparkUsers)" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Pending -->
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon" style="background:#fff7ed">
                        <i class="bi bi-clock-fill" style="color:#f97316"></i>
                    </div>
                    <span class="stat-badge badge-orange">Chờ duyệt</span>
                </div>
                <p class="stat-num">{{ stats.pendingApproval }}</p>
                <p class="stat-label">Tin Đăng Chờ Duyệt</p>
                <svg class="sparkline" viewBox="0 0 100 36" preserveAspectRatio="none">
                    <path :d="sparkline(sparkPending)" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Reports -->
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon" style="background:#fef2f2">
                        <i class="bi bi-flag-fill" style="color:#ef4444"></i>
                    </div>
                    <span class="stat-badge badge-red">Cần xử lý</span>
                </div>
                <p class="stat-num">{{ stats.reports }}</p>
                <p class="stat-label">Báo Cáo Vi Phạm</p>
                <svg class="sparkline" viewBox="0 0 100 36" preserveAspectRatio="none">
                    <path :d="sparkline(sparkReport)" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Landlords Count -->
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon" style="background:#f0fdf4">
                        <i class="bi bi-house-check-fill" style="color:#22c55e"></i>
                    </div>
                    <span class="stat-badge badge-green">Đã xác minh</span>
                </div>
                <p class="stat-num">{{ stats.totalLandlords }}</p>
                <p class="stat-label">Tài Khoản Chủ Trọ</p>
                <svg class="sparkline" viewBox="0 0 100 36" preserveAspectRatio="none">
                    <path :d="sparkline(sparkUsers)" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <!-- Charts + Tables -->
        <div class="dash-grid">
            <!-- Bar Chart: Users per month -->
            <div class="dash-card dash-chart">
                <div class="card-head">
                    <h3 class="card-title">Người Dùng Đăng Ký</h3>
                    <span class="card-sub">12 tháng gần nhất</span>
                </div>
                <div class="bar-chart">
                    <div v-for="(val, i) in barData" :key="i" class="bar-col">
                        <div class="bar-wrap">
                            <div
                                class="bar"
                                :style="`height:${(val / barMax) * 100}%; background: ${i === barData.length - 1 ? '#7c3aed' : '#c4b5fd'}`"
                                :title="`${months[i]}: ${val} người`"
                            ></div>
                        </div>
                        <span class="bar-label">{{ months[i] }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="dash-card">
                <div class="card-head">
                    <h3 class="card-title">Thao Tác Nhanh</h3>
                </div>
                <div class="quick-actions">
                    <Link :href="route('admin.listings.index')" class="qa-btn qa-orange">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Duyệt tin đăng</span>
                    </Link>
                    <Link :href="route('admin.reports')" class="qa-btn qa-red">
                        <i class="bi bi-flag-fill"></i>
                        <span>Xử lý báo cáo</span>
                    </Link>
                    <Link :href="route('admin.users')" class="qa-btn qa-blue">
                        <i class="bi bi-people-fill"></i>
                        <span>Quản lý users</span>
                    </Link>
                    <Link :href="route('admin.landlords')" class="qa-btn qa-purple">
                        <i class="bi bi-house-check-fill"></i>
                        <span>Duyệt chủ trọ</span>
                    </Link>
                    <Link :href="route('admin.boarding-houses.index')" class="qa-btn qa-green">
                        <i class="bi bi-building-check"></i>
                        <span>Cơ sở trọ mới</span>
                    </Link>
                    <Link :href="route('admin.auditlog')" class="qa-btn qa-gray">
                        <i class="bi bi-journal-text"></i>
                        <span>Audit log</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Bottom tables -->
        <div class="dash-grid mt-5">
            <!-- Recent Users -->
            <div class="dash-card">
                <div class="card-head">
                    <h3 class="card-title">Người Dùng Mới</h3>
                    <Link :href="route('admin.users')" class="card-link">Xem tất cả <i class="bi bi-arrow-right"></i></Link>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Người dùng</th>
                            <th>Loại TK</th>
                            <th>Ngày đăng ký</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="recentUsers.length === 0">
                            <td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">
                                Chưa có người dùng mới.
                            </td>
                        </tr>
                        <tr v-for="u in recentUsers" :key="u.id">
                            <td>
                                <div class="user-cell">
                                    <div class="user-ava" style="overflow: hidden; padding: 0; background: #f1f5f9;">
                                        <img v-if="u.avatar" :src="u.avatar.startsWith('/') || u.avatar.startsWith('http') ? u.avatar : `/storage/${u.avatar}`" style="width:100%;height:100%;object-fit:cover;" />
                                        <span v-else>{{ u.name ? u.name[0].toUpperCase() : 'U' }}</span>
                                    </div>
                                    <div>
                                        <p class="user-name">{{ u.name }}</p>
                                        <p class="user-email">{{ u.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td><span :class="['role-badge', u.role === 'Chủ trọ' ? 'role-purple' : 'role-blue']">{{ u.role }}</span></td>
                            <td class="text-gray">{{ u.date }}</td>
                            <td>
                                <span :class="['status-dot', u.status === 'active' ? 'dot-green' : 'dot-red']">
                                    {{ u.status === 'active' ? 'Hoạt động' : 'Bị khóa' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Recent Reports -->
            <div class="dash-card">
                <div class="card-head">
                    <h3 class="card-title">Báo Cáo Gần Đây</h3>
                    <Link :href="route('admin.reports')" class="card-link">Xem tất cả <i class="bi bi-arrow-right"></i></Link>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Người báo cáo</th>
                            <th>Đối tượng</th>
                            <th>Loại vi phạm</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="recentReports.length === 0">
                            <td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">
                                Chưa có báo cáo vi phạm nào.
                            </td>
                        </tr>
                        <tr v-for="r in recentReports" :key="r.id">
                            <td class="font-medium">{{ r.from }}</td>
                            <td class="text-gray">{{ r.target }}</td>
                            <td><span class="type-badge">{{ r.type }}</span></td>
                            <td>
                                <span :class="['status-dot', r.status === 'resolved' ? 'dot-green' : 'dot-orange']">
                                    {{ r.status === 'resolved' ? 'Đã xử lý' : 'Chờ xử lý' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.page-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }
.page-sub   { font-size: 12px; color: #94a3b8; margin: 2px 0 0; }

/* Stat grid */
.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
@media(max-width:1100px){ .stat-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:600px){ .stat-grid { grid-template-columns: 1fr; } }

.stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    transition: box-shadow 0.2s;
}
.stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }

.stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.stat-icon { width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.stat-badge { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 99px; }
.badge-green  { background: #f0fdf4; color: #16a34a; }
.badge-orange { background: #fff7ed; color: #ea580c; }
.badge-red    { background: #fef2f2; color: #dc2626; }

.stat-num   { font-size: 30px; font-weight: 800; color: #0f172a; margin: 0; line-height: 1; }
.stat-label { font-size: 12px; color: #94a3b8; margin: 4px 0 12px; }
.sparkline  { width: 100%; height: 36px; display: block; }

/* Dash grid */
.dash-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; }
@media(max-width:900px){ .dash-grid { grid-template-columns: 1fr; } }
.mt-5 { margin-top: 20px; }

.dash-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}

.card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.card-title { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0; }
.card-sub   { font-size: 11px; color: #94a3b8; }
.card-link  { font-size: 12px; color: #7c3aed; text-decoration: none; font-weight: 500; }
.card-link:hover { color: #6d28d9; }

/* Bar chart */
.bar-chart { display: flex; align-items: flex-end; gap: 6px; height: 180px; padding-top: 8px; }
.bar-col   { display: flex; flex-direction: column; align-items: center; flex: 1; gap: 4px; height: 100%; }
.bar-wrap  { flex: 1; display: flex; align-items: flex-end; width: 100%; }
.bar       { width: 100%; border-radius: 4px 4px 0 0; transition: opacity 0.2s; cursor: default; }
.bar:hover { opacity: 0.75; }
.bar-label { font-size: 9px; color: #94a3b8; }

/* Quick actions */
.quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.qa-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 11px 12px; border-radius: 8px;
    font-size: 12.5px; font-weight: 600;
    text-decoration: none;
    transition: transform 0.15s, box-shadow 0.15s;
}
.qa-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.qa-btn i { font-size: 16px; }
.qa-orange { background: #fff7ed; color: #ea580c; }
.qa-red    { background: #fef2f2; color: #dc2626; }
.qa-blue   { background: #eff6ff; color: #2563eb; }
.qa-purple { background: #faf5ff; color: #7c3aed; }
.qa-green  { background: #f0fdf4; color: #16a34a; }
.qa-gray   { background: #f8fafc; color: #475569; }

/* Tables */
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th { text-align: left; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; padding: 0 8px 10px; border-bottom: 1px solid #f1f5f9; }
.data-table td { padding: 11px 8px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }

.user-cell  { display: flex; align-items: center; gap: 10px; }
.user-ava   { width: 32px; height: 32px; border-radius: 6px; background: linear-gradient(135deg,#7c3aed,#4f46e5); color:#fff; display:flex;align-items:center;justify-content:center; font-size:13px;font-weight:700;flex-shrink:0; }
.user-name  { font-size: 13px; font-weight: 600; color: #0f172a; margin: 0; }
.user-email { font-size: 11px; color: #94a3b8; margin: 0; }
.font-medium { font-weight: 600; color: #0f172a; }
.text-gray  { color: #64748b; }

.role-badge { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 99px; }
.role-blue   { background: #eff6ff; color: #2563eb; }
.role-purple { background: #faf5ff; color: #7c3aed; }

.status-dot { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 99px; display: inline-flex; align-items: center; gap: 4px; }
.status-dot::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; }
.dot-green::before  { background: #22c55e; }
.dot-red::before    { background: #ef4444; }
.dot-orange::before { background: #f97316; }
.dot-green  { background: #f0fdf4; color: #16a34a; }
.dot-red    { background: #fef2f2; color: #dc2626; }
.dot-orange { background: #fff7ed; color: #ea580c; }

.type-badge { font-size: 11px; background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 99px; font-weight: 600; }
</style>