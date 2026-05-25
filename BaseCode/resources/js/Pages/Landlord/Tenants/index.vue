<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref } from 'vue'

const tenants = ref([
    { id: 1, name: 'Nguyễn Văn A', phone: '0912 345 678', cccd: '036091234567', room: 'Phòng 101', floor: 1, moveIn: '2025-06-01', people: 2, status: 'active', avatar: 'A' },
    { id: 2, name: 'Trần Thị B',   phone: '0987 654 321', cccd: '045098765432', room: 'Phòng 102', floor: 1, moveIn: '2026-01-15', people: 1, status: 'active', avatar: 'B' },
    { id: 3, name: 'Lê Minh C',    phone: '0901 111 222', cccd: '038001112222', room: 'Phòng 201', floor: 2, moveIn: '2025-09-01', people: 3, status: 'active', avatar: 'C' },
    { id: 4, name: 'Phạm Thị D',   phone: '0933 444 555', cccd: '034044455555', room: 'Phòng 202', floor: 2, moveIn: '2026-04-20', people: 2, status: 'active', avatar: 'D' },
    { id: 5, name: 'Hoàng Văn E',  phone: '0966 777 888', cccd: '040077788888', room: 'Phòng 301', floor: 3, moveIn: '2025-03-01', people: 1, status: 'leaving', avatar: 'E' },
    { id: 6, name: 'Ngô Thị F',    phone: '0944 333 111', cccd: '038033311111', room: 'Phòng 205', floor: 2, moveIn: '2026-02-10', people: 2, status: 'active', avatar: 'F' },
])

const showModal  = ref(false)
const showAdd    = ref(false)
const selected   = ref(null)
const searchQ    = ref('')

const filtered = ref(() => tenants.value)

const openDetail = (t) => { selected.value = t; showModal.value = true }
const closeModal = () => { showModal.value = false }

const statusMap = {
    active:  { label: 'Đang ở',      cls: 'st-active' },
    leaving: { label: 'Sắp rời đi',  cls: 'st-leaving' },
}

const avatarColors = ['#0f766e','#1d4ed8','#7c3aed','#b45309','#dc2626','#0891b2']
const avatarColor  = (i) => avatarColors[i % avatarColors.length]

const formatDate = (d) => new Date(d).toLocaleDateString('vi-VN')
</script>

<template>
    <LandlordLayout>
        <template #header-title><h1 class="ll-header-title">Quản Lý Người Thuê Trọ</h1></template>

        <div class="tn-wrap">
            <!-- Top bar -->
            <div class="tn-topbar">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input v-model="searchQ" class="search-input" placeholder="Tìm theo tên, phòng, SĐT..." />
                </div>
                <button class="btn-add" @click="showAdd = true"><i class="bi bi-person-plus-fill"></i> Thêm Người Thuê</button>
            </div>

            <!-- Stats -->
            <div class="tn-stats">
                <div class="tn-stat ts1"><i class="bi bi-people-fill"></i><div><div class="ts-num">{{ tenants.length }}</div><div class="ts-lbl">Tổng Người Thuê</div></div></div>
                <div class="tn-stat ts2"><i class="bi bi-person-check-fill"></i><div><div class="ts-num">{{ tenants.filter(t=>t.status==='active').length }}</div><div class="ts-lbl">Đang Ở</div></div></div>
                <div class="tn-stat ts3"><i class="bi bi-people"></i><div><div class="ts-num">{{ tenants.reduce((s,t)=>s+t.people,0) }}</div><div class="ts-lbl">Tổng Số Người</div></div></div>
                <div class="tn-stat ts4"><i class="bi bi-house-fill"></i><div><div class="ts-num">{{ new Set(tenants.map(t=>t.room)).size }}</div><div class="ts-lbl">Phòng Có Người</div></div></div>
            </div>

            <!-- Tenant cards -->
            <div class="tn-grid">
                <div
                    v-for="(t, i) in tenants"
                    :key="t.id"
                    class="tn-card"
                    @click="openDetail(t)"
                >
                    <div class="tn-card-head">
                        <div class="tn-avatar" :style="{ background: avatarColor(i) }">{{ t.avatar }}</div>
                        <div class="tn-basic">
                            <div class="tn-name">{{ t.name }}</div>
                            <div class="tn-phone"><i class="bi bi-telephone-fill"></i> {{ t.phone }}</div>
                        </div>
                        <span :class="['status-pill', statusMap[t.status].cls]">{{ statusMap[t.status].label }}</span>
                    </div>
                    <div class="tn-card-body">
                        <div class="tn-info-row"><i class="bi bi-building"></i> {{ t.room }} (Tầng {{ t.floor }})</div>
                        <div class="tn-info-row"><i class="bi bi-calendar3"></i> Vào ở: {{ formatDate(t.moveIn) }}</div>
                        <div class="tn-info-row"><i class="bi bi-people"></i> {{ t.people }} người</div>
                        <div class="tn-info-row"><i class="bi bi-credit-card"></i> CCCD: {{ t.cccd }}</div>
                    </div>
                    <div class="tn-card-foot">
                        <button class="tn-btn-detail" @click.stop="openDetail(t)"><i class="bi bi-eye"></i> Chi Tiết</button>
                        <button class="tn-btn-contract"><i class="bi bi-file-earmark-text"></i> Hợp Đồng</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Teleport to="body">
            <div v-if="showModal && selected" class="modal-overlay" @click.self="closeModal">
                <div class="modal-box">
                    <div class="modal-head">
                        <div class="modal-avatar" :style="{ background: avatarColor(selected.id - 1) }">{{ selected.avatar }}</div>
                        <div>
                            <h3>{{ selected.name }}</h3>
                            <span :class="['status-pill', statusMap[selected.status].cls]">{{ statusMap[selected.status].label }}</span>
                        </div>
                        <button @click="closeModal" class="modal-close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="detail-grid">
                            <div class="ditem"><span class="dlabel">Số điện thoại</span><span>{{ selected.phone }}</span></div>
                            <div class="ditem"><span class="dlabel">CCCD</span><span>{{ selected.cccd }}</span></div>
                            <div class="ditem"><span class="dlabel">Phòng</span><span>{{ selected.room }}</span></div>
                            <div class="ditem"><span class="dlabel">Tầng</span><span>{{ selected.floor }}</span></div>
                            <div class="ditem"><span class="dlabel">Ngày vào ở</span><span>{{ formatDate(selected.moveIn) }}</span></div>
                            <div class="ditem"><span class="dlabel">Số người ở</span><span>{{ selected.people }} người</span></div>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn-outline" @click="closeModal">Đóng</button>
                        <button class="btn-edit"><i class="bi bi-pencil"></i> Chỉnh Sửa</button>
                        <button class="btn-primary"><i class="bi bi-file-earmark-text"></i> Xem Hợp Đồng</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>

<style scoped>
.tn-wrap { display: flex; flex-direction: column; gap: 20px; }

.tn-topbar { display: flex; align-items: center; gap: 12px; justify-content: space-between; }
.search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1.5px solid #d1fae5; border-radius: 10px; padding: 8px 14px; flex: 1; max-width: 400px; }
.search-box i { color: #0f766e; }
.search-input { border: none; outline: none; font-size: 14px; flex: 1; background: transparent; }

.btn-add { display: flex; align-items: center; gap: 7px; padding: 9px 18px; background: #0f766e; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-add:hover { background: #0d9488; }

.tn-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.tn-stat { border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 14px; color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.tn-stat i { font-size: 26px; opacity: 0.85; }
.ts-num { font-size: 26px; font-weight: 800; line-height: 1; }
.ts-lbl { font-size: 11px; opacity: 0.85; margin-top: 3px; }
.ts1 { background: linear-gradient(135deg, #0f766e, #0d9488); }
.ts2 { background: linear-gradient(135deg, #15803d, #16a34a); }
.ts3 { background: linear-gradient(135deg, #1d4ed8, #2563eb); }
.ts4 { background: linear-gradient(135deg, #7c3aed, #8b5cf6); }

/* Cards grid */
.tn-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.tn-card {
    background: #fff; border-radius: 16px; padding: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1.5px solid #f0fdf4;
    cursor: pointer; transition: all 0.2s;
}
.tn-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(15,118,110,0.12); border-color: #6ee7b7; }

.tn-card-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.tn-avatar { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 18px; font-weight: 800; flex-shrink: 0; }
.tn-basic { flex: 1; min-width: 0; }
.tn-name  { font-size: 15px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tn-phone { font-size: 12px; color: #6b7280; margin-top: 2px; display: flex; align-items: center; gap: 4px; }

.tn-card-body { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
.tn-info-row { font-size: 13px; color: #374151; display: flex; align-items: center; gap: 7px; }
.tn-info-row i { color: #0f766e; width: 14px; }

.tn-card-foot { display: flex; gap: 8px; }
.tn-btn-detail, .tn-btn-contract {
    flex: 1; padding: 7px; border-radius: 8px; font-size: 12px; font-weight: 600;
    border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px;
}
.tn-btn-detail   { background: #f0fdf4; color: #0f766e; }
.tn-btn-contract { background: #eff6ff; color: #1d4ed8; }

.status-pill { padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.st-active  { background: #dcfce7; color: #15803d; }
.st-leaving { background: #fef9c3; color: #854d0e; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
.modal-box { background: #fff; border-radius: 18px; width: 480px; max-width: 95vw; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
.modal-head { display: flex; align-items: center; gap: 14px; padding: 18px 20px; border-bottom: 1px solid #f0fdf4; background: #f8fffe; }
.modal-avatar { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; font-weight: 800; flex-shrink: 0; }
.modal-head h3 { margin: 0 0 6px; font-size: 17px; font-weight: 700; color: #064e3b; }
.modal-close { background: none; border: none; font-size: 16px; cursor: pointer; color: #6b7280; margin-left: auto; }
.modal-body { padding: 20px; }
.modal-foot { padding: 16px 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 8px; }

.detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.ditem { display: flex; flex-direction: column; gap: 3px; }
.dlabel { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
.ditem span:last-child { font-size: 14px; font-weight: 600; color: #0f172a; }

.btn-primary { padding: 9px 18px; background: #0f766e; color: #fff; border: none; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.btn-edit    { padding: 9px 18px; background: #fef9c3; color: #854d0e; border: none; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.btn-outline { padding: 9px 18px; background: #fff; color: #374151; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; }

@media (max-width: 768px) {
    .tn-topbar   { flex-direction: column; align-items: stretch; }
    .search-box  { max-width: 100%; }
    .btn-add     { width: 100%; justify-content: center; }
    .tn-stats    { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .ts-num      { font-size: 20px; }
    .tn-stat     { padding: 12px; gap: 10px; }
    .tn-stat i   { font-size: 20px; }
    .tn-grid     { grid-template-columns: 1fr; }
    .modal-box   { width: 96vw; }
    .detail-grid { grid-template-columns: 1fr; }
    .modal-foot  { flex-wrap: wrap; }
    .modal-foot button { flex: 1; justify-content: center; }
}
</style>
