<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    totalRevenue: { type: Number, default: 0 },
    thisMonthRevenue: { type: Number, default: 0 },
    paidCount: { type: Number, default: 0 },
    freeCount: { type: Number, default: 0 },
    monthlyRevenue: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    transactions: { type: [Object, Array], default: () => [] },
});

const transactionsList = computed(() => {
    if (!props.transactions) return [];
    if (Array.isArray(props.transactions)) return props.transactions;
    return props.transactions.data || [];
});

const paginationLinks = computed(() => {
    if (props.transactions && !Array.isArray(props.transactions) && props.transactions.links) {
        return props.transactions.links;
    }
    return [];
});

const months = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];
const revenueData = computed(() => props.monthlyRevenue?.length ? props.monthlyRevenue : [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]);
const maxRev = computed(() => Math.max(...revenueData.value) || 1);

function fmt(n) {
    if (!n) return '0đ';
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(0) + 'K';
    return Number(n).toLocaleString('vi-VN') + 'đ';
}
</script>

<template>

    <Head title="Admin - Nguồn Thu" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Quản Lý Nguồn Thu</h1>
                <p class="page-sub">Theo dõi doanh thu từ các gói dịch vụ chủ trọ</p>
            </div>
        </template>

        <!-- Thống kê tổng quan -->
        <div class="stats-row">
            <div class="scard sc-green">
                <div class="sc-icon"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <p class="snum">{{ fmt(props.totalRevenue) }}</p>
                    <p class="slbl">Tổng doanh thu</p>
                </div>
            </div>
            <div class="scard sc-blue">
                <div class="sc-icon"><i class="bi bi-calendar-month"></i></div>
                <div>
                    <p class="snum">{{ fmt(props.thisMonthRevenue) }}</p>
                    <p class="slbl">Tháng này</p>
                </div>
            </div>
            <div class="scard sc-purple">
                <div class="sc-icon"><i class="bi bi-people-fill"></i></div>
                <div>
                    <p class="snum">{{ props.paidCount }}</p>
                    <p class="slbl">Chủ trọ trả phí</p>
                </div>
            </div>
            <div class="scard sc-orange">
                <div class="sc-icon"><i class="bi bi-gift"></i></div>
                <div>
                    <p class="snum">{{ props.freeCount }}</p>
                    <p class="slbl">Đang dùng thử</p>
                </div>
            </div>
        </div>

        <!-- Biểu đồ cột Doanh Thu 12 Tháng -->
        <div class="chart-card">
            <div class="card-head">
                <h3 class="card-title">Doanh Thu Theo Tháng ({{ new Date().getFullYear() }})</h3>
                <button class="export-btn"><i class="bi bi-download"></i> Xuất Báo Cáo</button>
            </div>
            <div class="bar-chart">
                <div v-for="(val, i) in revenueData" :key="i" class="bar-col">
                    <div class="bar-wrap">
                        <div class="bar-val" v-if="val > 0">{{ fmt(val) }}</div>
                        <div class="bar"
                            :style="`height:${(val / maxRev) * 100}%; background:${i === new Date().getMonth() ? '#7c3aed' : '#c4b5fd'}`">
                        </div>
                    </div>
                    <span class="bar-lbl">{{ months[i] }}</span>
                </div>
            </div>
        </div>

        <!-- Danh sách Các Gói Dịch Vụ -->
        <div class="plans-row" v-if="props.plans?.length">
            <div v-for="plan in props.plans" :key="plan.id" :class="['plan-card', plan.badge ? 'plan-highlight' : '']">
                <div class="plan-badge-top" v-if="plan.badge">{{ plan.badge }}</div>
                <div class="plan-icon pi-purple"><i class="bi bi-star-fill"></i></div>
                <h4 class="plan-name">{{ plan.name }}</h4>
                <p class="plan-price">{{ fmt(plan.price) }}<span>/tháng</span></p>
                <ul class="plan-features" v-if="plan.features?.length">
                    <li v-for="f in plan.features" :key="f.id">
                        ✓ {{ f.name }}: {{ f.pivot?.feature_value || 'Có' }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bảng Lịch Sử Giao Dịch Mua Gói -->
        <div class="table-card">
            <div class="card-head p16">
                <h3 class="card-title">Lịch Sử Giao Dịch Mua Gói Gần Đây</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Chủ trọ</th>
                        <th>Gói dịch vụ</th>
                        <th>Số tiền</th>
                        <th>Ngày thanh toán</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!transactionsList.length">
                        <td colspan="6" class="text-center py-6 text-slate-400 font-medium">Chưa có lịch sử giao dịch
                            nào</td>
                    </tr>
                    <tr v-for="(t, i) in transactionsList" :key="t.id" class="trow">
                        <td class="idx">{{ i + 1 }}</td>
                        <td class="fw">{{ t.landlord }}</td>
                        <td>{{ t.plan }}</td>
                        <td class="fw" :style="t.amount > 0 ? 'color:#16a34a' : 'color:#94a3b8'">
                            {{ t.amount > 0 ? Number(t.amount).toLocaleString('vi-VN') + 'đ' : 'Miễn phí' }}
                        </td>
                        <td class="sm-gray">{{ t.date }}</td>
                        <td>
                            <span :class="['status-chip', t.status === 'paid' ? 's-green' : 's-gray']">
                                {{ t.status === 'paid' ? 'Đã thanh toán' : 'Miễn phí / Dùng thử' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Component Phân Trang -->
            <Pagination :links="paginationLinks" class="mt-4" />
        </div>
    </AdminLayout>
</template>

<style scoped>
.page-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.page-sub {
    font-size: 12px;
    color: #94a3b8;
    margin: 2px 0 0;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 18px;
}

.scard {
    background: #fff;
    border-radius: 16px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1fr solid #e2e8f0;
}

.sc-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.sc-green .sc-icon {
    background: #dcfce7;
    color: #16a34a;
}

.sc-blue .sc-icon {
    background: #dbeafe;
    color: #2563eb;
}

.sc-purple .sc-icon {
    background: #f3e8ff;
    color: #9333ea;
}

.sc-orange .sc-icon {
    background: #ffedd5;
    color: #ea580c;
}

.snum {
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
}

.slbl {
    font-size: 11px;
    color: #64748b;
    margin: 0;
}

.chart-card,
.table-card {
    background: #fff;
    border-radius: 16px;
    padding: 18px;
    border: 1px solid #e2e8f0;
    margin-bottom: 18px;
}

.card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.card-title {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.export-btn {
    background: #f1f5f9;
    color: #475569;
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.bar-chart {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    height: 180px;
    padding-top: 20px;
}

.bar-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
    justify-content: flex-end;
}

.bar-wrap {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: center;
    position: relative;
}

.bar-val {
    font-size: 10px;
    color: #64748b;
    margin-bottom: 4px;
    font-weight: 600;
}

.bar {
    width: 60%;
    max-width: 28px;
    border-radius: 6px 6px 0 0;
    transition: all 0.3s;
}

.bar-lbl {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 6px;
}

.plans-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 18px;
}

.plan-card {
    background: #fff;
    border-radius: 16px;
    padding: 18px;
    border: 1px solid #e2e8f0;
    position: relative;
}

.plan-highlight {
    border: 2px solid #7c3aed;
}

.plan-badge-top {
    position: absolute;
    top: -10px;
    right: 16px;
    background: #7c3aed;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
}

.plan-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}

.pi-purple {
    background: #f3e8ff;
    color: #7c3aed;
}

.pi-gray {
    background: #f1f5f9;
    color: #64748b;
}

.plan-name {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px;
}

.plan-price {
    font-size: 18px;
    font-weight: 800;
    color: #7c3aed;
    margin: 0 0 12px;
}

.plan-price span {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 400;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 11px;
    color: #475569;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 12px;
}

.data-table th {
    padding: 10px 12px;
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #f1f5f9;
}

.status-chip {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
}

.s-green {
    background: #dcfce7;
    color: #15803d;
}

.s-gray {
    background: #f1f5f9;
    color: #64748b;
}
</style>
