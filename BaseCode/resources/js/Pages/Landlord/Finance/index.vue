<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed, reactive } from 'vue'

// Mock rooms for input
const rooms = ref([
    { id: 'P101', name: 'Phòng 101', tenants: 2, rent: 2800000, elecStart: 1210, elecEnd: 1350, waterStart: 45, waterEnd: 52, elecPrice: 3500, waterPrice: 15000, status: 'paid' },
    { id: 'P102', name: 'Phòng 102', tenants: 1, rent: 2800000, elecStart: 890, elecEnd: 1010, waterStart: 30, waterEnd: 35, elecPrice: 3500, waterPrice: 15000, status: 'pending' },
    { id: 'P103', name: 'Phòng 201', tenants: 3, rent: 3200000, elecStart: 2100, elecEnd: 2290, waterStart: 60, waterEnd: 71, elecPrice: 3500, waterPrice: 15000, status: 'overdue' },
    { id: 'P104', name: 'Phòng 202', tenants: 2, rent: 3200000, elecStart: 1540, elecEnd: 1680, waterStart: 44, waterEnd: 51, elecPrice: 3500, waterPrice: 15000, status: 'pending' },
    { id: 'P105', name: 'Phòng 301', tenants: 1, rent: 3500000, elecStart: 720, elecEnd: 810, waterStart: 22, waterEnd: 26, elecPrice: 3500, waterPrice: 15000, status: 'paid' },
])

const month = ref('2026-05')

const calcElec  = (r) => (r.elecEnd - r.elecStart) * r.elecPrice
const calcWater = (r) => (r.waterEnd - r.waterStart) * r.waterPrice
const calcTotal = (r) => r.rent + calcElec(r) + calcWater(r)
const calcPerPerson = (r) => Math.ceil(calcTotal(r) / r.tenants)

const totalRevenue   = computed(() => rooms.value.reduce((s, r) => s + calcTotal(r), 0))
const totalCollected = computed(() => rooms.value.filter(r => r.status === 'paid').reduce((s, r) => s + calcTotal(r), 0))
const totalDebt      = computed(() => rooms.value.filter(r => r.status !== 'paid').reduce((s, r) => s + calcTotal(r), 0))

const formatMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'

const statusMap = {
    paid:    { label: 'Đã Thu',         cls: 'st-paid' },
    pending: { label: 'Chờ Thanh Toán', cls: 'st-pending' },
    overdue: { label: 'Quá Hạn',        cls: 'st-overdue' },
}

const markPaid = (room) => { room.status = 'paid' }
const debtRooms = computed(() => rooms.value.filter(r => r.status !== 'paid'))

// ── Ảnh đồng hồ ──────────────────────────────────────────
const showMeterModal = ref(false)
const meterRoom      = ref(null)

// Lưu ảnh theo room id: { P101: { elecStart, elecEnd, waterStart, waterEnd } }
const meterPhotos = reactive({})

const openMeterModal = (room) => {
    meterRoom.value = room
    if (!meterPhotos[room.id]) {
        meterPhotos[room.id] = { elecStart: null, elecEnd: null, waterStart: null, waterEnd: null }
    }
    showMeterModal.value = true
}
const closeMeterModal = () => { showMeterModal.value = false; meterRoom.value = null }

const handleMeterPhoto = (roomId, field, e) => {
    const file = e.target.files[0]
    if (!file) return
    const reader = new FileReader()
    reader.onload = (ev) => { meterPhotos[roomId][field] = ev.target.result }
    reader.readAsDataURL(file)
}

const photoCount = (roomId) => {
    if (!meterPhotos[roomId]) return 0
    return Object.values(meterPhotos[roomId]).filter(Boolean).length
}
</script>

<template>
    <LandlordLayout>
        <template #header-title><h1 class="ll-header-title">Quản Lý Tài Chính ⭐</h1></template>

        <div class="fin-wrap">
            <!-- Period selector -->
            <div class="fin-topbar">
                <div class="period-box">
                    <i class="bi bi-calendar3"></i>
                    <span>Kỳ tháng:</span>
                    <input type="month" v-model="month" class="month-input" />
                </div>
                <div class="fin-summary-chips">
                    <span class="chip chip-total">Tổng dự kiến: <strong>{{ formatMoney(totalRevenue) }}</strong></span>
                    <span class="chip chip-paid">Đã thu: <strong>{{ formatMoney(totalCollected) }}</strong></span>
                    <span class="chip chip-debt" :class="{ 'chip-debt-blink': debtRooms.length > 0 }">
                        Còn nợ: <strong>{{ formatMoney(totalDebt) }}</strong>
                    </span>
                </div>
            </div>

            <!-- Debt Alert -->
            <div v-if="debtRooms.length > 0" class="debt-alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ debtRooms.length }} phòng chưa đóng tiền: <strong>{{ debtRooms.map(r => r.name).join(', ') }}</strong></span>
            </div>

            <!-- Input table -->
            <div class="fin-card">
                <div class="fin-card-head">
                    <h3 class="fin-title"><i class="bi bi-table"></i> Nhập Chỉ Số Điện / Nước & Tính Tiền</h3>
                    <button class="btn-export"><i class="bi bi-file-earmark-pdf"></i> Xuất Báo Cáo</button>
                </div>

                <div class="table-scroll">
                    <table class="fin-table">
                        <thead>
                            <tr>
                                <th>Phòng</th>
                                <th>Số người</th>
                                <th>Tiền phòng</th>
                                <th colspan="2">Điện (kWh)</th>
                                <th>Tiền điện</th>
                                <th colspan="2">Nước (m³)</th>
                                <th>Tiền nước</th>
                                <th>Tổng</th>
                                <th>/Người</th>
                                <th>Trạng thái</th>
                                <th>Ảnh ĐH</th>
                                <th>Hành động</th>
                            </tr>
                            <tr class="thead-sub">
                                <th></th><th></th><th></th>
                                <th>Đầu kỳ</th><th>Cuối kỳ</th>
                                <th></th>
                                <th>Đầu kỳ</th><th>Cuối kỳ</th>
                                <th></th><th></th><th></th><th></th><th></th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="room in rooms"
                                :key="room.id"
                                :class="{ 'row-overdue': room.status === 'overdue', 'row-paid': room.status === 'paid' }"
                            >
                                <td class="td-room">{{ room.name }}</td>
                                <td class="td-center">
                                    <input type="number" v-model.number="room.tenants" class="num-input" min="1" />
                                </td>
                                <td class="td-money">{{ formatMoney(room.rent) }}</td>
                                <!-- Điện -->
                                <td><input type="number" v-model.number="room.elecStart" class="num-input" /></td>
                                <td><input type="number" v-model.number="room.elecEnd" class="num-input" /></td>
                                <td class="td-money calc-val">{{ formatMoney(calcElec(room)) }}</td>
                                <!-- Nước -->
                                <td><input type="number" v-model.number="room.waterStart" class="num-input" /></td>
                                <td><input type="number" v-model.number="room.waterEnd" class="num-input" /></td>
                                <td class="td-money calc-val">{{ formatMoney(calcWater(room)) }}</td>
                                <!-- Total -->
                                <td class="td-money td-total">{{ formatMoney(calcTotal(room)) }}</td>
                                <td class="td-money td-perperson">{{ formatMoney(calcPerPerson(room)) }}</td>
                                <!-- Status -->
                                <td>
                                    <span :class="['status-pill', statusMap[room.status].cls]">
                                        {{ statusMap[room.status].label }}
                                    </span>
                                </td>
                                <!-- Meter photos -->
                                <td class="td-center">
                                    <button class="btn-meter" @click="openMeterModal(room)">
                                        <i class="bi bi-camera-fill"></i>
                                        <span v-if="photoCount(room.id) > 0" class="photo-badge">{{ photoCount(room.id) }}/4</span>
                                    </button>
                                </td>
                                <td>
                                    <button
                                        v-if="room.status !== 'paid'"
                                        class="btn-confirm-pay"
                                        @click="markPaid(room)"
                                    >
                                        <i class="bi bi-check-circle"></i> Xác Nhận Thu
                                    </button>
                                    <span v-else class="txt-done"><i class="bi bi-check-all"></i> Đã xong</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="tfoot-row">
                                <td colspan="9" class="tfoot-label">TỔNG CỘNG</td>
                                <td class="tfoot-total">{{ formatMoney(totalRevenue) }}</td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Debt list -->
            <div class="debt-card" v-if="debtRooms.length > 0">
                <h3 class="debt-title"><i class="bi bi-exclamation-circle-fill"></i> Danh Sách Nợ — Cần Thu</h3>
                <div class="debt-list">
                    <div v-for="room in debtRooms" :key="room.id" class="debt-item">
                        <div class="debt-room">{{ room.name }}</div>
                        <div class="debt-amount">{{ formatMoney(calcTotal(room)) }}</div>
                        <span :class="['status-pill', statusMap[room.status].cls]">{{ statusMap[room.status].label }}</span>
                        <button class="btn-confirm-pay" @click="markPaid(room)">
                            <i class="bi bi-check-circle"></i> Xác Nhận Thu
                        </button>
                    </div>
                </div>
            </div>

            <!-- Price config -->
            <div class="price-config-card">
                <h3 class="fin-title mb"><i class="bi bi-sliders"></i> Cấu Hình Đơn Giá</h3>
                <div class="price-inputs">
                    <div class="price-field">
                        <label>Đơn giá điện (đ/kWh)</label>
                        <input type="number" v-model.number="rooms[0].elecPrice" class="price-input" placeholder="3500" />
                    </div>
                    <div class="price-field">
                        <label>Đơn giá nước (đ/m³)</label>
                        <input type="number" v-model.number="rooms[0].waterPrice" class="price-input" placeholder="15000" />
                    </div>
                    <button class="btn-apply">Áp Dụng Cho Tất Cả Phòng</button>
                </div>
            </div>
        </div>

        <!-- ── Modal Ảnh Đồng Hồ ── -->
        <Teleport to="body">
            <div v-if="showMeterModal && meterRoom" class="modal-overlay" @click.self="closeMeterModal">
                <div class="meter-modal">
                    <div class="meter-head">
                        <div>
                            <h3>📷 Ảnh Đồng Hồ — {{ meterRoom.name }}</h3>
                            <p class="meter-sub">Chụp ảnh số đồng hồ đầu kỳ &amp; cuối kỳ để gửi minh bạch cho người thuê</p>
                        </div>
                        <button class="modal-close" @click="closeMeterModal"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="meter-body">
                        <!-- Điện -->
                        <div class="meter-section">
                            <div class="meter-sec-title elec-title">
                                <span class="meter-icon elec-icon">⚡</span>
                                Đồng Hồ Điện
                            </div>
                            <div class="meter-photo-row">
                                <div class="meter-photo-box">
                                    <div class="mph-label">Đầu kỳ — {{ meterRoom.elecStart }} kWh</div>
                                    <label class="mph-upload" :class="{ 'mph-done': meterPhotos[meterRoom.id]?.elecStart }">
                                        <input type="file" accept="image/*" @change="handleMeterPhoto(meterRoom.id, 'elecStart', $event)" style="display:none" />
                                        <img v-if="meterPhotos[meterRoom.id]?.elecStart" :src="meterPhotos[meterRoom.id].elecStart" class="mph-img" />
                                        <div v-else class="mph-placeholder">
                                            <i class="bi bi-camera"></i>
                                            <span>Chụp / Tải ảnh</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="meter-arrow"><i class="bi bi-arrow-right"></i></div>
                                <div class="meter-photo-box">
                                    <div class="mph-label">Cuối kỳ — {{ meterRoom.elecEnd }} kWh</div>
                                    <label class="mph-upload" :class="{ 'mph-done': meterPhotos[meterRoom.id]?.elecEnd }">
                                        <input type="file" accept="image/*" @change="handleMeterPhoto(meterRoom.id, 'elecEnd', $event)" style="display:none" />
                                        <img v-if="meterPhotos[meterRoom.id]?.elecEnd" :src="meterPhotos[meterRoom.id].elecEnd" class="mph-img" />
                                        <div v-else class="mph-placeholder">
                                            <i class="bi bi-camera"></i>
                                            <span>Chụp / Tải ảnh</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="meter-result elec-result">
                                    <div class="mr-num">{{ meterRoom.elecEnd - meterRoom.elecStart }} kWh</div>
                                    <div class="mr-price">{{ formatMoney(calcElec(meterRoom)) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Nước -->
                        <div class="meter-section">
                            <div class="meter-sec-title water-title">
                                <span class="meter-icon water-icon">💧</span>
                                Đồng Hồ Nước
                            </div>
                            <div class="meter-photo-row">
                                <div class="meter-photo-box">
                                    <div class="mph-label">Đầu kỳ — {{ meterRoom.waterStart }} m³</div>
                                    <label class="mph-upload" :class="{ 'mph-done': meterPhotos[meterRoom.id]?.waterStart }">
                                        <input type="file" accept="image/*" @change="handleMeterPhoto(meterRoom.id, 'waterStart', $event)" style="display:none" />
                                        <img v-if="meterPhotos[meterRoom.id]?.waterStart" :src="meterPhotos[meterRoom.id].waterStart" class="mph-img" />
                                        <div v-else class="mph-placeholder">
                                            <i class="bi bi-camera"></i>
                                            <span>Chụp / Tải ảnh</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="meter-arrow"><i class="bi bi-arrow-right"></i></div>
                                <div class="meter-photo-box">
                                    <div class="mph-label">Cuối kỳ — {{ meterRoom.waterEnd }} m³</div>
                                    <label class="mph-upload" :class="{ 'mph-done': meterPhotos[meterRoom.id]?.waterEnd }">
                                        <input type="file" accept="image/*" @change="handleMeterPhoto(meterRoom.id, 'waterEnd', $event)" style="display:none" />
                                        <img v-if="meterPhotos[meterRoom.id]?.waterEnd" :src="meterPhotos[meterRoom.id].waterEnd" class="mph-img" />
                                        <div v-else class="mph-placeholder">
                                            <i class="bi bi-camera"></i>
                                            <span>Chụp / Tải ảnh</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="meter-result water-result">
                                    <div class="mr-num">{{ meterRoom.waterEnd - meterRoom.waterStart }} m³</div>
                                    <div class="mr-price">{{ formatMoney(calcWater(meterRoom)) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress -->
                        <div class="meter-progress">
                            <div class="mp-bar"><div class="mp-fill" :style="{ width: (photoCount(meterRoom.id) / 4 * 100) + '%' }"></div></div>
                            <span class="mp-text">{{ photoCount(meterRoom.id) }}/4 ảnh đã tải lên</span>
                        </div>
                    </div>

                    <div class="meter-foot">
                        <button class="btn-outline" @click="closeMeterModal">Đóng</button>
                        <button class="btn-send-img" :disabled="photoCount(meterRoom.id) === 0">
                            <i class="bi bi-send-fill"></i> Gửi Ảnh Cho Người Thuê
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>

<style scoped>
.fin-wrap { display: flex; flex-direction: column; gap: 20px; }

/* Topbar */
.fin-topbar {
    display: flex; align-items: center; justify-content: space-between;
    background: #fff; border-radius: 14px; padding: 14px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #f0fdf4;
    flex-wrap: wrap; gap: 12px;
}
.period-box { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #374151; }
.period-box i { color: #0f766e; font-size: 17px; }
.month-input { border: 1.5px solid #d1fae5; border-radius: 8px; padding: 6px 12px; font-size: 14px; outline: none; }
.month-input:focus { border-color: #0f766e; }

.fin-summary-chips { display: flex; gap: 10px; flex-wrap: wrap; }
.chip { padding: 6px 14px; border-radius: 100px; font-size: 13px; }
.chip-total  { background: #f0fdf4; color: #064e3b; border: 1px solid #bbf7d0; }
.chip-paid   { background: #dcfce7; color: #15803d; }
.chip-debt   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.5} }
.chip-debt-blink { animation: blink 2s infinite; }

/* Debt alert */
.debt-alert {
    display: flex; align-items: center; gap: 10px;
    background: #fef2f2; border: 1.5px solid #fca5a5;
    border-radius: 12px; padding: 12px 18px;
    color: #b91c1c; font-size: 14px; font-weight: 600;
}
.debt-alert i { font-size: 18px; }

/* Main card */
.fin-card {
    background: #fff; border-radius: 16px; padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #f0fdf4;
}
.fin-card-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.fin-title { font-size: 15px; font-weight: 700; color: #064e3b; margin: 0; display: flex; align-items: center; gap: 7px; }
.mb { margin-bottom: 14px !important; }

.btn-export {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 16px; background: #dc2626; color: #fff;
    border: none; border-radius: 9px; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: background 0.15s;
}
.btn-export:hover { background: #b91c1c; }

/* Table */
.table-scroll { overflow-x: auto; }
.fin-table {
    width: 100%; border-collapse: collapse;
    font-size: 13px; min-width: 900px;
}
.fin-table th {
    background: #f0fdf4; color: #065f46;
    padding: 10px 8px; text-align: center;
    font-weight: 700; border-bottom: 2px solid #d1fae5;
    white-space: nowrap;
}
.thead-sub th { background: #f8fffe; font-size: 11px; color: #6b7280; padding: 5px 8px; }
.fin-table td { padding: 10px 8px; border-bottom: 1px solid #f0fdf4; vertical-align: middle; }

.td-room    { font-weight: 700; color: #064e3b; white-space: nowrap; }
.td-center  { text-align: center; }
.td-money   { text-align: right; font-weight: 600; color: #374151; white-space: nowrap; }
.td-total   { color: #0f766e; font-weight: 800; font-size: 14px; }
.td-perperson { color: #6b7280; }
.calc-val   { color: #374151; }

.row-overdue td { background: #fff5f5; }
.row-paid   td  { background: #fafffe; }

.num-input {
    width: 72px; padding: 5px 8px;
    border: 1.5px solid #e2e8f0; border-radius: 6px;
    font-size: 13px; text-align: center; outline: none;
}
.num-input:focus { border-color: #0f766e; }

/* Status pills */
.status-pill { padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.st-paid    { background: #dcfce7; color: #15803d; }
.st-pending { background: #fef9c3; color: #854d0e; }
.st-overdue { background: #fee2e2; color: #b91c1c; }

.btn-confirm-pay {
    display: flex; align-items: center; gap: 5px;
    padding: 5px 12px; background: #0f766e; color: #fff;
    border: none; border-radius: 7px; font-size: 12px; font-weight: 600;
    cursor: pointer; white-space: nowrap;
    transition: background 0.15s;
}
.btn-confirm-pay:hover { background: #0d9488; }
.txt-done { color: #16a34a; font-size: 12px; font-weight: 600; }

/* Tfoot */
.tfoot-row td { border-top: 2px solid #d1fae5; padding: 12px 8px; }
.tfoot-label { text-align: right; font-weight: 700; color: #064e3b; font-size: 14px; padding-right: 12px; }
.tfoot-total { text-align: right; font-weight: 800; color: #0f766e; font-size: 16px; white-space: nowrap; }

/* Debt card */
.debt-card {
    background: #fff5f5; border: 1.5px solid #fca5a5;
    border-radius: 16px; padding: 20px;
}
.debt-title { font-size: 15px; font-weight: 700; color: #b91c1c; margin: 0 0 14px; display: flex; align-items: center; gap: 7px; }
.debt-list { display: flex; flex-direction: column; gap: 10px; }
.debt-item {
    display: flex; align-items: center; gap: 14px;
    background: #fff; border-radius: 10px; padding: 12px 16px;
    border: 1px solid #fecaca;
}
.debt-room   { font-weight: 700; color: #0f172a; min-width: 100px; }
.debt-amount { font-weight: 800; color: #dc2626; min-width: 130px; font-size: 14px; }

/* Price config */
.price-config-card {
    background: #fff; border-radius: 16px; padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid #f0fdf4;
}
.price-inputs { display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap; }
.price-field { display: flex; flex-direction: column; gap: 6px; }
.price-field label { font-size: 13px; font-weight: 600; color: #374151; }
.price-input {
    padding: 8px 12px; border: 1.5px solid #d1fae5;
    border-radius: 8px; font-size: 14px; outline: none; width: 160px;
}
.price-input:focus { border-color: #0f766e; }
.btn-apply {
    padding: 9px 18px; background: #0f766e; color: #fff;
    border: none; border-radius: 9px; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: background 0.15s; height: 38px;
}
.btn-apply:hover { background: #0d9488; }

/* ── Camera button ── */
.btn-meter {
    position: relative;
    width: 34px; height: 34px;
    border-radius: 8px;
    background: #f0fdf4; border: 1.5px solid #d1fae5;
    color: #0f766e; font-size: 15px;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}
.btn-meter:hover { background: #d1fae5; transform: scale(1.1); }
.photo-badge {
    position: absolute; top: -6px; right: -6px;
    background: #0f766e; color: #fff;
    font-size: 9px; font-weight: 700;
    padding: 1px 5px; border-radius: 100px;
    border: 1.5px solid #fff;
}

/* ── Meter Modal ── */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(3px); }
.meter-modal {
    background: #fff; border-radius: 20px;
    width: 680px; max-width: 96vw; max-height: 90vh;
    box-shadow: 0 24px 64px rgba(0,0,0,0.2);
    overflow: hidden; display: flex; flex-direction: column;
}
.meter-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 20px 24px; border-bottom: 1px solid #f0fdf4;
    background: linear-gradient(135deg, #f0fdf4, #d1fae5);
}
.meter-head h3 { margin: 0 0 4px; font-size: 17px; font-weight: 700; color: #064e3b; }
.meter-sub { margin: 0; font-size: 12px; color: #6b7280; }
.modal-close { background: none; border: none; font-size: 16px; cursor: pointer; color: #6b7280; padding: 4px; }

.meter-body { padding: 20px 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }

.meter-section { background: #f8fafc; border-radius: 14px; padding: 16px; }
.meter-sec-title {
    font-size: 14px; font-weight: 700;
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 14px;
}
.elec-title  { color: #b45309; }
.water-title { color: #1d4ed8; }
.meter-icon  { font-size: 18px; }

.meter-photo-row { display: flex; align-items: center; gap: 12px; }
.meter-arrow { color: #94a3b8; font-size: 18px; flex-shrink: 0; }

.meter-photo-box { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.mph-label { font-size: 11px; font-weight: 700; color: #374151; text-align: center; }

.mph-upload {
    display: block;
    width: 100%; aspect-ratio: 4/3;
    border: 2px dashed #d1d5db; border-radius: 10px;
    cursor: pointer; overflow: hidden;
    transition: border-color 0.15s;
    background: #fff;
}
.mph-upload:hover { border-color: #0f766e; }
.mph-done { border-style: solid !important; border-color: #16a34a !important; }

.mph-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.mph-placeholder {
    width: 100%; height: 100%;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 6px; color: #9ca3af;
}
.mph-placeholder i { font-size: 24px; color: #d1d5db; }
.mph-placeholder span { font-size: 11px; }

.meter-result {
    flex-shrink: 0; width: 100px;
    border-radius: 10px; padding: 12px;
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    text-align: center;
}
.elec-result  { background: #fffbeb; }
.water-result { background: #eff6ff; }
.mr-num   { font-size: 16px; font-weight: 800; color: #0f172a; }
.mr-price { font-size: 12px; font-weight: 700; color: #0f766e; }

/* Progress */
.meter-progress { display: flex; align-items: center; gap: 12px; }
.mp-bar { flex: 1; height: 8px; background: #f1f5f9; border-radius: 100px; overflow: hidden; }
.mp-fill { height: 100%; background: linear-gradient(90deg, #0f766e, #34d399); border-radius: 100px; transition: width 0.4s; }
.mp-text { font-size: 12px; color: #6b7280; font-weight: 600; white-space: nowrap; }

.meter-foot {
    padding: 16px 24px; border-top: 1px solid #f0fdf4;
    display: flex; justify-content: flex-end; gap: 10px;
    background: #fafffe;
}
.btn-outline  { padding: 9px 20px; background: #fff; color: #374151; border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-send-img {
    display: flex; align-items: center; gap: 7px;
    padding: 9px 20px; background: #0f766e; color: #fff;
    border: none; border-radius: 9px; font-size: 14px; font-weight: 600;
    cursor: pointer; transition: background 0.15s;
}
.btn-send-img:hover:not(:disabled) { background: #0d9488; }
.btn-send-img:disabled { opacity: 0.5; cursor: not-allowed; }

@media (max-width: 768px) {
    .fin-topbar         { flex-direction: column; align-items: stretch; }
    .fin-summary-chips  { justify-content: flex-start; }
    .fin-card-head      { flex-direction: column; align-items: flex-start; gap: 10px; }
    .btn-export         { width: 100%; justify-content: center; }
    .table-scroll       { -webkit-overflow-scrolling: touch; }
    .fin-table          { font-size: 12px; min-width: 860px; }
    .num-input          { width: 60px; font-size: 12px; }
    .debt-item          { flex-wrap: wrap; gap: 8px; }
    .price-inputs       { flex-direction: column; }
    .price-input        { width: 100%; }
    .btn-apply          { width: 100%; }
    .meter-modal        { width: 96vw; }
    .meter-photo-row    { flex-wrap: wrap; }
    .meter-arrow        { display: none; }
    .meter-result       { width: 100%; }
}
</style>
