<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed } from 'vue'

const stats = ref({
    totalRooms: 12,
    occupied: 9,
    vacant: 2,
    maintenance: 1,
    monthRevenue: 27500000,
    pendingPayments: 3,
    expiringContracts: 2,
    pendingAppointments: 4,
})

const recentActivities = ref([
    { id: 1, type: 'payment', message: 'Phòng 101 - Nguyễn Văn A đã thanh toán tháng 5', time: '2 giờ trước', color: 'green' },
    { id: 2, type: 'contract', message: 'Hợp đồng phòng 205 sắp hết hạn (còn 12 ngày)', time: '5 giờ trước', color: 'orange' },
    { id: 3, type: 'appointment', message: 'Lịch hẹn xem phòng mới: 14:00 hôm nay', time: '6 giờ trước', color: 'blue' },
    { id: 4, type: 'invoice', message: 'Hoá đơn tháng 5 đã được tạo cho 9 phòng', time: '1 ngày trước', color: 'teal' },
    { id: 5, type: 'payment', message: 'Phòng 302 - Trần Thị B chưa đóng tiền (quá hạn 5 ngày)', time: '1 ngày trước', color: 'red' },
])

const chartData = ref([
    { month: 'T1', amount: 18500000 },
    { month: 'T2', amount: 21000000 },
    { month: 'T3', amount: 19800000 },
    { month: 'T4', amount: 23500000 },
    { month: 'T5', amount: 25000000 },
    { month: 'T6', amount: 27500000 },
])

const maxAmount = computed(() => Math.max(...chartData.value.map(d => d.amount)))
const occupancyRate = computed(() => Math.round((stats.value.occupied / stats.value.totalRooms) * 100))
const formatMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'
const barHeight = (amount) => Math.round((amount / maxAmount.value) * 100)
</script>

<template>
    <LandlordLayout>
        <template #header-title>
            <h1 class="ll-header-title">Tổng Quan</h1>
        </template>

        <div class="dash-wrap">
            <!-- Stats row -->
            <div class="stat-grid">
                <div class="stat-card stat-teal">
                    <div class="stat-icon"><i class="bi bi-building"></i></div>
                    <div class="stat-info">
                        <div class="stat-val">{{ stats.totalRooms }}</div>
                        <div class="stat-label">Tổng Phòng</div>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon"><i class="bi bi-house-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-val">{{ stats.occupied }}</div>
                        <div class="stat-label">Đang Cho Thuê</div>
                    </div>
                </div>
                <div class="stat-card stat-slate">
                    <div class="stat-icon"><i class="bi bi-house-dash"></i></div>
                    <div class="stat-info">
                        <div class="stat-val">{{ stats.vacant }}</div>
                        <div class="stat-label">Phòng Trống</div>
                    </div>
                </div>
                <div class="stat-card stat-money">
                    <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
                    <div class="stat-info">
                        <div class="stat-val stat-val-sm">{{ formatMoney(stats.monthRevenue) }}</div>
                        <div class="stat-label">Doanh Thu Tháng</div>
                    </div>
                </div>
            </div>

            <!-- Alert strip -->
            <div class="alert-strip" v-if="stats.expiringContracts > 0 || stats.pendingPayments > 0">
                <span v-if="stats.expiringContracts > 0" class="alert-pill alert-orange">
                    <i class="bi bi-clock-history"></i> {{ stats.expiringContracts }} hợp đồng sắp hết hạn
                </span>
                <span v-if="stats.pendingPayments > 0" class="alert-pill alert-red">
                    <i class="bi bi-exclamation-triangle-fill"></i> {{ stats.pendingPayments }} phòng chưa đóng tiền
                </span>
                <span v-if="stats.pendingAppointments > 0" class="alert-pill alert-blue">
                    <i class="bi bi-calendar-event"></i> {{ stats.pendingAppointments }} lịch hẹn chờ duyệt
                </span>
            </div>

            <div class="main-cols">
                <!-- Chart + Occupancy -->
                <div class="left-col">
                    <div class="card">
                        <div class="card-head">
                            <h3 class="card-title"><i class="bi bi-bar-chart-fill"></i> Doanh Thu 6 Tháng</h3>
                            <span class="badge-teal">2026</span>
                        </div>
                        <div class="chart-area">
                            <div class="bar-chart">
                                <div v-for="d in chartData" :key="d.month" class="bar-col">
                                    <div class="bar-tooltip">{{ formatMoney(d.amount) }}</div>
                                    <div class="bar-fill" :style="{ height: barHeight(d.amount) + '%' }"></div>
                                    <div class="bar-label">{{ d.month }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-sm">
                        <div class="card-head">
                            <h3 class="card-title"><i class="bi bi-pie-chart-fill"></i> Tỷ Lệ Lấp Đầy</h3>
                            <span class="occ-pct">{{ occupancyRate }}%</span>
                        </div>
                        <div class="occ-bar-wrap">
                            <div class="occ-bar">
                                <div class="occ-fill" :style="{ width: occupancyRate + '%' }"></div>
                            </div>
                        </div>
                        <div class="occ-legend">
                            <span><span class="leg-dot leg-green"></span>Đã thuê ({{ stats.occupied }})</span>
                            <span><span class="leg-dot leg-slate"></span>Trống ({{ stats.vacant }})</span>
                            <span><span class="leg-dot leg-yellow"></span>Bảo trì ({{ stats.maintenance }})</span>
                        </div>
                    </div>

                    <div class="card card-ai">
                        <div class="ai-badge"><i class="bi bi-stars"></i> AI Dự Báo</div>
                        <div class="ai-text">
                            Dựa trên tỷ lệ lấp đầy <strong>{{ occupancyRate }}%</strong>, doanh thu tháng tới dự kiến đạt
                            <strong class="ai-green">{{ formatMoney(stats.monthRevenue * 1.05) }}</strong>
                            nếu lấp đầy các phòng còn trống.
                        </div>
                        <div class="ai-note">* Tính năng AI đầy đủ sẽ sớm ra mắt</div>
                    </div>
                </div>

                <!-- Right: Recent activities + quick links -->
                <div class="right-col">
                    <div class="card card-full">
                        <div class="card-head">
                            <h3 class="card-title"><i class="bi bi-activity"></i> Hoạt Động Gần Đây</h3>
                        </div>
                        <div class="activity-list">
                            <div v-for="act in recentActivities" :key="act.id" class="activity-item">
                                <div :class="['act-dot', `dot-${act.color}`]"></div>
                                <div class="act-body">
                                    <div class="act-msg">{{ act.message }}</div>
                                    <div class="act-time">{{ act.time }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-sm">
                        <h3 class="card-title mb-3"><i class="bi bi-lightning-fill"></i> Thao Tác Nhanh</h3>
                        <div class="quick-grid">
                            <a href="/landlord/invoices" class="quick-btn">
                                <i class="bi bi-receipt"></i><span>Tạo Hoá Đơn</span>
                            </a>
                            <a href="/landlord/listings/create" class="quick-btn">
                                <i class="bi bi-plus-circle"></i><span>Đăng Tin</span>
                            </a>
                            <a href="/landlord/contracts" class="quick-btn">
                                <i class="bi bi-file-earmark-plus"></i><span>Hợp Đồng</span>
                            </a>
                            <a href="/landlord/finance" class="quick-btn">
                                <i class="bi bi-calculator"></i><span>Tài Chính</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>

<style scoped>
.dash-wrap { display: flex; flex-direction: column; gap: 20px; }

.stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.stat-card { border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.stat-teal  { background: linear-gradient(135deg, #0f766e, #0d9488); color: #fff; }
.stat-green { background: linear-gradient(135deg, #15803d, #16a34a); color: #fff; }
.stat-slate { background: linear-gradient(135deg, #334155, #475569); color: #fff; }
.stat-money { background: linear-gradient(135deg, #b45309, #d97706); color: #fff; }
.stat-icon  { font-size: 28px; opacity: 0.85; }
.stat-val   { font-size: 26px; font-weight: 800; line-height: 1; }
.stat-val-sm{ font-size: 17px; font-weight: 800; }
.stat-label { font-size: 12px; opacity: 0.85; margin-top: 4px; }

.alert-strip { display: flex; gap: 10px; flex-wrap: wrap; }
.alert-pill  { display: flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 100px; font-size: 13px; font-weight: 600; }
.alert-orange{ background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
.alert-red   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
.alert-blue  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

.main-cols { display: grid; grid-template-columns: 1fr 380px; gap: 20px; }
.left-col, .right-col { display: flex; flex-direction: column; gap: 16px; }

.card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f0fdf4; }
.card-sm   { padding: 16px; }
.card-full { flex: 1; }
.card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.card-title{ font-size: 15px; font-weight: 700; color: #064e3b; margin: 0; display: flex; align-items: center; gap: 7px; }
.mb-3 { margin-bottom: 12px; }
.badge-teal{ background: #d1fae5; color: #065f46; padding: 3px 10px; border-radius: 100px; font-size: 12px; font-weight: 600; }

.chart-area { height: 180px; }
.bar-chart  { display: flex; align-items: flex-end; justify-content: space-around; height: 100%; gap: 8px; padding: 0 4px; }
.bar-col    { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; position: relative; gap: 6px; }
.bar-col:hover .bar-tooltip { opacity: 1; }
.bar-tooltip{ position: absolute; top: -28px; background: #064e3b; color: #fff; padding: 3px 8px; border-radius: 6px; font-size: 11px; white-space: nowrap; opacity: 0; transition: opacity 0.2s; pointer-events: none; }
.bar-fill   { width: 100%; background: linear-gradient(to top, #0f766e, #34d399); border-radius: 6px 6px 0 0; transition: height 0.4s ease; min-height: 8px; }
.bar-label  { font-size: 12px; color: #6b7280; font-weight: 600; }

.occ-pct    { font-size: 24px; font-weight: 800; color: #0f766e; }
.occ-bar-wrap{ margin-bottom: 10px; }
.occ-bar    { background: #f1f5f9; border-radius: 100px; height: 12px; overflow: hidden; }
.occ-fill   { height: 100%; background: linear-gradient(90deg, #0f766e, #34d399); border-radius: 100px; transition: width 0.5s; }
.occ-legend { display: flex; align-items: center; gap: 12px; font-size: 12px; color: #6b7280; }
.leg-dot    { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.leg-green  { background: #16a34a; }
.leg-slate  { background: #94a3b8; }
.leg-yellow { background: #f59e0b; }

.card-ai  { background: linear-gradient(135deg, #f0fdf4 0%, #d1fae5 100%); border: 1px solid #6ee7b7; }
.ai-badge { display: inline-flex; align-items: center; gap: 6px; background: #0f766e; color: #fff; padding: 4px 12px; border-radius: 100px; font-size: 12px; font-weight: 700; margin-bottom: 10px; }
.ai-text  { font-size: 14px; color: #064e3b; line-height: 1.6; }
.ai-green { color: #059669; }
.ai-note  { margin-top: 8px; font-size: 11px; color: #6b7280; font-style: italic; }

.activity-list { display: flex; flex-direction: column; gap: 14px; }
.activity-item { display: flex; gap: 12px; align-items: flex-start; }
.act-dot  { width: 10px; height: 10px; border-radius: 50%; margin-top: 4px; flex-shrink: 0; }
.dot-green  { background: #16a34a; }
.dot-orange { background: #f59e0b; }
.dot-blue   { background: #2563eb; }
.dot-teal   { background: #0f766e; }
.dot-red    { background: #dc2626; }
.act-msg  { font-size: 13.5px; color: #1e293b; font-weight: 500; }
.act-time { font-size: 12px; color: #94a3b8; margin-top: 2px; }

.quick-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.quick-btn  { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 14px 8px; border-radius: 12px; background: #f0fdf4; border: 1px solid #d1fae5; color: #0f766e; text-decoration: none; font-size: 12px; font-weight: 600; transition: all 0.15s; text-align: center; }
.quick-btn:hover { background: #d1fae5; border-color: #6ee7b7; transform: translateY(-1px); }
.quick-btn i { font-size: 20px; }

@media (max-width: 1280px) {
    .stat-grid { grid-template-columns: repeat(2, 1fr); }
    .main-cols { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .stat-val  { font-size: 20px; }
    .stat-val-sm { font-size: 14px; }
    .stat-card { padding: 14px; gap: 10px; }
    .stat-icon { font-size: 22px; }
    .alert-strip { gap: 8px; }
    .alert-pill  { font-size: 12px; padding: 5px 10px; }
    .quick-grid  { grid-template-columns: repeat(2, 1fr); }
    .chart-area  { height: 140px; }
    .bar-tooltip { font-size: 10px; }
}
</style>
