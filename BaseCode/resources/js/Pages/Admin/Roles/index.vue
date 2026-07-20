<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const roles = ref([
    { id:1, name:'Admin', color:'#ef4444', icon:'bi-shield-fill', desc:'Toàn quyền hệ thống', users:1, permissions:['all'] },
    { id:2, name:'Nhân viên', color:'#3b82f6', icon:'bi-person-badge-fill', desc:'Duyệt tin, xử lý báo cáo', users:3, permissions:['approval','reports','reviews'] },
    { id:3, name:'Chủ trọ', color:'#7c3aed', icon:'bi-house-fill', desc:'Đăng tin, quản lý phòng', users:24, permissions:['post','manage_rooms'] },
    { id:4, name:'Người thuê', color:'#22c55e', icon:'bi-person-fill', desc:'Tìm kiếm, đặt phòng, đánh giá', users:156, permissions:['search','book','review'] },
])

const allPerms = [
    { key:'all',         label:'Toàn quyền',        icon:'bi-infinity' },
    { key:'approval',    label:'Duyệt tin đăng',     icon:'bi-check-circle' },
    { key:'reports',     label:'Xử lý báo cáo',      icon:'bi-flag' },
    { key:'reviews',     label:'Quản lý đánh giá',   icon:'bi-star' },
    { key:'post',        label:'Đăng tin',            icon:'bi-plus-square' },
    { key:'manage_rooms',label:'Quản lý phòng',       icon:'bi-house-gear' },
    { key:'search',      label:'Tìm kiếm phòng',     icon:'bi-search' },
    { key:'book',        label:'Đặt phòng',           icon:'bi-calendar-check' },
    { key:'review',      label:'Đánh giá',            icon:'bi-star-half' },
]

const users = ref([
    { id:1, name:'Nguyễn Văn An',   email:'vanan@gmail.com',   role:'Người thuê' },
    { id:2, name:'Trần Thị Bình',   email:'thibinh@gmail.com', role:'Chủ trọ' },
    { id:3, name:'Lê Văn Cường',    email:'vanc@gmail.com',    role:'Nhân viên' },
    { id:4, name:'Phạm Thị Dung',   email:'thid@gmail.com',    role:'Người thuê' },
])

const selected = ref(null)
const newRole  = ref('')

function hasPermission(role, perm) {
    return role.permissions.includes('all') || role.permissions.includes(perm)
}

function togglePerm(role, perm) {
    if (perm === 'all') return
    const idx = role.permissions.indexOf(perm)
    if (idx >= 0) role.permissions.splice(idx, 1)
    else role.permissions.push(perm)
}

function assignRole(user) {
    if (newRole.value) { user.role = newRole.value; newRole.value = '' }
    selected.value = null
}
</script>

<template>
    <Head title="Admin - Phân Quyền" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Phân Quyền Người Dùng</h1>
                <p class="page-sub">Quản lý vai trò và quyền hạn trong hệ thống</p>
            </div>
        </template>

        <!-- Role cards -->
        <div class="roles-grid">
            <div v-for="role in roles" :key="role.id" class="role-card">
                <div class="role-top">
                    <div class="role-icon" :style="`background:${role.color}20;color:${role.color}`">
                        <i :class="['bi', role.icon]"></i>
                    </div>
                    <div>
                        <h4 class="role-name">{{ role.name }}</h4>
                        <p class="role-desc">{{ role.desc }}</p>
                    </div>
                    <span class="user-count">{{ role.users }} users</span>
                </div>
                <div class="perm-list">
                    <button
                        v-for="perm in allPerms" :key="perm.key"
                        :class="['perm-chip', hasPermission(role, perm.key) ? 'perm-on' : 'perm-off']"
                        @click="togglePerm(role, perm.key)"
                        :style="hasPermission(role, perm.key) ? `background:${role.color}15;color:${role.color};border-color:${role.color}40` : ''"
                        :disabled="role.permissions.includes('all')"
                    >
                        <i :class="['bi', perm.icon]"></i>
                        {{ perm.label }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Assign role to user -->
        <div class="assign-card">
            <h3 class="card-title">Gán Vai Trò Cho Người Dùng</h3>
            <table class="data-table">
                <thead><tr><th>Người dùng</th><th>Email</th><th>Vai trò hiện tại</th><th style="text-align:center">Thay đổi</th></tr></thead>
                <tbody>
                    <tr v-for="u in users" :key="u.id" class="trow">
                        <td class="fw">{{ u.name }}</td>
                        <td class="sm-gray">{{ u.email }}</td>
                        <td>
                            <span :class="['role-chip', u.role==='Admin'?'rc-red':u.role==='Nhân viên'?'rc-blue':u.role==='Chủ trọ'?'rc-purple':'rc-green']">{{ u.role }}</span>
                        </td>
                        <td style="text-align:center">
                            <div v-if="selected?.id === u.id" class="inline-assign">
                                <select v-model="newRole" class="role-select">
                                    <option value="">Chọn vai trò...</option>
                                    <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                                </select>
                                <button @click="assignRole(u)" class="btn-assign">Lưu</button>
                                <button @click="selected=null" class="btn-cancel-sm">Hủy</button>
                            </div>
                            <button v-else @click="selected=u;newRole=u.role" class="act-btn">
                                <i class="bi bi-pencil-fill"></i> Thay đổi
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

<style scoped>
.page-title{font-size:18px;font-weight:700;color:#0f172a;margin:0}.page-sub{font-size:12px;color:#94a3b8;margin:2px 0 0}
.roles-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:20px}
.role-card{background:#fff;border-radius:8px;border:1px solid #f1f5f9;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.role-top{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.role-icon{width:42px;height:42px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.role-name{font-size:14px;font-weight:700;color:#0f172a;margin:0}
.role-desc{font-size:11px;color:#94a3b8;margin:2px 0 0}
.user-count{margin-left:auto;font-size:11px;font-weight:600;background:#f1f5f9;color:#64748b;padding:3px 9px;border-radius:99px;white-space:nowrap}
.perm-list{display:flex;flex-wrap:wrap;gap:6px}
.perm-chip{padding:4px 10px;border-radius:99px;border:1px solid #e2e8f0;font-size:11px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;transition:all .15s}
.perm-on{}
.perm-off{background:#f8fafc;color:#94a3b8}
.perm-chip:disabled{cursor:default}
.assign-card{background:#fff;border-radius:8px;border:1px solid #f1f5f9;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.card-title{font-size:14px;font-weight:700;color:#0f172a;margin:0;padding:16px 16px 0}
.data-table{width:100%;border-collapse:collapse;font-size:13px;margin-top:12px}
.data-table th{text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;padding:10px 16px;background:#f8fafc;border-bottom:1px solid #f1f5f9;letter-spacing:.04em}
.data-table td{padding:11px 16px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.trow:last-child td{border-bottom:none}.trow:hover td{background:#fafbff}
.fw{font-weight:600;color:#0f172a}.sm-gray{color:#94a3b8;font-size:12px}
.role-chip{font-size:11px;font-weight:600;padding:3px 9px;border-radius:99px}
.rc-red{background:#fef2f2;color:#dc2626}.rc-blue{background:#eff6ff;color:#2563eb}
.rc-purple{background:#faf5ff;color:#7c3aed}.rc-green{background:#f0fdf4;color:#16a34a}
.inline-assign{display:flex;align-items:center;gap:6px;justify-content:center}
.role-select{padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;outline:none}
.btn-assign{padding:6px 12px;border-radius:6px;border:none;background:#7c3aed;color:#fff;font-size:12px;font-weight:600;cursor:pointer}
.btn-cancel-sm{padding:6px 10px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:12px;cursor:pointer}
.act-btn{padding:7px 12px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#7c3aed;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
.act-btn:hover{background:#faf5ff}
</style>
