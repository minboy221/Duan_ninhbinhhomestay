<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'

const months = ['T1','T2','T3','T4','T5','T6','T7','T8','T9','T10','T11','T12']
const revenueData = [0,0,0,0,500000,1500000,3000000,2500000,4000000,3500000,5000000,4500000]
const maxRev = Math.max(...revenueData) || 1

function fmt(n) {
    if (n >= 1000000) return (n/1000000).toFixed(1) + 'M'
    if (n >= 1000) return (n/1000).toFixed(0) + 'K'
    return n + 'đ'
}

const transactions = [
    { id:1, landlord:'Trần Văn Hùng', plan:'Gói Cơ Bản', amount:299000,  date:'15/05/2026', status:'paid' },
    { id:2, landlord:'Lê Thị Nga',    plan:'Gói Nâng Cao', amount:599000, date:'12/05/2026', status:'paid' },
    { id:3, landlord:'Nguyễn Văn Bá', plan:'Gói Cơ Bản', amount:299000,  date:'10/05/2026', status:'paid' },
    { id:4, landlord:'Phạm Thị Mai',  plan:'Gói Miễn Phí', amount:0,     date:'05/05/2026', status:'free' },
]
</script>

<template>
    <Head title="Admin - Nguồn Thu" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Quản Lý Nguồn Thu</h1>
                <p class="page-sub">Theo dõi doanh thu từ chủ trọ</p>
            </div>
        </template>

        <div class="stats-row">
            <div class="scard sc-green"><div class="sc-icon"><i class="bi bi-cash-stack"></i></div><div><p class="snum">24.9M</p><p class="slbl">Tổng doanh thu</p></div></div>
            <div class="scard sc-blue"><div class="sc-icon"><i class="bi bi-calendar-month"></i></div><div><p class="snum">4.5M</p><p class="slbl">Tháng này</p></div></div>
            <div class="scard sc-purple"><div class="sc-icon"><i class="bi bi-people-fill"></i></div><div><p class="snum">{{ transactions.filter(t=>t.status==='paid').length }}</p><p class="slbl">Chủ trọ trả phí</p></div></div>
            <div class="scard sc-orange"><div class="sc-icon"><i class="bi bi-gift"></i></div><div><p class="snum">{{ transactions.filter(t=>t.status==='free').length }}</p><p class="slbl">Đang miễn phí</p></div></div>
        </div>

        <!-- Bar chart -->
        <div class="chart-card">
            <div class="card-head">
                <h3 class="card-title">Doanh Thu Theo Tháng (2026)</h3>
                <button class="export-btn"><i class="bi bi-download"></i> Xuất Excel</button>
            </div>
            <div class="bar-chart">
                <div v-for="(val, i) in revenueData" :key="i" class="bar-col">
                    <div class="bar-wrap">
                        <div class="bar-val" v-if="val > 0">{{ fmt(val) }}</div>
                        <div class="bar" :style="`height:${(val/maxRev)*100}%; background:${i===revenueData.length-1?'#7c3aed':'#c4b5fd'}`"></div>
                    </div>
                    <span class="bar-lbl">{{ months[i] }}</span>
                </div>
            </div>
        </div>

        <!-- Plans config -->
        <div class="plans-row">
            <div class="plan-card">
                <div class="plan-icon pi-gray"><i class="bi bi-gift"></i></div>
                <h4 class="plan-name">Gói Miễn Phí</h4>
                <p class="plan-price">0đ</p>
                <ul class="plan-features">
                    <li>✓ Đăng tối đa 2 tin</li>
                    <li>✓ Thời hạn 1-2 tháng</li>
                    <li>✗ Không có hỗ trợ ưu tiên</li>
                </ul>
            </div>
            <div class="plan-card plan-highlight">
                <div class="plan-badge-top">Phổ biến</div>
                <div class="plan-icon pi-purple"><i class="bi bi-star-fill"></i></div>
                <h4 class="plan-name">Gói Cơ Bản</h4>
                <p class="plan-price">299.000đ<span>/tháng</span></p>
                <ul class="plan-features">
                    <li>✓ Đăng tối đa 10 tin</li>
                    <li>✓ Hỗ trợ email</li>
                    <li>✓ Thống kê cơ bản</li>
                </ul>
            </div>
            <div class="plan-card">
                <div class="plan-icon pi-gold"><i class="bi bi-lightning-fill"></i></div>
                <h4 class="plan-name">Gói Nâng Cao</h4>
                <p class="plan-price">599.000đ<span>/tháng</span></p>
                <ul class="plan-features">
                    <li>✓ Không giới hạn tin</li>
                    <li>✓ Hỗ trợ 24/7</li>
                    <li>✓ Thống kê nâng cao</li>
                </ul>
            </div>
        </div>

        <!-- Transactions table -->
        <div class="table-card">
            <div class="card-head p16"><h3 class="card-title">Lịch Sử Giao Dịch</h3></div>
            <table class="data-table">
                <thead><tr><th>#</th><th>Chủ trọ</th><th>Gói dịch vụ</th><th>Số tiền</th><th>Ngày TT</th><th>Trạng thái</th></tr></thead>
                <tbody>
                    <tr v-for="(t, i) in transactions" :key="t.id" class="trow">
                        <td class="idx">{{ i+1 }}</td>
                        <td class="fw">{{ t.landlord }}</td>
                        <td>{{ t.plan }}</td>
                        <td class="fw" :style="t.amount > 0 ? 'color:#16a34a' : 'color:#94a3b8'">
                            {{ t.amount > 0 ? t.amount.toLocaleString()+'đ' : 'Miễn phí' }}
                        </td>
                        <td class="sm-gray">{{ t.date }}</td>
                        <td>
                            <span :class="['status-chip', t.status==='paid' ? 's-green' : 's-gray']">
                                {{ t.status === 'paid' ? 'Đã thanh toán' : 'Miễn phí' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

<style scoped>
.page-title{font-size:18px;font-weight:700;color:#0f172a;margin:0}.page-sub{font-size:12px;color:#94a3b8;margin:2px 0 0}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
.scard{background:#fff;border-radius:8px;padding:16px;display:flex;align-items:center;gap:12px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.sc-icon{width:44px;height:44px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.sc-green .sc-icon{background:#f0fdf4;color:#22c55e}.sc-green .snum{color:#16a34a}
.sc-blue .sc-icon{background:#eff6ff;color:#3b82f6}.sc-blue .snum{color:#2563eb}
.sc-purple .sc-icon{background:#faf5ff;color:#7c3aed}.sc-purple .snum{color:#7c3aed}
.sc-orange .sc-icon{background:#fff7ed;color:#f97316}.sc-orange .snum{color:#ea580c}
.snum{font-size:22px;font-weight:800;margin:0;line-height:1}.slbl{font-size:11px;color:#94a3b8;margin:2px 0 0}
.chart-card{background:#fff;border-radius:8px;border:1px solid #f1f5f9;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:18px}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.card-title{font-size:14px;font-weight:700;color:#0f172a;margin:0}
.export-btn{padding:7px 14px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px}
.bar-chart{display:flex;align-items:flex-end;gap:8px;height:180px;padding-top:24px}
.bar-col{display:flex;flex-direction:column;align-items:center;flex:1;gap:4px;height:100%}
.bar-wrap{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;width:100%;position:relative}
.bar-val{position:absolute;top:-20px;font-size:9px;font-weight:600;color:#7c3aed;white-space:nowrap}
.bar{width:100%;border-radius:4px 4px 0 0;min-height:2px;transition:opacity .2s}.bar:hover{opacity:.75}
.bar-lbl{font-size:9px;color:#94a3b8}
.plans-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}
.plan-card{background:#fff;border-radius:8px;border:1px solid #f1f5f9;padding:20px;text-align:center;position:relative;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.plan-highlight{border-color:#7c3aed;box-shadow:0 4px 20px rgba(124,58,237,.12)}
.plan-badge-top{position:absolute;top:-10px;left:50%;transform:translateX(-50%);background:#7c3aed;color:#fff;font-size:11px;font-weight:700;padding:3px 12px;border-radius:99px}
.plan-icon{width:48px;height:48px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 12px}
.pi-gray{background:#f1f5f9;color:#64748b}.pi-purple{background:#faf5ff;color:#7c3aed}.pi-gold{background:#fffbeb;color:#d97706}
.plan-name{font-size:15px;font-weight:700;color:#0f172a;margin:0 0 4px}
.plan-price{font-size:22px;font-weight:800;color:#7c3aed;margin:0 0 14px}.plan-price span{font-size:12px;color:#94a3b8;font-weight:400}
.plan-features{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:6px;font-size:12px;color:#475569;text-align:left}
.table-card{background:#fff;border-radius:8px;border:1px solid #f1f5f9;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.p16{padding:16px 16px 0}
.data-table{width:100%;border-collapse:collapse;font-size:13px}
.data-table th{text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;padding:13px 16px;background:#f8fafc;border-bottom:1px solid #f1f5f9;letter-spacing:.04em}
.data-table td{padding:12px 16px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.trow:last-child td{border-bottom:none}.trow:hover td{background:#fafbff}
.idx{color:#cbd5e1;font-size:12px;font-weight:600}.fw{font-weight:600;color:#0f172a}.sm-gray{color:#94a3b8;font-size:12px}
.status-chip{font-size:11px;font-weight:600;padding:3px 9px;border-radius:99px}
.s-green{background:#f0fdf4;color:#16a34a}.s-gray{background:#f1f5f9;color:#64748b}
</style>
