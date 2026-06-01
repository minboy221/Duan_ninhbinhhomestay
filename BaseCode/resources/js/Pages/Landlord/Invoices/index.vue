<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed, watch } from 'vue'

const month = ref('2026-05')

const DEFAULT_INVOICES = [
    { id: 'HD001', room: 'Phòng 101', tenant: 'Nguyễn Văn A', phone: '0912 345 678', rent: 2800000, elec: 490000, water: 105000, other: 0, status: 'paid',    dueDate: '2026-05-10' },
    { id: 'HD002', room: 'Phòng 102', tenant: 'Trần Thị B',   phone: '0987 654 321', rent: 2800000, elec: 420000, water: 75000,  other: 0, status: 'pending', dueDate: '2026-05-10' },
    { id: 'HD003', room: 'Phòng 201', tenant: 'Lê Minh C',    phone: '0901 111 222', rent: 3200000, elec: 665000, water: 165000, other: 50000, status: 'overdue', dueDate: '2026-05-05' },
    { id: 'HD004', room: 'Phòng 202', tenant: 'Phạm Thị D',   phone: '0933 444 555', rent: 3200000, elec: 490000, water: 105000, other: 0, status: 'pending', dueDate: '2026-05-10' },
    { id: 'HD005', room: 'Phòng 301', tenant: 'Hoàng Văn E',  phone: '0966 777 888', rent: 3500000, elec: 315000, water: 60000,  other: 0, status: 'paid',    dueDate: '2026-05-10' },
]

const invoices = ref([])
if (typeof window !== 'undefined') {
    const saved = localStorage.getItem('landlord_invoices')
    invoices.value = saved ? JSON.parse(saved) : DEFAULT_INVOICES
} else {
    invoices.value = DEFAULT_INVOICES
}

watch(invoices, (val) => {
    localStorage.setItem('landlord_invoices', JSON.stringify(val))
}, { deep: true })

const totalInvoice   = (inv) => inv.rent + inv.elec + inv.water + inv.other
const totalRevenue   = computed(() => invoices.value.reduce((s, i) => s + totalInvoice(i), 0))
const totalCollected = computed(() => invoices.value.filter(i => i.status === 'paid').reduce((s, i) => s + totalInvoice(i), 0))
const totalPending   = computed(() => invoices.value.filter(i => i.status !== 'paid').reduce((s, i) => s + totalInvoice(i), 0))

const statusMap = {
    paid:    { label: 'Đã Thanh Toán',  cls: 'st-paid' },
    pending: { label: 'Chờ Thanh Toán', cls: 'st-pending' },
    overdue: { label: 'Quá Hạn',        cls: 'st-overdue' },
}

const markPaid = (inv) => {
    inv.status = 'paid'
    
    // Also update the room status in the landlord_rooms state
    let savedRooms = []
    if (typeof window !== 'undefined') {
        const data = localStorage.getItem('landlord_rooms')
        savedRooms = data ? JSON.parse(data) : []
    }
    
    const roomIndex = savedRooms.findIndex(r => r.name === inv.room)
    if (roomIndex !== -1) {
        savedRooms[roomIndex].status = 'paid'
        if (typeof window !== 'undefined') {
            localStorage.setItem('landlord_rooms', JSON.stringify(savedRooms))
        }
    }
}

const formatMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'
</script>

<template>
    <LandlordLayout>
        <template #header-title><h1 class="ll-header-title">Quản Lý Hoá Đơn</h1></template>

        <div class="inv-wrap">
            <!-- Summary -->
            <div class="inv-stats">
                <div class="inv-stat inv-s1"><i class="bi bi-receipt"></i><div><div class="inv-num">{{ invoices.length }}</div><div class="inv-lbl">Tổng Hoá Đơn</div></div></div>
                <div class="inv-stat inv-s2"><i class="bi bi-check-circle"></i><div><div class="inv-num">{{ invoices.filter(i=>i.status==='paid').length }}</div><div class="inv-lbl">Đã Thanh Toán</div></div></div>
                <div class="inv-stat inv-s3"><i class="bi bi-hourglass-split"></i><div><div class="inv-num">{{ invoices.filter(i=>i.status==='pending').length }}</div><div class="inv-lbl">Chờ Thanh Toán</div></div></div>
                <div class="inv-stat inv-s4"><i class="bi bi-exclamation-triangle"></i><div><div class="inv-num">{{ invoices.filter(i=>i.status==='overdue').length }}</div><div class="inv-lbl">Quá Hạn</div></div></div>
            </div>

            <!-- Finance strip -->
            <div class="inv-finance">
                <span class="fin-chip fin-total">Tổng dự thu: <strong>{{ formatMoney(totalRevenue) }}</strong></span>
                <span class="fin-chip fin-paid">Đã thu: <strong>{{ formatMoney(totalCollected) }}</strong></span>
                <span class="fin-chip fin-debt">Còn lại: <strong>{{ formatMoney(totalPending) }}</strong></span>
            </div>

            <!-- Table card -->
            <div class="inv-card">
                <div class="inv-head">
                    <div class="inv-head-left">
                        <h3 class="inv-title"><i class="bi bi-receipt"></i> Hoá Đơn Tháng</h3>
                        <input type="month" v-model="month" class="month-input" />
                    </div>
                    <div class="inv-actions">
                        <button class="btn-send"><i class="bi bi-send"></i> Gửi Tất Cả</button>
                    </div>
                </div>

                <div class="table-scroll">
                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th>Phòng</th><th>Người thuê</th><th>SĐT</th>
                                <th>Tiền phòng</th><th>Tiền điện</th><th>Tiền nước</th>
                                <th>Khác</th><th>Tổng</th><th>Hạn thu</th>
                                <th>Trạng thái</th><th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="inv in invoices" :key="inv.id" :class="{ 'row-overdue': inv.status === 'overdue' }">
                                <td class="td-room">{{ inv.room }}</td>
                                <td class="td-name">{{ inv.tenant }}</td>
                                <td class="td-phone">{{ inv.phone }}</td>
                                <td class="td-money">{{ formatMoney(inv.rent) }}</td>
                                <td class="td-money">{{ formatMoney(inv.elec) }}</td>
                                <td class="td-money">{{ formatMoney(inv.water) }}</td>
                                <td class="td-money">{{ formatMoney(inv.other) }}</td>
                                <td class="td-money td-total">{{ formatMoney(totalInvoice(inv)) }}</td>
                                <td :class="{ 'td-warn': inv.status === 'overdue' }">{{ new Date(inv.dueDate).toLocaleDateString('vi-VN') }}</td>
                                <td><span :class="['status-pill', statusMap[inv.status].cls]">{{ statusMap[inv.status].label }}</span></td>
                                <td>
                                    <div class="act-row">
                                        <button v-if="inv.status !== 'paid'" class="abtn abtn-pay" @click="markPaid(inv)">
                                            <i class="bi bi-check-circle"></i> Đã Thu
                                        </button>
                                        <button class="abtn abtn-send"><i class="bi bi-send"></i> Gửi</button>
                                        <button class="abtn abtn-pdf"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>

<style scoped>
.inv-wrap { display: flex; flex-direction: column; gap: 20px; }

.inv-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.inv-stat  { border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 14px; color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.inv-stat i{ font-size: 26px; opacity: 0.85; }
.inv-num { font-size: 26px; font-weight: 800; line-height: 1; }
.inv-lbl { font-size: 11px; opacity: 0.85; margin-top: 3px; }
.inv-s1 { background: linear-gradient(135deg, #0f766e, #0d9488); }
.inv-s2 { background: linear-gradient(135deg, #15803d, #16a34a); }
.inv-s3 { background: linear-gradient(135deg, #b45309, #d97706); }
.inv-s4 { background: linear-gradient(135deg, #dc2626, #ef4444); }

.inv-finance { display: flex; gap: 12px; flex-wrap: wrap; }
.fin-chip    { padding: 8px 16px; border-radius: 100px; font-size: 13px; }
.fin-total   { background: #f0fdf4; color: #064e3b; border: 1px solid #bbf7d0; }
.fin-paid    { background: #dcfce7; color: #15803d; }
.fin-debt    { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

.inv-card    { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.inv-head    { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.inv-head-left{ display: flex; align-items: center; gap: 14px; }
.inv-title   { font-size: 15px; font-weight: 700; color: #064e3b; margin: 0; display: flex; align-items: center; gap: 7px; }
.month-input { border: 1.5px solid #d1fae5; border-radius: 8px; padding: 6px 10px; font-size: 13px; outline: none; }
.inv-actions { display: flex; gap: 8px; }
.btn-create  { display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: #0f766e; color: #fff; border: none; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-send    { display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: #2563eb; color: #fff; border: none; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; }

.table-scroll { overflow-x: auto; }
.inv-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 900px; }
.inv-table th { background: #f0fdf4; color: #065f46; padding: 10px 12px; text-align: left; font-weight: 700; border-bottom: 2px solid #d1fae5; white-space: nowrap; }
.inv-table td { padding: 12px; border-bottom: 1px solid #f0fdf4; vertical-align: middle; }
.td-room  { font-weight: 700; color: #064e3b; white-space: nowrap; }
.td-name  { font-weight: 500; white-space: nowrap; }
.td-phone { color: #6b7280; white-space: nowrap; }
.td-money { text-align: right; font-weight: 600; color: #374151; white-space: nowrap; }
.td-total { color: #0f766e; font-weight: 800; font-size: 14px; }
.td-warn  { color: #dc2626; font-weight: 600; }
.row-overdue td { background: #fff0f0; }
.row-overdue .td-room { color: #b91c1c !important; }
.row-overdue .td-warn { color: #dc2626; font-weight: 700; }

.status-pill { padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.st-paid    { background: #dcfce7; color: #15803d; }
.st-pending { background: #fef9c3; color: #854d0e; }
.st-overdue { background: #fee2e2; color: #b91c1c; }

.act-row { display: flex; gap: 6px; flex-wrap: wrap; }
.abtn    { height: 32px; border-radius: 7px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; font-size: 13px; font-weight: 600; padding: 0 10px; white-space: nowrap; }
.abtn:hover { filter: brightness(0.92); }
.abtn-pay  { background: #d1fae5; color: #15803d; border: 1px solid #86efac; }
.abtn-send { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }
.abtn-pdf  { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
.modal-box  { background: #fff; border-radius: 18px; width: 540px; max-width: 95vw; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
.modal-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid #f0fdf4; background: #f8fffe; }
.modal-head h3 { margin: 0; font-size: 16px; font-weight: 700; color: #064e3b; }
.modal-close{ background: none; border: none; font-size: 16px; cursor: pointer; color: #6b7280; }
.modal-body { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
.modal-foot { padding: 16px 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 8px; }

.form-row   { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-label { font-size: 12px; font-weight: 600; color: #6b7280; }
.form-input { padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; }
.form-input:focus { border-color: #0f766e; }

.preview-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px; }
.prev-row  { display: flex; justify-content: space-between; font-size: 13px; color: #374151; padding: 3px 0; }
.prev-total{ display: flex; justify-content: space-between; font-size: 15px; font-weight: 700; color: #064e3b; padding-top: 10px; margin-top: 8px; border-top: 1px solid #bbf7d0; }
.prev-num  { color: #0f766e; }

.btn-primary  { padding: 9px 18px; background: #0f766e; color: #fff; border: none; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-send-inv { padding: 9px 18px; background: #2563eb; color: #fff; border: none; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.btn-outline  { padding: 9px 18px; background: #fff; color: #374151; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; }

@media (max-width: 768px) {
    .inv-stats { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .inv-num   { font-size: 20px; }
    .inv-stat  { padding: 12px 14px; gap: 10px; }
    .inv-stat i{ font-size: 20px; }
    .inv-finance { gap: 8px; }
    .fin-chip    { font-size: 12px; padding: 6px 12px; }
    .inv-head    { flex-direction: column; align-items: flex-start; }
    .inv-actions { width: 100%; }
    .btn-create, .btn-send { flex: 1; justify-content: center; }
    .table-scroll { -webkit-overflow-scrolling: touch; }
    .inv-table   { font-size: 12px; min-width: 750px; }
    .inv-table th, .inv-table td { padding: 8px; }
    .act-row  { flex-wrap: nowrap; gap: 4px; }
    .abtn     { font-size: 12px; padding: 0 8px; }
    .modal-box{ width: 96vw; }
    .form-row { grid-template-columns: 1fr; }
}
</style>
