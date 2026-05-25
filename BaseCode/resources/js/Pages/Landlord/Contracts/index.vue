<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed } from 'vue'

const contracts = ref([
    { id: 'HD001', room: 'Phòng 101', tenant: 'Nguyễn Văn A', phone: '0912 345 678', start: '2025-06-01', end: '2026-06-01', rent: 2800000, deposit: 2800000, depositPaid: true,  status: 'active' },
    { id: 'HD002', room: 'Phòng 102', tenant: 'Trần Thị B',   phone: '0987 654 321', start: '2026-01-15', end: '2026-05-15', rent: 2800000, deposit: 2800000, depositPaid: true,  status: 'expiring' },
    { id: 'HD003', room: 'Phòng 201', tenant: 'Lê Minh C',    phone: '0901 111 222', start: '2025-09-01', end: '2026-09-01', rent: 3200000, deposit: 3200000, depositPaid: false, status: 'active' },
    { id: 'HD004', room: 'Phòng 205', tenant: 'Phạm Thị D',   phone: '0933 444 555', start: '2026-04-20', end: '2026-06-20', rent: 3200000, deposit: 3200000, depositPaid: true,  status: 'expiring' },
    { id: 'HD005', room: 'Phòng 301', tenant: 'Hoàng Văn E',  phone: '0966 777 888', start: '2025-03-01', end: '2025-12-31', rent: 3500000, deposit: 3500000, depositPaid: true,  status: 'expired' },
])

const showModal      = ref(false)
const selectedContract = ref(null)
const showDeleteConfirm = ref(false)
const deleteTarget   = ref(null)

const daysLeft  = (endDate) => Math.ceil((new Date(endDate) - new Date()) / (1000 * 60 * 60 * 24))
const statusMap = {
    active:   { label: 'Đang Hiệu Lực', cls: 'st-active' },
    expiring: { label: 'Sắp Hết Hạn',   cls: 'st-expiring' },
    expired:  { label: 'Đã Hết Hạn',    cls: 'st-expired' },
}

const expiringCount = computed(() => contracts.value.filter(c => c.status === 'expiring').length)
const openContract  = (c) => { selectedContract.value = c; showModal.value = true }
const closeModal    = () => { showModal.value = false; selectedContract.value = null }
const askDelete     = (c) => { deleteTarget.value = c; showDeleteConfirm.value = true }
const confirmDelete = () => { contracts.value = contracts.value.filter(c => c.id !== deleteTarget.value.id); showDeleteConfirm.value = false }
const formatMoney   = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'
const formatDate    = (d) => new Date(d).toLocaleDateString('vi-VN')
</script>

<template>
    <LandlordLayout>
        <template #header-title><h1 class="ll-header-title">Quản Lý Hợp Đồng</h1></template>

        <div class="ct-wrap">
            <!-- Alert -->
            <div v-if="expiringCount > 0" class="ct-alert">
                <i class="bi bi-clock-history"></i>
                <span>Có <strong>{{ expiringCount }}</strong> hợp đồng sắp hết hạn trong vòng 30 ngày. Vui lòng gia hạn hoặc kết thúc hợp đồng.</span>
            </div>

            <!-- Stats -->
            <div class="ct-stats">
                <div class="ct-stat ct-active"><i class="bi bi-file-check"></i><div><div class="ct-num">{{ contracts.filter(c=>c.status==='active').length }}</div><div class="ct-lbl">Đang Hiệu Lực</div></div></div>
                <div class="ct-stat ct-expiring"><i class="bi bi-clock"></i><div><div class="ct-num">{{ expiringCount }}</div><div class="ct-lbl">Sắp Hết Hạn</div></div></div>
                <div class="ct-stat ct-expired"><i class="bi bi-file-x"></i><div><div class="ct-num">{{ contracts.filter(c=>c.status==='expired').length }}</div><div class="ct-lbl">Đã Hết Hạn</div></div></div>
            </div>

            <!-- Table -->
            <div class="ct-card">
                <div class="ct-head">
                    <h3 class="ct-title"><i class="bi bi-file-earmark-text-fill"></i> Danh Sách Hợp Đồng</h3>
                    <button class="btn-new"><i class="bi bi-plus-circle"></i> Tạo Hợp Đồng Mới</button>
                </div>
                <div class="table-scroll">
                    <table class="ct-table">
                        <thead>
                            <tr>
                                <th>Mã HĐ</th><th>Phòng</th><th>Người thuê</th><th>SĐT</th>
                                <th>Ngày bắt đầu</th><th>Ngày kết thúc</th><th>Còn lại</th>
                                <th>Tiền thuê</th><th>Đặt cọc</th><th>Trạng thái</th><th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in contracts" :key="c.id" :class="{ 'row-expiring': c.status === 'expiring', 'row-expired': c.status === 'expired' }">
                                <td class="td-code">{{ c.id }}</td>
                                <td class="td-room">{{ c.room }}</td>
                                <td class="td-name">{{ c.tenant }}</td>
                                <td>{{ c.phone }}</td>
                                <td>{{ formatDate(c.start) }}</td>
                                <td :class="{ 'td-warn': c.status === 'expiring' }">{{ formatDate(c.end) }}</td>
                                <td>
                                    <span v-if="c.status !== 'expired'" :class="['days-badge', daysLeft(c.end) <= 30 ? 'days-red' : 'days-ok']">{{ daysLeft(c.end) }} ngày</span>
                                    <span v-else class="days-badge days-expired">Hết hạn</span>
                                </td>
                                <td class="td-money">{{ formatMoney(c.rent) }}</td>
                                <td><span :class="['dep-pill', c.depositPaid ? 'dep-paid' : 'dep-no']">{{ c.depositPaid ? 'Đã cọc' : 'Chưa cọc' }}</span></td>
                                <td><span :class="['status-pill', statusMap[c.status].cls]">{{ statusMap[c.status].label }}</span></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="abtn abtn-view" @click="openContract(c)" title="Xem chi tiết"><i class="bi bi-eye"></i></button>
                                        <button class="abtn abtn-pdf" title="Xuất PDF"><i class="bi bi-file-earmark-pdf"></i></button>
                                        <button class="abtn abtn-edit" title="Chỉnh sửa"><i class="bi bi-pencil"></i></button>
                                        <button class="abtn abtn-del" @click="askDelete(c)" title="Xoá"><i class="bi bi-trash3"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <!-- Detail modal -->
            <div v-if="showModal && selectedContract" class="modal-overlay" @click.self="closeModal">
                <div class="modal-box">
                    <div class="modal-head">
                        <div>
                            <h3>{{ selectedContract.id }} — {{ selectedContract.room }}</h3>
                            <span :class="['status-pill', statusMap[selectedContract.status].cls]">{{ statusMap[selectedContract.status].label }}</span>
                        </div>
                        <button @click="closeModal" class="modal-close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="info-grid">
                            <div class="info-item"><span class="info-label">Người thuê</span><span>{{ selectedContract.tenant }}</span></div>
                            <div class="info-item"><span class="info-label">Số điện thoại</span><span>{{ selectedContract.phone }}</span></div>
                            <div class="info-item"><span class="info-label">Bắt đầu</span><span>{{ formatDate(selectedContract.start) }}</span></div>
                            <div class="info-item"><span class="info-label">Kết thúc</span><span>{{ formatDate(selectedContract.end) }}</span></div>
                            <div class="info-item"><span class="info-label">Tiền thuê</span><span class="txt-green">{{ formatMoney(selectedContract.rent) }}/tháng</span></div>
                            <div class="info-item"><span class="info-label">Tiền cọc</span><span>{{ formatMoney(selectedContract.deposit) }}</span></div>
                            <div class="info-item"><span class="info-label">Đặt cọc</span><span :class="selectedContract.depositPaid ? 'txt-green' : 'txt-red'">{{ selectedContract.depositPaid ? '✅ Đã đặt cọc' : '❌ Chưa đặt cọc' }}</span></div>
                            <div class="info-item" v-if="selectedContract.status !== 'expired'"><span class="info-label">Còn lại</span><span :class="daysLeft(selectedContract.end) <= 30 ? 'txt-red' : 'txt-green'">{{ daysLeft(selectedContract.end) }} ngày</span></div>
                        </div>
                        <div class="audit-note"><i class="bi bi-info-circle"></i> Mọi thao tác xuất/sửa hợp đồng sẽ được ghi vào Audit Log hệ thống.</div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn-outline" @click="closeModal">Đóng</button>
                        <button class="btn-pdf"><i class="bi bi-file-earmark-pdf"></i> Xuất PDF</button>
                        <button class="btn-primary">Gia Hạn HĐ</button>
                    </div>
                </div>
            </div>

            <!-- Confirm delete -->
            <div v-if="showDeleteConfirm" class="modal-overlay">
                <div class="modal-box modal-sm">
                    <div class="modal-head"><h3>Xác Nhận Xoá</h3></div>
                    <div class="modal-body" style="text-align:center">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size:40px;color:#dc2626;display:block;margin-bottom:10px"></i>
                        <p style="font-size:14px;color:#374151;margin:0">Bạn có chắc muốn xoá hợp đồng <strong>{{ deleteTarget?.room }}</strong> không?</p>
                        <p style="font-size:13px;color:#94a3b8;margin-top:6px">Thao tác này không thể hoàn tác.</p>
                    </div>
                    <div class="modal-foot">
                        <button class="btn-outline" @click="showDeleteConfirm = false">Huỷ</button>
                        <button class="btn-delete" @click="confirmDelete"><i class="bi bi-trash3"></i> Xác Nhận Xoá</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>

<style scoped>
.ct-wrap { display: flex; flex-direction: column; gap: 20px; }

.ct-alert { display: flex; align-items: center; gap: 10px; background: #fffbeb; border: 1.5px solid #fcd34d; border-radius: 12px; padding: 12px 18px; color: #92400e; font-size: 14px; }
.ct-alert i { font-size: 18px; color: #d97706; }

.ct-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.ct-stat  { border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.ct-stat i{ font-size: 28px; opacity: 0.85; }
.ct-num  { font-size: 28px; font-weight: 800; line-height: 1; }
.ct-lbl  { font-size: 12px; opacity: 0.85; margin-top: 3px; }
.ct-active   { background: linear-gradient(135deg, #0f766e, #0d9488); }
.ct-expiring { background: linear-gradient(135deg, #b45309, #d97706); }
.ct-expired  { background: linear-gradient(135deg, #6b7280, #9ca3af); }

.ct-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.ct-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.ct-title{ font-size: 15px; font-weight: 700; color: #064e3b; margin: 0; display: flex; align-items: center; gap: 7px; }
.btn-new { display: flex; align-items: center; gap: 6px; padding: 8px 16px; background: #0f766e; color: #fff; border: none; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-new:hover { background: #0d9488; }

.table-scroll { overflow-x: auto; }
.ct-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 900px; }
.ct-table th { background: #f0fdf4; color: #065f46; padding: 10px 12px; text-align: left; font-weight: 700; border-bottom: 2px solid #d1fae5; white-space: nowrap; }
.ct-table td { padding: 12px; border-bottom: 1px solid #f0fdf4; vertical-align: middle; }
.td-code  { font-weight: 700; color: #0f766e; }
.td-room  { font-weight: 600; color: #0f172a; }
.td-name  { font-weight: 500; }
.td-money { font-weight: 700; color: #0f766e; }
.td-warn  { color: #d97706; font-weight: 600; }
.row-expiring td { background: #fffbeb; }
.row-expired  td { background: #f9fafb; color: #9ca3af; }

.days-badge   { padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; }
.days-ok      { background: #dcfce7; color: #15803d; }
.days-red     { background: #fee2e2; color: #b91c1c; }
.days-expired { background: #f3f4f6; color: #9ca3af; }
.dep-pill     { padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; }
.dep-paid     { background: #dcfce7; color: #15803d; }
.dep-no       { background: #fee2e2; color: #b91c1c; }
.status-pill  { padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.st-active    { background: #d1fae5; color: #065f46; }
.st-expiring  { background: #fef3c7; color: #92400e; }
.st-expired   { background: #f3f4f6; color: #6b7280; }

.action-btns { display: flex; gap: 4px; }
.abtn { width: 28px; height: 28px; border-radius: 7px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 13px; }
.abtn-view { background: #dbeafe; color: #1d4ed8; }
.abtn-pdf  { background: #fee2e2; color: #b91c1c; }
.abtn-edit { background: #f0fdf4; color: #0f766e; }
.abtn-del  { background: #fef2f2; color: #dc2626; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
.modal-box  { background: #fff; border-radius: 18px; width: 520px; max-width: 95vw; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
.modal-sm   { width: 380px; }
.modal-head { display: flex; align-items: flex-start; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid #f0fdf4; background: #f8fffe; }
.modal-head h3 { margin: 0 0 6px; font-size: 16px; font-weight: 700; color: #064e3b; }
.modal-close{ background: none; border: none; font-size: 16px; cursor: pointer; color: #6b7280; }
.modal-body { padding: 20px; }
.modal-foot { padding: 16px 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; }

.info-grid  { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 16px; }
.info-item  { display: flex; flex-direction: column; gap: 3px; }
.info-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
.info-item span:last-child { font-size: 14px; font-weight: 600; color: #0f172a; }
.txt-green  { color: #059669 !important; }
.txt-red    { color: #dc2626 !important; }
.audit-note { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #065f46; display: flex; align-items: center; gap: 7px; }

.btn-primary { padding: 9px 20px; background: #0f766e; color: #fff; border: none; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-pdf     { padding: 9px 20px; background: #dc2626; color: #fff; border: none; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.btn-outline { padding: 9px 20px; background: #fff; color: #374151; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-delete  { padding: 9px 20px; background: #dc2626; color: #fff; border: none; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }

@media (max-width: 768px) {
    .ct-stats   { grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .ct-num     { font-size: 22px; }
    .ct-lbl     { font-size: 11px; }
    .ct-stat    { padding: 12px; gap: 10px; flex-direction: column; text-align: center; }
    .ct-stat i  { font-size: 22px; }
    .table-scroll { -webkit-overflow-scrolling: touch; }
    .ct-table   { font-size: 12px; min-width: 800px; }
    .ct-table th, .ct-table td { padding: 8px; }
    .action-btns { gap: 3px; }
    .modal-box  { width: 96vw; }
    .info-grid  { grid-template-columns: 1fr; }
    .ct-head    { flex-direction: column; align-items: flex-start; gap: 10px; }
    .btn-new    { width: 100%; justify-content: center; }
}
</style>
