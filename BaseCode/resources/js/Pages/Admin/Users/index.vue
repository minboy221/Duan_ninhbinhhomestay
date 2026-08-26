<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Swal from 'sweetalert2'
import { getAvatarUrl, DEFAULT_AVATAR } from '@/Utils/media'

const props = defineProps({
    users: { type: Array, default: () => [] }
})

const search    = ref('')
const roleFilter   = ref('all')
const statusFilter = ref('all')
const currentPage  = ref(1)
const perPage = 10

const filtered = computed(() => {
    return props.users.filter(u => {
        const q = search.value.toLowerCase()
        const matchSearch = !q || (u.name && u.name.toLowerCase().includes(q)) || (u.email && u.email.toLowerCase().includes(q))
        const matchRole   = roleFilter.value === 'all' || u.role === roleFilter.value || (roleFilter.value === 'tenant' && u.role === 'user')
        const matchStatus = statusFilter.value === 'all' || (u.status || 'active') === statusFilter.value
        return matchSearch && matchRole && matchStatus
    })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage)))
const paginated  = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return filtered.value.slice(start, start + perPage)
})

const roleLabel = { tenant: 'Người thuê', user: 'Người thuê', landlord: 'Chủ trọ', staff: 'Nhân viên', admin: 'Admin' }
const roleClass = { tenant: 'role-blue', user: 'role-blue', landlord: 'role-purple', staff: 'role-green', admin: 'role-red' }

function formatDate(dateStr) {
    if (!dateStr) return 'N/A'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    const hours = String(d.getHours()).padStart(2, '0')
    const mins = String(d.getMinutes()).padStart(2, '0')
    return `${day}/${month}/${year} ${hours}:${mins}`
}

// Action modal
const showModal    = ref(false)
const modalUser    = ref(null)
const modalAction  = ref('')
const lockReason   = ref('')
const lockReasonError = ref('')

function openAction(user, action) {
    modalUser.value  = user
    modalAction.value = action
    lockReason.value = ''
    lockReasonError.value = ''
    showModal.value  = true
}

function confirmAction() {
    if (modalAction.value === 'toggle') {
        const isLocking = (modalUser.value.status || 'active') === 'active';
        
        if (isLocking && !lockReason.value.trim()) {
            lockReasonError.value = 'Vui lòng nhập lý do khóa tài khoản!';
            return;
        }

        router.patch(route('admin.users.toggle-status', modalUser.value.id), {
            reason: lockReason.value.trim()
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công',
                    text: isLocking ? 'Đã khóa tài khoản thành công.' : 'Đã mở khóa tài khoản thành công.',
                    confirmButtonText: 'Đóng'
                });
            }
        });
    }
}

function resetFilters() {
    search.value = ''; roleFilter.value = 'all'; statusFilter.value = 'all'; currentPage.value = 1
}
</script>

<template>
    <Head title="Admin - Quản Lý Người Dùng" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Quản Lý Tài Khoản Người Dùng</h1>
                <p class="page-sub">Tổng cộng {{ filtered.length }} người dùng</p>
            </div>
        </template>

        <!-- Filter bar -->
        <div class="filter-bar">
            <div class="search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input v-model="search" @input="currentPage=1" type="text" placeholder="Tìm theo tên, email..." class="search-input" />
            </div>
            <select v-model="roleFilter" @change="currentPage=1" class="filter-select">
                <option value="all">Tất cả loại TK</option>
                <option value="tenant">Người thuê</option>
                <option value="landlord">Chủ trọ</option>
                <option value="staff">Nhân viên</option>
                <option value="admin">Admin</option>
            </select>
            <select v-model="statusFilter" @change="currentPage=1" class="filter-select">
                <option value="all">Tất cả trạng thái</option>
                <option value="active">Hoạt động</option>
                <option value="locked">Bị khóa</option>
            </select>
            <button @click="resetFilters" class="btn-reset">
                <i class="bi bi-x-circle"></i> Reset
            </button>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Người dùng</th>
                        <th>Loại tài khoản</th>
                        <th>Ngày đăng ký</th>
                        <th>Trạng thái</th>
                        <th style="width:100px;text-align:center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="paginated.length === 0">
                        <td colspan="6" class="empty-row">
                            <i class="bi bi-inbox text-3xl text-gray-300"></i>
                            <p>Không tìm thấy người dùng nào</p>
                        </td>
                    </tr>
                    <tr v-for="(u, i) in paginated" :key="u.id" class="table-row">
                        <td class="idx">{{ (currentPage - 1) * perPage + i + 1 }}</td>
                        <td>
                            <div class="user-cell">
                                <div class="user-ava" :style="u.avatar ? 'overflow: hidden; background: #f1f5f9; padding: 0;' : `background: hsl(${(u.id * 57) % 360}, 65%, 55%)`">
                                    <img v-if="u.avatar"
                                        :src="getAvatarUrl(u.avatar)" 
                                        @error="$event.target.onerror = null; $event.target.src = DEFAULT_AVATAR"
                                        :alt="u.name"
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                    />
                                    <template v-else>
                                        {{ u.name ? u.name[0] : 'U' }}
                                    </template>
                                </div>
                                <div>
                                    <p class="user-name">{{ u.name }}</p>
                                    <p class="user-email">{{ u.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span :class="['role-badge', roleClass[u.role] || 'role-blue']">
                                {{ roleLabel[u.role] || u.role }}
                            </span>
                        </td>
                        <td class="text-gray">{{ formatDate(u.created_at) }}</td>
                        <td>
                            <span :class="['status-badge', u.status === 'locked' ? 'badge-locked' : 'badge-active']">
                                <span class="dot"></span>
                                {{ u.status === 'locked' ? 'Bị khóa' : 'Hoạt động' }}
                            </span>
                        </td>
                        <td>
                            <div class="action-btns" v-if="u.role !== 'admin'">
                                <button class="act-btn act-view" title="Xem chi tiết" @click="openAction(u, 'view')">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button
                                    :class="['act-btn', u.status === 'locked' ? 'act-unlock' : 'act-lock']"
                                    :title="u.status === 'locked' ? 'Mở khóa tài khoản' : 'Khóa tài khoản'"
                                    @click="openAction(u, 'toggle')"
                                >
                                    <i :class="['bi', u.status === 'locked' ? 'bi-unlock-fill' : 'bi-lock-fill']"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination" v-if="totalPages > 1">
                <button :disabled="currentPage === 1" @click="currentPage--" class="page-btn">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button
                    v-for="p in totalPages" :key="p"
                    @click="currentPage = p"
                    :class="['page-btn', currentPage === p ? 'page-active' : '']"
                >{{ p }}</button>
                <button :disabled="currentPage === totalPages" @click="currentPage++" class="page-btn">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Confirm / View Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="showModal=false">
                <!-- Toggle status modal -->
                <div v-if="modalAction === 'toggle'" class="modal-box">
                    <div class="modal-icon icon-orange">
                        <i :class="['bi', (modalUser?.status || 'active') === 'active' ? 'bi-lock-fill' : 'bi-unlock-fill']"></i>
                    </div>
                    <h3 class="modal-title">
                        {{ (modalUser?.status || 'active') === 'active' ? 'Khóa tài khoản?' : 'Mở khóa tài khoản?' }}
                    </h3>
                    <p class="modal-desc">
                        {{ `Bạn có muốn ${(modalUser?.status || 'active') === 'active' ? 'khóa' : 'mở khóa'} tài khoản "${modalUser?.name}"?` }}
                    </p>

                    <div v-if="(modalUser?.status || 'active') === 'active'" style="text-align: left; margin: 15px 0;">
                        <label style="font-size: 13px; font-weight: 600; color: #334155; display: block; margin-bottom: 6px;">
                            Lý do khóa tài khoản <span style="color: #ef4444;">*</span>:
                        </label>
                        <textarea
                            v-model="lockReason"
                            @input="lockReasonError = ''"
                            rows="3"
                            placeholder="Nhập chi tiết lý do khóa tài khoản (ví dụ: Vi phạm điều khoản, giả mạo thông tin, spam...)"
                            style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; font-size: 13px; outline: none; resize: vertical;"
                        ></textarea>
                        <p v-if="lockReasonError" style="color: #ef4444; font-size: 12px; margin: 4px 0 0 0; font-weight: 500;">
                            {{ lockReasonError }}
                        </p>
                    </div>

                    <div class="modal-actions">
                        <button @click="showModal=false" class="btn-cancel">Hủy</button>
                        <button @click="confirmAction" class="btn-confirm btn-warning">
                            Xác nhận
                        </button>
                    </div>
                </div>

                <!-- User Detail Modal -->
                <div v-else-if="modalAction === 'view'" class="modal-box">
                    <div class="user-detail-ava" :style="modalUser?.avatar ? 'overflow: hidden; background: #f1f5f9;' : `background: hsl(${((modalUser?.id || 1) * 57) % 360}, 65%, 55%)`">
                        <img v-if="modalUser?.avatar" :src="getAvatarUrl(modalUser.avatar)" @error="$event.target.onerror = null; $event.target.src = DEFAULT_AVATAR" :alt="modalUser?.name" style="width:100%;height:100%;object-fit:cover;" />
                        <template v-else>{{ modalUser?.name ? modalUser.name[0] : 'U' }}</template>
                    </div>
                    <h3 class="modal-title" style="margin-top:10px;">{{ modalUser?.name }}</h3>
                    <p class="modal-desc" style="margin-bottom:16px;">{{ modalUser?.email }}</p>

                    <div class="detail-list">
                        <div class="detail-row">
                            <span class="detail-lbl">Loại tài khoản:</span>
                            <span :class="['role-badge', roleClass[modalUser?.role] || 'role-blue']">{{ roleLabel[modalUser?.role] || modalUser?.role }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-lbl">Trạng thái:</span>
                            <span :class="['status-badge', modalUser?.status === 'locked' ? 'badge-locked' : 'badge-active']">
                                <span class="dot"></span>
                                {{ modalUser?.status === 'locked' ? 'Bị khóa' : 'Hoạt động' }}
                            </span>
                        </div>
                        <div v-if="modalUser?.status === 'locked'" class="detail-row" style="align-items: flex-start;">
                            <span class="detail-lbl">Lý do khóa:</span>
                            <span class="detail-val" style="color: #ef4444; font-weight: 600;">
                                {{ modalUser?.lock_reason || 'Không ghi rõ lý do' }}
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-lbl">Số điện thoại:</span>
                            <span class="detail-val">{{ modalUser?.phone || 'Chưa cập nhật' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-lbl">Ngày đăng ký:</span>
                            <span class="detail-val">{{ formatDate(modalUser?.created_at) }}</span>
                        </div>
                    </div>

                    <div class="modal-actions" style="margin-top:20px;">
                        <button @click="showModal=false" class="btn-cancel" style="width:100%;">Đóng</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.page-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }
.page-sub   { font-size: 12px; color: #94a3b8; margin: 2px 0 0; }

/* Filter */
.filter-bar { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.search-wrap { position: relative; flex: 1; min-width: 200px; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; }
.search-input { width: 100%; padding: 9px 12px 9px 36px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; color: #0f172a; background:#fff; outline:none; transition: border 0.15s; box-sizing: border-box; }
.search-input:focus { border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.08); }
.filter-select { padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; color: #334155; background:#fff; outline:none; cursor:pointer; }
.filter-select:focus { border-color: #7c3aed; }
.btn-reset { padding: 9px 14px; border-radius: 6px; border: 1px solid #e2e8f0; background:#fff; color:#64748b; font-size:13px; cursor:pointer; display:flex;align-items:center;gap:5px; }
.btn-reset:hover { background: #f8fafc; }

/* Table card */
.table-card { background: #fff; border-radius: 8px; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.05); overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th { text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; }
.data-table td { padding: 13px 16px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.table-row:last-child td { border-bottom: none; }
.table-row:hover td { background: #fafbff; }
.idx { color: #cbd5e1; font-weight: 600; font-size: 12px; }

.empty-row { text-align: center; padding: 48px !important; color: #94a3b8; }
.empty-row i { display: block; font-size: 40px; margin-bottom: 8px; }

.user-cell  { display: flex; align-items: center; gap: 10px; }
.user-ava   { width: 34px; height: 34px; border-radius: 6px; display:flex;align-items:center;justify-content:center; color:#fff; font-size:14px; font-weight:700; flex-shrink:0; }
.user-name  { font-size: 13px; font-weight: 600; color: #0f172a; margin: 0; }
.user-email { font-size: 11px; color: #94a3b8; margin: 0; }
.text-gray  { color: #64748b; }

.role-badge { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 99px; }
.role-blue   { background: #eff6ff; color: #2563eb; }
.role-purple { background: #faf5ff; color: #7c3aed; }
.role-green  { background: #f0fdf4; color: #16a34a; }
.role-red    { background: #fef2f2; color: #dc2626; }

.status-badge { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600; padding:3px 9px; border-radius:99px; }
.dot { width:6px; height:6px; border-radius:50%; display:inline-block; }
.badge-active .dot  { background: #22c55e; }
.badge-locked .dot  { background: #ef4444; }
.badge-active  { background: #f0fdf4; color: #16a34a; }
.badge-locked  { background: #fef2f2; color: #dc2626; }

.action-btns { display: flex; align-items: center; justify-content: center; gap: 6px; }
.act-btn { width: 30px; height: 30px; border-radius: 6px; border: none; cursor: pointer; display:flex;align-items:center;justify-content:center; font-size: 14px; transition: all 0.15s; }
.act-view   { background: #eff6ff; color: #3b82f6; }
.act-view:hover   { background: #3b82f6; color: #fff; }
.act-lock   { background: #fff7ed; color: #f97316; }
.act-lock:hover   { background: #f97316; color: #fff; }
.act-unlock { background: #f0fdf4; color: #22c55e; }
.act-unlock:hover { background: #22c55e; color: #fff; }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 16px; border-top: 1px solid #f1f5f9; }
.page-btn { min-width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-size: 13px; cursor: pointer; display:flex;align-items:center;justify-content:center; transition: all 0.15s; }
.page-btn:hover:not(:disabled) { border-color: #7c3aed; color: #7c3aed; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-active { background: #7c3aed; border-color: #7c3aed; color: #fff; font-weight: 700; }

/* Modal */
.modal-overlay { position:fixed;inset:0;background:rgba(15,23,42,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(2px); }
.modal-box { background:#fff;border-radius:10px;padding:32px;width:380px;max-width:90vw;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.15); }
.modal-icon { width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 16px; }
.icon-orange { background:#fff7ed;color:#f97316; }
.modal-title { font-size:17px;font-weight:700;color:#0f172a;margin:0 0 8px; }
.modal-desc  { font-size:13px;color:#64748b;margin:0 0 24px;line-height:1.5; }
.modal-actions { display:flex;gap:10px; }
.btn-cancel  { flex:1;padding:10px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer; }
.btn-confirm { flex:1;padding:10px;border-radius:6px;border:none;color:#fff;font-size:13px;font-weight:600;cursor:pointer; }
.btn-warning { background:#f97316; }
.btn-warning:hover { background:#ea580c; }

/* Detail modal styles */
.user-detail-ava { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; font-weight: 700; margin: 0 auto; }
.detail-list { display: flex; flex-direction: column; gap: 12px; text-align: left; background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #f1f5f9; }
.detail-row { display: flex; align-items: center; justify-content: space-between; font-size: 13px; }
.detail-lbl { color: #64748b; font-weight: 500; }
.detail-val { color: #0f172a; font-weight: 600; }
</style>
