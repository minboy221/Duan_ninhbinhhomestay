<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const search     = ref('')
const typeFilter = ref('all')
const dateFilter = ref('')

const logs = ref([
    { id:1, user:'Admin System',    ip:'192.168.1.1',   action:'login',         target:'Đăng nhập hệ thống',           at:'19/05/2026 08:00', sensitive:false },
    { id:2, user:'Lê Văn Cường',   ip:'192.168.1.10',  action:'view_cccd',     target:'Xem CCCD của Trần Văn Hùng',   at:'19/05/2026 09:15', sensitive:true  },
    { id:3, user:'Admin System',   ip:'192.168.1.1',   action:'approve_post',  target:'Duyệt tin đăng #1023',         at:'19/05/2026 10:30', sensitive:false },
    { id:4, user:'Lê Văn Cường',   ip:'192.168.1.10',  action:'edit_contract', target:'Sửa hợp đồng #HĐ-2026-001',   at:'19/05/2026 11:00', sensitive:true  },
    { id:5, user:'Admin System',   ip:'127.0.0.1',     action:'lock_user',     target:'Khóa TK: nguyenvana@gmail.com', at:'18/05/2026 14:22', sensitive:false },
    { id:6, user:'Đặng Thị Fang',  ip:'10.0.0.5',      action:'view_cccd',     target:'Xem CCCD của Phạm Thị Mai',    at:'18/05/2026 15:10', sensitive:true  },
    { id:7, user:'Admin System',   ip:'192.168.1.1',   action:'delete_review', target:'Xóa đánh giá #RV-045',         at:'17/05/2026 09:00', sensitive:false },
    { id:8, user:'Lê Văn Cường',   ip:'192.168.1.10',  action:'resolve_report',target:'Xử lý báo cáo #BC-012',        at:'17/05/2026 11:30', sensitive:false },
])

const actionMap = {
    login:          { label:'Đăng nhập',       icon:'bi-box-arrow-in-right', color:'#3b82f6'  },
    view_cccd:      { label:'Xem CCCD',         icon:'bi-card-image',          color:'#f97316'  },
    approve_post:   { label:'Duyệt tin',        icon:'bi-check-circle',        color:'#22c55e'  },
    edit_contract:  { label:'Sửa hợp đồng',     icon:'bi-pencil-square',       color:'#ef4444'  },
    lock_user:      { label:'Khóa tài khoản',   icon:'bi-lock-fill',           color:'#f97316'  },
    delete_review:  { label:'Xóa đánh giá',     icon:'bi-trash3',              color:'#ef4444'  },
    resolve_report: { label:'Xử lý báo cáo',    icon:'bi-flag-fill',           color:'#7c3aed'  },
}

const filtered = computed(() => logs.value.filter(l => {
    const q = search.value.toLowerCase()
    const mS = !q || l.user.toLowerCase().includes(q) || l.target.toLowerCase().includes(q) || l.ip.includes(q)
    const mT = typeFilter.value === 'all' || (typeFilter.value === 'sensitive' ? l.sensitive : l.action === typeFilter.value)
    return mS && mT
}))
</script>

<template>
    <Head title="Admin - Audit Log" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Audit Log — Giám Sát Bảo Mật</h1>
                <p class="page-sub">Nhật ký toàn bộ hành động nhạy cảm trong hệ thống</p>
            </div>
        </template>

        <!-- Alert nhạy cảm -->
        <div class="alert-banner">
            <i class="bi bi-shield-exclamation"></i>
            <span>Phát hiện <strong>{{ logs.filter(l=>l.sensitive).length }} hành động nhạy cảm</strong> trong 7 ngày qua. Hãy kiểm tra kỹ!</span>
        </div>

        <!-- Filters -->
        <div class="filter-bar">
            <div class="search-wrap">
                <i class="bi bi-search si"></i>
                <input v-model="search" type="text" placeholder="Tìm theo user, IP, hành động..." class="search-input" />
            </div>
            <select v-model="typeFilter" class="filter-select">
                <option value="all">Tất cả hành động</option>
                <option value="sensitive">⚠️ Nhạy cảm</option>
                <option value="view_cccd">Xem CCCD</option>
                <option value="edit_contract">Sửa hợp đồng</option>
                <option value="login">Đăng nhập</option>
                <option value="lock_user">Khóa tài khoản</option>
            </select>
            <button class="export-btn"><i class="bi bi-download"></i> Xuất Log</button>
        </div>

        <!-- Log table -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Người thực hiện</th>
                        <th>IP</th>
                        <th>Hành động</th>
                        <th>Đối tượng</th>
                        <th>Thời gian</th>
                        <th style="text-align:center">Mức độ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!filtered.length"><td colspan="7" class="empty-row"><i class="bi bi-inbox"></i><p>Không có log nào</p></td></tr>
                    <tr v-for="(log, i) in filtered" :key="log.id" :class="['trow', log.sensitive ? 'row-sensitive' : '']">
                        <td class="idx">{{ i+1 }}</td>
                        <td>
                            <div class="user-cell">
                                <div class="user-dot" :style="`background:${actionMap[log.action]?.color||'#94a3b8'}20`">
                                    <i :class="['bi', actionMap[log.action]?.icon||'bi-activity']" :style="`color:${actionMap[log.action]?.color||'#94a3b8'}`"></i>
                                </div>
                                <span class="fw">{{ log.user }}</span>
                            </div>
                        </td>
                        <td><code class="ip-code">{{ log.ip }}</code></td>
                        <td>
                            <span class="action-badge" :style="`background:${actionMap[log.action]?.color||'#94a3b8'}15;color:${actionMap[log.action]?.color||'#94a3b8'}`">
                                {{ actionMap[log.action]?.label || log.action }}
                            </span>
                        </td>
                        <td class="target-cell">{{ log.target }}</td>
                        <td class="sm-gray">{{ log.at }}</td>
                        <td style="text-align:center">
                            <span v-if="log.sensitive" class="sensitive-chip">
                                <i class="bi bi-exclamation-triangle-fill"></i> Nhạy cảm
                            </span>
                            <span v-else class="normal-chip">Bình thường</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

<style scoped>
.page-title{font-size:18px;font-weight:700;color:#0f172a;margin:0}.page-sub{font-size:12px;color:#94a3b8;margin:2px 0 0}
.alert-banner{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;display:flex;align-items:center;gap:10px;font-size:13px;color:#92400e;margin-bottom:16px}
.alert-banner i{font-size:18px;color:#f59e0b;flex-shrink:0}
.filter-bar{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;align-items:center}
.search-wrap{position:relative;flex:1;min-width:180px}.si{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px}
.search-input{width:100%;padding:9px 12px 9px 36px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;outline:none;box-sizing:border-box}
.search-input:focus{border-color:#7c3aed}
.filter-select{padding:9px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;color:#334155;background:#fff;outline:none}
.export-btn{padding:9px 14px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;white-space:nowrap}
.table-card{background:#fff;border-radius:8px;border:1px solid #f1f5f9;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.data-table{width:100%;border-collapse:collapse;font-size:13px}
.data-table th{text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;padding:13px 16px;background:#f8fafc;border-bottom:1px solid #f1f5f9;letter-spacing:.04em}
.data-table td{padding:11px 16px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.trow:last-child td{border-bottom:none}.trow:hover td{background:#fafbff}
.row-sensitive td{background:#fffbeb !important}.row-sensitive:hover td{background:#fef3c7 !important}
.idx{color:#cbd5e1;font-size:12px;font-weight:600}
.user-cell{display:flex;align-items:center;gap:8px}
.user-dot{width:30px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.fw{font-weight:600;color:#0f172a}
.ip-code{font-family:monospace;font-size:12px;background:#f1f5f9;padding:2px 7px;border-radius:4px;color:#475569}
.action-badge{font-size:11px;font-weight:600;padding:3px 9px;border-radius:99px}
.target-cell{font-size:12px;color:#334155;max-width:220px}
.sm-gray{color:#94a3b8;font-size:12px}
.sensitive-chip{font-size:11px;font-weight:700;background:#fef3c7;color:#92400e;padding:3px 9px;border-radius:99px;display:inline-flex;align-items:center;gap:4px}
.normal-chip{font-size:11px;font-weight:600;background:#f1f5f9;color:#64748b;padding:3px 9px;border-radius:99px}
.empty-row{text-align:center;padding:48px !important;color:#94a3b8}.empty-row i{display:block;font-size:40px;margin-bottom:8px}
</style>
