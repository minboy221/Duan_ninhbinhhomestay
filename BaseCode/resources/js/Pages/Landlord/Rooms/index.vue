<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed } from 'vue'

const floors = ref([
    {
        id: 1, name: 'Tầng 1',
        rooms: [
            { id: 'P101', name: '101', status: 'occupied',    tenant: 'Nguyễn Văn A', price: 2800000, area: 20, note: '' },
            { id: 'P102', name: '102', status: 'occupied',    tenant: 'Trần Thị B',   price: 2800000, area: 20, note: '' },
            { id: 'P103', name: '103', status: 'vacant',      tenant: null,            price: 3000000, area: 22, note: '' },
            { id: 'P104', name: '104', status: 'maintenance', tenant: null,            price: 2800000, area: 20, note: 'Sửa điện' },
        ]
    },
    {
        id: 2, name: 'Tầng 2',
        rooms: [
            { id: 'P201', name: '201', status: 'occupied', tenant: 'Lê Minh C',   price: 3200000, area: 25, note: '' },
            { id: 'P202', name: '202', status: 'occupied', tenant: 'Phạm Thị D',  price: 3200000, area: 25, note: '' },
            { id: 'P203', name: '203', status: 'occupied', tenant: 'Hoàng Văn E', price: 3200000, area: 25, note: '' },
            { id: 'P204', name: '204', status: 'vacant',   tenant: null,           price: 3500000, area: 28, note: '' },
            { id: 'P205', name: '205', status: 'occupied', tenant: 'Ngô Thị F',   price: 3200000, area: 25, note: '' },
        ]
    },
    {
        id: 3, name: 'Tầng 3',
        rooms: [
            { id: 'P301', name: '301', status: 'occupied', tenant: 'Đinh Văn G', price: 3500000, area: 28, note: '' },
            { id: 'P302', name: '302', status: 'occupied', tenant: 'Vũ Thị H',   price: 3500000, area: 28, note: '' },
            { id: 'P303', name: '303', status: 'occupied', tenant: 'Bùi Văn I',  price: 3500000, area: 28, note: '' },
        ]
    },
])

const selectedRoom = ref(null)
const showModal    = ref(false)
const showAddRoom  = ref(false)
const showConfirmDelete = ref(false)
const newRoomName  = ref('')
const addFloorId   = ref(null)

const statusConfig = {
    occupied:    { label: 'Đã Thuê',   class: 'room-occupied',    dot: 'dot-green'  },
    vacant:      { label: 'Còn Trống', class: 'room-vacant',      dot: 'dot-blue'   },
    maintenance: { label: 'Bảo Trì',  class: 'room-maintenance',  dot: 'dot-yellow' },
}

const totalRooms    = computed(() => floors.value.reduce((s, f) => s + f.rooms.length, 0))
const countByStatus = (status) => floors.value.reduce((s, f) => s + f.rooms.filter(r => r.status === status).length, 0)
const formatMoney   = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'

const openRoom   = (room) => { selectedRoom.value = room; showModal.value = true }
const closeModal = () => { showModal.value = false; selectedRoom.value = null }
const changeStatus = (status) => { if (selectedRoom.value) selectedRoom.value.status = status }

const openAddRoom   = (floorId) => { addFloorId.value = floorId; newRoomName.value = ''; showAddRoom.value = true }
const confirmAddRoom = () => {
    if (!newRoomName.value) return
    const floor = floors.value.find(f => f.id === addFloorId.value)
    if (floor) floor.rooms.push({ id: 'P' + floor.id + newRoomName.value, name: newRoomName.value, status: 'vacant', tenant: null, price: 3000000, area: 20, note: '' })
    showAddRoom.value = false
}
const addFloor = () => {
    const nextId = floors.value.length + 1
    floors.value.push({ id: nextId, name: `Tầng ${nextId}`, rooms: [] })
}
</script>

<template>
    <LandlordLayout>
        <template #header-title><h1 class="ll-header-title">Quản Lý Trọ</h1></template>

        <div class="rooms-wrap">
            <!-- Summary -->
            <div class="rm-stat-row">
                <div class="rm-stat rm-total"><i class="bi bi-building"></i><span>{{ totalRooms }} phòng</span></div>
                <div class="rm-stat rm-occ"><span class="rm-dot dot-green"></span>{{ countByStatus('occupied') }} Đã thuê</div>
                <div class="rm-stat rm-vac"><span class="rm-dot dot-blue"></span>{{ countByStatus('vacant') }} Trống</div>
                <div class="rm-stat rm-maint"><span class="rm-dot dot-yellow"></span>{{ countByStatus('maintenance') }} Bảo trì</div>
                <button class="btn-add-floor" @click="addFloor"><i class="bi bi-plus-circle"></i> Thêm tầng</button>
            </div>

            <!-- Floor Plans -->
            <div v-for="floor in floors" :key="floor.id" class="floor-block">
                <div class="floor-head">
                    <h3 class="floor-name"><i class="bi bi-layers-fill"></i> {{ floor.name }}</h3>
                    <button class="btn-add-room" @click="openAddRoom(floor.id)">
                        <i class="bi bi-plus"></i> Thêm phòng
                    </button>
                </div>
                <div class="floor-rooms">
                    <div
                        v-for="room in floor.rooms"
                        :key="room.id"
                        :class="['room-cell', statusConfig[room.status].class]"
                        @click="openRoom(room)"
                    >
                        <div class="room-num">{{ room.name }}</div>
                        <div class="room-status-badge">{{ statusConfig[room.status].label }}</div>
                        <div class="room-price">{{ formatMoney(room.price) }}/tháng</div>
                        <div v-if="room.tenant" class="room-tenant"><i class="bi bi-person-fill"></i> {{ room.tenant }}</div>
                        <div v-if="room.note" class="room-note"><i class="bi bi-wrench"></i> {{ room.note }}</div>
                    </div>
                    <div v-if="floor.rooms.length === 0" class="floor-empty">Chưa có phòng nào</div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <!-- Room Detail Modal -->
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-box" v-if="selectedRoom">
                    <div class="modal-head">
                        <h3>Phòng {{ selectedRoom.name }}</h3>
                        <button @click="closeModal" class="modal-close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="detail-row">
                            <span class="detail-label">Trạng thái:</span>
                            <span :class="['status-tag', statusConfig[selectedRoom.status].class + '-tag']">
                                {{ statusConfig[selectedRoom.status].label }}
                            </span>
                        </div>
                        <div class="detail-row"><span class="detail-label">Giá thuê:</span><span>{{ formatMoney(selectedRoom.price) }}/tháng</span></div>
                        <div class="detail-row"><span class="detail-label">Diện tích:</span><span>{{ selectedRoom.area }} m²</span></div>
                        <div class="detail-row" v-if="selectedRoom.tenant"><span class="detail-label">Người thuê:</span><span>{{ selectedRoom.tenant }}</span></div>
                        <div class="detail-row" v-if="selectedRoom.note"><span class="detail-label">Ghi chú:</span><span>{{ selectedRoom.note }}</span></div>

                        <div class="status-actions">
                            <p class="detail-label mb-2">Đổi trạng thái:</p>
                            <div class="status-btn-row">
                                <button @click="changeStatus('occupied')"    class="sbtn sbtn-green">Đã Thuê</button>
                                <button @click="changeStatus('vacant')"      class="sbtn sbtn-blue">Còn Trống</button>
                                <button @click="changeStatus('maintenance')" class="sbtn sbtn-yellow">Bảo Trì</button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn-outline" @click="closeModal">Đóng</button>
                        <button class="btn-primary">Lưu Thay Đổi</button>
                    </div>
                </div>
            </div>

            <!-- Add Room Modal -->
            <div v-if="showAddRoom" class="modal-overlay" @click.self="showAddRoom = false">
                <div class="modal-box modal-sm">
                    <div class="modal-head">
                        <h3>Thêm Phòng Mới</h3>
                        <button @click="showAddRoom = false" class="modal-close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Số phòng</label>
                        <input v-model="newRoomName" class="form-input" placeholder="VD: 101, A01, ..." />
                    </div>
                    <div class="modal-foot">
                        <button class="btn-outline" @click="showAddRoom = false">Hủy</button>
                        <button class="btn-primary" @click="confirmAddRoom">Thêm Phòng</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>

<style scoped>
.rooms-wrap { display: flex; flex-direction: column; gap: 20px; }

.rm-stat-row { display: flex; align-items: center; gap: 16px; background: #fff; border-radius: 14px; padding: 14px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f0fdf4; flex-wrap: wrap; }
.rm-stat { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #374151; }
.rm-total { color: #0f766e; font-size: 15px; margin-right: 8px; }
.rm-total i { font-size: 18px; }
.rm-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.dot-green  { background: #16a34a; }
.dot-blue   { background: #2563eb; }
.dot-yellow { background: #d97706; }

.btn-add-floor { margin-left: auto; display: flex; align-items: center; gap: 6px; padding: 8px 16px; background: #0f766e; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-add-floor:hover { background: #0d9488; }

.floor-block { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f0fdf4; }
.floor-head  { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.floor-name  { font-size: 15px; font-weight: 700; color: #064e3b; display: flex; align-items: center; gap: 7px; margin: 0; }
.btn-add-room{ display: flex; align-items: center; gap: 5px; padding: 6px 14px; background: #f0fdf4; color: #0f766e; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-add-room:hover { background: #d1fae5; }

.floor-rooms { display: flex; flex-wrap: wrap; gap: 12px; }
.floor-empty { color: #94a3b8; font-size: 14px; padding: 20px 0; }

.room-cell {
    width: 160px;
    border-radius: 12px;
    padding: 16px 14px;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
    border: 2px solid transparent;
    border-left-width: 5px;
    position: relative;
}
.room-cell:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
.room-occupied    { background: #f0fdf4; border-color: #86efac; border-left-color: #16a34a; }
.room-vacant      { background: #eff6ff; border-color: #93c5fd; border-left-color: #2563eb; }
.room-maintenance { background: #fffbeb; border-color: #fcd34d; border-left-color: #d97706; }

.room-num { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
.room-status-badge { font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 100px; display: inline-block; margin-bottom: 8px; }
.room-occupied    .room-status-badge { background: #dcfce7; color: #15803d; }
.room-vacant      .room-status-badge { background: #dbeafe; color: #1d4ed8; }
.room-maintenance .room-status-badge { background: #fef3c7; color: #b45309; }
.room-price  { font-size: 11px; color: #4b5563; font-weight: 600; }
.room-tenant { font-size: 11px; color: #6b7280; margin-top: 4px; }
.room-note   { font-size: 11px; color: #d97706; margin-top: 4px; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
.modal-box { background: #fff; border-radius: 18px; width: 420px; max-width: 95vw; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
.modal-sm  { width: 320px; }
.modal-head{ display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid #f0fdf4; background: #f8fffe; }
.modal-head h3 { margin: 0; font-size: 16px; font-weight: 700; color: #064e3b; }
.modal-close   { background: none; border: none; font-size: 16px; cursor: pointer; color: #6b7280; }
.modal-body { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
.modal-foot { padding: 16px 20px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; }

.detail-row   { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #374151; }
.detail-label { font-weight: 600; color: #6b7280; min-width: 100px; }
.mb-2 { margin-bottom: 8px; }
.status-tag { padding: 3px 10px; border-radius: 100px; font-size: 12px; font-weight: 700; }
.room-occupied-tag    { background: #dcfce7; color: #15803d; }
.room-vacant-tag      { background: #dbeafe; color: #1d4ed8; }
.room-maintenance-tag { background: #fef3c7; color: #b45309; }
.status-actions { margin-top: 6px; padding-top: 14px; border-top: 1px solid #f1f5f9; }
.status-btn-row { display: flex; gap: 8px; }
.sbtn { padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }
.sbtn-green  { background: #dcfce7; color: #15803d; }
.sbtn-blue   { background: #dbeafe; color: #1d4ed8; }
.sbtn-yellow { background: #fef3c7; color: #b45309; }

.form-label  { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block; }
.form-input  { width: 100%; padding: 9px 12px; border: 1.5px solid #d1fae5; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box; }
.form-input:focus { border-color: #0f766e; }
.btn-primary { padding: 9px 20px; background: #0f766e; color: #fff; border: none; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-primary:hover { background: #0d9488; }
.btn-outline { padding: 9px 20px; background: #fff; color: #374151; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-outline:hover { background: #f8fafc; }

@media (max-width: 640px) {
    .rm-stat-row { gap: 10px; padding: 12px 14px; }
    .rm-stat      { font-size: 13px; }
    .btn-add-floor { width: 100%; justify-content: center; margin-left: 0; }
    .floor-block  { padding: 14px; }
    .floor-rooms  { gap: 10px; }
    .room-cell    { width: calc(50% - 5px); }
    .modal-box    { width: 96vw; }
    .status-btn-row { flex-wrap: wrap; }
}
</style>
