<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

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
        const matchSearch = !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
        const matchRole   = roleFilter.value === 'all' || u.role === roleFilter.value
        const matchStatus = statusFilter.value === 'all' || u.status === statusFilter.value
        return matchSearch && matchRole && matchStatus
    })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage)))
const paginated  = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return filtered.value.slice(start, start + perPage)
})

const roleLabel = { user: 'Người thuê', landlord: 'Chủ trọ', staff: 'Nhân viên', admin: 'Admin' }
const roleClass = { user: 'role-blue', landlord: 'role-purple', staff: 'role-green', admin: 'role-red' }

// Action modal
const showModal    = ref(false)
const modalUser    = ref(null)
const modalAction  = ref('')

function openAction(user, action) {
    modalUser.value  = user
    modalAction.value = action
    showModal.value  = true
}

function confirmAction() {
    if (modalAction.value === 'toggle') {
        router.patch(route('admin.users.toggle-status', modalUser.value.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            }
        });
    } else if (modalAction.value === 'delete') {
        router.delete(route('admin.users.delete', modalUser.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
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
                <option value="user">Người thuê</option>
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
            <button class="btn-add">
                <i class="bi bi-person-plus-fill"></i> Thêm mới
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
                        <th style="width:120px;text-align:center">Hành động</th>
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
                                <div class="user-ava" :style="`background: hsl(${(u.id * 57) % 360}, 65%, 55%)`">
                                    {{ u.name[0] }}
                                </div>
                                <div>
                                    <p class="user-name">{{ u.name }}</p>
                                    <p class="user-email">{{ u.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span :class="['role-badge', roleClass[u.role]]">
                                {{ roleLabel[u.role] || u.role }}
                            </span>
                        </td>
                        <td class="text-gray">{{ u.created_at }}</td>
                        <td>
                            <span :class="['status-badge', u.status === 'active' ? 'badge-active' : 'badge-locked']">
                                <span class="dot"></span>
                                {{ u.status === 'active' ? 'Hoạt động' : 'Bị khóa' }}
                            </span>
                        </td>
                        <td>
                            <div class="action-btns" v-if="u.role !== 'admin'">
                                <button class="act-btn act-view" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button
                                    :class="['act-btn', u.status === 'active' ? 'act-lock' : 'act-unlock']"
                                    :title="u.status === 'active' ? 'Khóa tài khoản' : 'Mở khóa'"
                                    @click="openAction(u, 'toggle')"
                                >
                                    <i :class="['bi', u.status === 'active' ? 'bi-lock-fill' : 'bi-unlock-fill']"></i>
                                </button>
                                <button class="act-btn act-delete" title="Xóa" @click="openAction(u, 'delete')">
                                    <i class="bi bi-trash3"></i>
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

        <!-- Confirm Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="showModal=false">
                <div class="modal-box">
                    <div :class="['modal-icon', modalAction === 'delete' ? 'icon-red' : 'icon-orange']">
                        <i :class="['bi', modalAction === 'delete' ? 'bi-trash3-fill' : (modalUser?.status === 'active' ? 'bi-lock-fill' : 'bi-unlock-fill')]"></i>
                    </div>
                    <h3 class="modal-title">
                        {{ modalAction === 'delete' ? 'Xác nhận xóa' : (modalUser?.status === 'active' ? 'Khóa tài khoản?' : 'Mở khóa tài khoản?') }}
                    </h3>
                    <p class="modal-desc">
                        {{ modalAction === 'delete'
                            ? `Bạn có chắc chắn muốn xóa tài khoản "${modalUser?.name}"? Hành động này không thể hoàn tác.`
                            : `Bạn có muốn ${modalUser?.status === 'active' ? 'khóa' : 'mở khóa'} tài khoản "${modalUser?.name}"?`
                        }}
                    </p>
                    <div class="modal-actions">
                        <button @click="showModal=false" class="btn-cancel">Hủy</button>
                        <button @click="confirmAction" :class="['btn-confirm', modalAction === 'delete' ? 'btn-danger' : 'btn-warning']">
                            Xác nhận
                        </button>
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
.btn-add { padding: 9px 16px; border-radius: 6px; border: none; background: #7c3aed; color:#fff; font-size:13px; font-weight:600; cursor:pointer; display:flex;align-items:center;gap:6px; transition: background 0.15s; }
.btn-add:hover { background: #6d28d9; }

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
.act-delete { background: #fef2f2; color: #ef4444; }
.act-delete:hover { background: #ef4444; color: #fff; }

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
.icon-red    { background:#fef2f2;color:#ef4444; }
.icon-orange { background:#fff7ed;color:#f97316; }
.modal-title { font-size:17px;font-weight:700;color:#0f172a;margin:0 0 8px; }
.modal-desc  { font-size:13px;color:#64748b;margin:0 0 24px;line-height:1.5; }
.modal-actions { display:flex;gap:10px; }
.btn-cancel  { flex:1;padding:10px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer; }
.btn-confirm { flex:1;padding:10px;border-radius:6px;border:none;color:#fff;font-size:13px;font-weight:600;cursor:pointer; }
.btn-danger  { background:#ef4444; }
.btn-danger:hover { background:#dc2626; }
.btn-warning { background:#f97316; }
.btn-warning:hover { background:#ea580c; }
</style>
