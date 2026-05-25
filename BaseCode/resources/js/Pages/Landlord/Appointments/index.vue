<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed } from 'vue'

const currentYear  = 2026
const currentMonth = 5
const selectedDate = ref(null)
const viewMode = ref('month') // 'month' | 'list'

const appointments = ref([
    { id: 1, name: 'Phạm Văn Khoa', phone: '0911 222 333', room: 'Phòng 103', date: '2026-05-23', time: '09:00', status: 'pending', note: 'Muốn xem phòng tầng 1' },
    { id: 2, name: 'Lê Thị Mai',    phone: '0944 555 666', room: 'Phòng 204', date: '2026-05-23', time: '09:30', status: 'pending', note: '' },
    { id: 3, name: 'Trần Quốc Anh', phone: '0977 888 999', room: 'Phòng 103', date: '2026-05-24', time: '14:00', status: 'approved', note: 'Đi cùng người bạn' },
    { id: 4, name: 'Nguyễn Hà Linh',phone: '0933 100 200', room: 'Phòng 204', date: '2026-05-25', time: '10:00', status: 'approved', note: '' },
    { id: 5, name: 'Vũ Đình Nam',   phone: '0900 300 400', room: 'Phòng 103', date: '2026-05-26', time: '15:00', status: 'rejected', note: '' },
    { id: 6, name: 'Hoàng Thị Lan', phone: '0912 500 600', room: 'Phòng 204', date: '2026-05-27', time: '09:00', status: 'pending', note: 'Hỏi về giá điện' },
])

// Check conflict: same room, same date, within 45 minutes
const hasConflict = (apt) => {
    return appointments.value.some(other => {
        if (other.id === apt.id || other.status === 'rejected') return false
        if (other.room !== apt.room || other.date !== apt.date) return false
        const t1 = timeToMin(apt.time)
        const t2 = timeToMin(other.time)
        return Math.abs(t1 - t2) < 45 && t1 !== t2
    })
}
const timeToMin = (t) => { const [h, m] = t.split(':').map(Number); return h * 60 + m }

// Calendar helpers
const daysInMonth = computed(() => new Date(currentYear, currentMonth, 0).getDate())
const firstDay    = computed(() => new Date(currentYear, currentMonth - 1, 1).getDay())
const calendarDays = computed(() => {
    const days = []
    for (let i = 0; i < (firstDay.value || 7) - 1; i++) days.push(null)
    for (let d = 1; d <= daysInMonth.value; d++) days.push(d)
    return days
})

const aptsForDay = (day) => {
    if (!day) return []
    const dateStr = `${currentYear}-${String(currentMonth).padStart(2,'0')}-${String(day).padStart(2,'0')}`
    return appointments.value.filter(a => a.date === dateStr)
}

const statusMap = {
    pending:  { label: 'Chờ Duyệt',  cls: 'st-pending' },
    approved: { label: 'Đã Duyệt',   cls: 'st-approved' },
    rejected: { label: 'Từ Chối',    cls: 'st-rejected' },
}

const approveApt  = (apt) => { apt.status = 'approved' }
const rejectApt   = (apt) => { apt.status = 'rejected' }
const pendingList = computed(() => appointments.value.filter(a => a.status === 'pending'))
</script>

<template>
    <LandlordLayout>
        <template #header-title><h1 class="ll-header-title">Quản Lý Lịch Hẹn</h1></template>

        <div class="apt-wrap">
            <!-- Summary -->
            <div class="apt-summary">
                <div class="sum-chip sum-total"><i class="bi bi-calendar-event"></i> Tổng: <strong>{{ appointments.length }}</strong></div>
                <div class="sum-chip sum-pending"><i class="bi bi-hourglass-split"></i> Chờ duyệt: <strong>{{ pendingList.length }}</strong></div>
                <div class="sum-chip sum-approved"><i class="bi bi-check-circle"></i> Đã duyệt: <strong>{{ appointments.filter(a=>a.status==='approved').length }}</strong></div>
                <div class="sum-chip sum-rejected"><i class="bi bi-x-circle"></i> Từ chối: <strong>{{ appointments.filter(a=>a.status==='rejected').length }}</strong></div>
            </div>

            <div class="apt-cols">
                <!-- Calendar -->
                <div class="cal-card">
                    <div class="cal-head">
                        <h3 class="cal-title"><i class="bi bi-calendar3"></i> Tháng {{ currentMonth }}/{{ currentYear }}</h3>
                    </div>
                    <div class="cal-grid-head">
                        <span v-for="d in ['T2','T3','T4','T5','T6','T7','CN']" :key="d">{{ d }}</span>
                    </div>
                    <div class="cal-grid">
                        <div
                            v-for="(day, i) in calendarDays"
                            :key="i"
                            :class="['cal-day', day ? 'cal-day-active' : 'cal-day-empty', aptsForDay(day).length > 0 ? 'cal-has-apt' : '']"
                        >
                            <span v-if="day" class="cal-num">{{ day }}</span>
                            <div class="cal-dots">
                                <span
                                    v-for="apt in aptsForDay(day).slice(0,3)"
                                    :key="apt.id"
                                    :class="['cal-dot', `dot-${apt.status}`]"
                                ></span>
                            </div>
                        </div>
                    </div>
                    <div class="cal-legend">
                        <span><span class="cal-dot dot-pending"></span> Chờ duyệt</span>
                        <span><span class="cal-dot dot-approved"></span> Đã duyệt</span>
                        <span><span class="cal-dot dot-rejected"></span> Từ chối</span>
                    </div>
                </div>

                <!-- Right panel -->
                <div class="right-panel">
                    <!-- Pending requests -->
                    <div class="apt-card" v-if="pendingList.length > 0">
                        <h3 class="apt-card-title"><i class="bi bi-bell-fill text-orange"></i> Yêu Cầu Chờ Duyệt ({{ pendingList.length }})</h3>
                        <div class="apt-list">
                            <div v-for="apt in pendingList" :key="apt.id" :class="['apt-item', hasConflict(apt) ? 'apt-conflict' : '']">
                                <div v-if="hasConflict(apt)" class="conflict-badge">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Trùng lịch (&lt;45 phút)
                                </div>
                                <div class="apt-info">
                                    <div class="apt-name">{{ apt.name }} <span class="apt-phone">{{ apt.phone }}</span></div>
                                    <div class="apt-detail"><i class="bi bi-house"></i> {{ apt.room }} &nbsp;·&nbsp; <i class="bi bi-clock"></i> {{ apt.time }} ngày {{ new Date(apt.date).toLocaleDateString('vi-VN') }}</div>
                                    <div v-if="apt.note" class="apt-note"><i class="bi bi-chat-text"></i> {{ apt.note }}</div>
                                </div>
                                <div class="apt-btns">
                                    <button class="abtn-approve" @click="approveApt(apt)"><i class="bi bi-check-lg"></i> Duyệt</button>
                                    <button class="abtn-reject" @click="rejectApt(apt)"><i class="bi bi-x-lg"></i> Từ chối</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="apt-empty"><i class="bi bi-check-all"></i> Không có lịch hẹn nào chờ duyệt</div>

                    <!-- All appointments table -->
                    <div class="apt-card">
                        <h3 class="apt-card-title"><i class="bi bi-list-ul"></i> Tất Cả Lịch Hẹn</h3>
                        <div class="apt-table-wrap">
                            <table class="apt-table">
                                <thead>
                                    <tr>
                                        <th>Khách</th>
                                        <th>Phòng</th>
                                        <th>Ngày & Giờ</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="apt in appointments" :key="apt.id" :class="{ 'row-conflict': hasConflict(apt) }">
                                        <td>
                                            <div class="td-name">{{ apt.name }}</div>
                                            <div class="td-phone-sm">{{ apt.phone }}</div>
                                        </td>
                                        <td>{{ apt.room }}</td>
                                        <td class="td-time">{{ apt.time }} · {{ new Date(apt.date).toLocaleDateString('vi-VN') }}</td>
                                        <td>
                                            <span :class="['status-pill', statusMap[apt.status].cls]">{{ statusMap[apt.status].label }}</span>
                                            <span v-if="hasConflict(apt)" class="conflict-icon" title="Trùng lịch"><i class="bi bi-exclamation-triangle-fill"></i></span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>

<style scoped>
.apt-wrap { display: flex; flex-direction: column; gap: 20px; }

.apt-summary { display: flex; gap: 12px; flex-wrap: wrap; }
.sum-chip { display: flex; align-items: center; gap: 7px; padding: 8px 16px; border-radius: 100px; font-size: 13px; }
.sum-total    { background: #f0fdf4; color: #064e3b; border: 1px solid #bbf7d0; }
.sum-pending  { background: #fffbeb; color: #92400e; border: 1px solid #fcd34d; }
.sum-approved { background: #dcfce7; color: #15803d; }
.sum-rejected { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

.apt-cols { display: grid; grid-template-columns: 320px 1fr; gap: 20px; align-items: flex-start; }

/* Calendar */
.cal-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.cal-head { margin-bottom: 14px; }
.cal-title { font-size: 15px; font-weight: 700; color: #064e3b; margin: 0; display: flex; align-items: center; gap: 7px; }
.cal-grid-head { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 6px; }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.cal-day { min-height: 42px; border-radius: 8px; padding: 4px; display: flex; flex-direction: column; align-items: center; }
.cal-day-empty { }
.cal-day-active { cursor: pointer; }
.cal-day-active:hover { background: #f0fdf4; }
.cal-has-apt { background: #f0fdf4 !important; border: 1px solid #bbf7d0; }
.cal-num { font-size: 13px; font-weight: 600; color: #374151; }
.cal-dots { display: flex; gap: 3px; flex-wrap: wrap; justify-content: center; margin-top: 2px; }
.cal-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.dot-pending  { background: #f59e0b; }
.dot-approved { background: #16a34a; }
.dot-rejected { background: #dc2626; }
.cal-legend { display: flex; gap: 12px; margin-top: 12px; font-size: 12px; color: #6b7280; padding-top: 12px; border-top: 1px solid #f0fdf4; justify-content: center; }
.cal-legend span { display: flex; align-items: center; gap: 5px; }

/* Right panel */
.right-panel { display: flex; flex-direction: column; gap: 16px; }
.apt-card { background: #fff; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.apt-card-title { font-size: 14px; font-weight: 700; color: #064e3b; margin: 0 0 14px; display: flex; align-items: center; gap: 7px; }
.text-orange { color: #d97706; }

.apt-empty { background: #f0fdf4; border-radius: 12px; padding: 20px; text-align: center; color: #059669; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; }

/* Pending items */
.apt-list { display: flex; flex-direction: column; gap: 12px; }
.apt-item {
    border-radius: 12px;
    padding: 14px;
    border: 1.5px solid #e2e8f0;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
}
.apt-conflict { border-color: #fca5a5 !important; background: #fff5f5; }
.conflict-badge { background: #fef2f2; color: #b91c1c; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 100px; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 6px; }

.apt-info { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.apt-name { font-size: 14px; font-weight: 700; color: #0f172a; }
.apt-phone { color: #6b7280; font-weight: 400; font-size: 13px; margin-left: 6px; }
.apt-detail { font-size: 12px; color: #6b7280; }
.apt-note { font-size: 12px; color: #4b5563; font-style: italic; }

.apt-btns { display: flex; flex-direction: column; gap: 6px; }
.abtn-approve, .abtn-reject {
    display: flex; align-items: center; gap: 5px;
    padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
    border: none; cursor: pointer; white-space: nowrap;
}
.abtn-approve { background: #dcfce7; color: #15803d; }
.abtn-reject  { background: #fee2e2; color: #b91c1c; }

/* Table */
.apt-table-wrap { overflow-x: auto; }
.apt-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.apt-table th { background: #f0fdf4; color: #065f46; padding: 9px 12px; text-align: left; font-weight: 700; border-bottom: 2px solid #d1fae5; }
.apt-table td { padding: 10px 12px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.td-name { font-weight: 600; color: #0f172a; }
.td-phone-sm { font-size: 11px; color: #6b7280; margin-top: 2px; }
.td-time { color: #374151; white-space: nowrap; }
.row-conflict td { background: #fff9f9; }

.status-pill { padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; }
.st-pending  { background: #fef9c3; color: #854d0e; }
.st-approved { background: #dcfce7; color: #15803d; }
.st-rejected { background: #f3f4f6; color: #6b7280; }

.conflict-icon { color: #dc2626; margin-left: 6px; font-size: 12px; }

@media (max-width: 1100px) {
    .apt-cols { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .apt-summary { gap: 8px; }
    .sum-chip    { font-size: 12px; padding: 6px 12px; }
    .apt-item    { flex-direction: column; }
    .apt-btns    { flex-direction: row; width: 100%; }
    .abtn-approve, .abtn-reject { flex: 1; justify-content: center; }
    .apt-table-wrap { -webkit-overflow-scrolling: touch; }
    .apt-table      { min-width: 480px; font-size: 12px; }
}
</style>
