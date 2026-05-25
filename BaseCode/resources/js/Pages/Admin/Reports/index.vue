<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const search     = ref('')
const typeFilter = ref('all')
const statusFilter = ref('all')

const reports = ref([
    { id:1, from:'Nguyễn Văn An', fromEmail:'vanan@gmail.com', target:'Tin đăng #1023 - Phòng trọ Hoa Lư', type:'tin_ao', date:'19/05/2026', status:'pending', note:'' },
    { id:2, from:'Trần Thị Bình', fromEmail:'thibinh@gmail.com', target:'Chủ trọ: Lê Văn Cường', type:'ghosting', date:'18/05/2026', status:'resolved', note:'Đã cảnh cáo chủ trọ.' },
    { id:3, from:'Phạm Thị Dung',  fromEmail:'thidung@gmail.com', target:'Tin đăng #1456 - Studio trung tâm', type:'lua_dao', date:'17/05/2026', status:'pending', note:'' },
    { id:4, from:'Hoàng Văn Em',   fromEmail:'vanem@gmail.com',  target:'Chủ trọ: Đặng Thị Fang', type:'ghosting', date:'16/05/2026', status:'ignored', note:'' },
    { id:5, from:'Bùi Văn Giang',  fromEmail:'vangiang@gmail.com', target:'Tin đăng #2001 - Nhà nguyên căn', type:'tin_ao', date:'15/05/2026', status:'pending', note:'' },
])

const typeMap = {
    tin_ao:  { label:'Tin ảo',   class:'type-orange' },
    ghosting:{ label:'Ghosting', class:'type-red'    },
    lua_dao: { label:'Lừa đảo', class:'type-red'    },
    khac:    { label:'Khác',    class:'type-gray'   },
}
const statusMap = {
    pending:  { label:'Chờ xử lý', class:'s-orange' },
    resolved: { label:'Đã xử lý', class:'s-green'  },
    ignored:  { label:'Bỏ qua',   class:'s-gray'   },
}

const filtered = computed(() => reports.value.filter(r => {
    const q = search.value.toLowerCase()
    const mSearch = !q || r.from.toLowerCase().includes(q) || r.target.toLowerCase().includes(q)
    const mType   = typeFilter.value === 'all' || r.type === typeFilter.value
    const mStatus = statusFilter.value === 'all' || r.status === statusFilter.value
    return mSearch && mType && mStatus
}))

const showModal = ref(false)
const selected  = ref(null)
const adminNote = ref('')
const action    = ref('')

function openReport(r) { selected.value = r; adminNote.value = r.note; showModal.value = true }
function handleAction(act) {
    action.value = act
    selected.value.note   = adminNote.value
    selected.value.status = act === 'resolve' ? 'resolved' : 'ignored'
    showModal.value = false
}
</script>

<template>
    <Head title="Admin - Báo Cáo & Khiếu Nại" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Báo Cáo & Khiếu Nại</h1>
                <p class="page-sub">Tiếp nhận và xử lý vi phạm từ người dùng</p>
            </div>
        </template>

        <!-- Summary cards -->
        <div class="summary-row">
            <div class="sum-card sum-orange">
                <i class="bi bi-hourglass-split"></i>
                <div><p class="sum-num">{{ reports.filter(r=>r.status==='pending').length }}</p><p class="sum-lbl">Chờ xử lý</p></div>
            </div>
            <div class="sum-card sum-green">
                <i class="bi bi-check-circle-fill"></i>
                <div><p class="sum-num">{{ reports.filter(r=>r.status==='resolved').length }}</p><p class="sum-lbl">Đã xử lý</p></div>
            </div>
            <div class="sum-card sum-gray">
                <i class="bi bi-dash-circle-fill"></i>
                <div><p class="sum-num">{{ reports.filter(r=>r.status==='ignored').length }}</p><p class="sum-lbl">Bỏ qua</p></div>
            </div>
            <div class="sum-card sum-blue">
                <i class="bi bi-flag-fill"></i>
                <div><p class="sum-num">{{ reports.length }}</p><p class="sum-lbl">Tổng cộng</p></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-bar">
            <div class="search-wrap">
                <i class="bi bi-search si"></i>
                <input v-model="search" type="text" placeholder="Tìm người báo cáo, đối tượng..." class="search-input" />
            </div>
            <select v-model="typeFilter" class="filter-select">
                <option value="all">Tất cả loại</option>
                <option value="tin_ao">Tin ảo</option>
                <option value="ghosting">Ghosting</option>
                <option value="lua_dao">Lừa đảo</option>
            </select>
            <select v-model="statusFilter" class="filter-select">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending">Chờ xử lý</option>
                <option value="resolved">Đã xử lý</option>
                <option value="ignored">Bỏ qua</option>
            </select>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Người báo cáo</th>
                        <th>Đối tượng bị báo cáo</th>
                        <th>Loại vi phạm</th>
                        <th>Ngày</th>
                        <th>Trạng thái</th>
                        <th style="text-align:center">Xử lý</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!filtered.length"><td colspan="7" class="empty-row"><i class="bi bi-inbox"></i><p>Không có báo cáo nào</p></td></tr>
                    <tr v-for="(r, i) in filtered" :key="r.id" class="trow">
                        <td class="idx">{{ i+1 }}</td>
                        <td>
                            <p class="fw">{{ r.from }}</p>
                            <p class="sm-gray">{{ r.fromEmail }}</p>
                        </td>
                        <td class="sm-target">{{ r.target }}</td>
                        <td><span :class="['type-badge', typeMap[r.type]?.class]">{{ typeMap[r.type]?.label }}</span></td>
                        <td class="sm-gray">{{ r.date }}</td>
                        <td><span :class="['status-chip', statusMap[r.status]?.class]">{{ statusMap[r.status]?.label }}</span></td>
                        <td style="text-align:center">
                            <button @click="openReport(r)" class="act-btn" :class="r.status==='pending' ? 'act-primary' : 'act-view'">
                                <i :class="['bi', r.status==='pending' ? 'bi-clipboard2-check' : 'bi-eye']"></i>
                                {{ r.status === 'pending' ? 'Xử lý' : 'Xem' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal xử lý -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="showModal=false">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3>Xử Lý Báo Cáo #{{ selected?.id }}</h3>
                        <button @click="showModal=false" class="modal-close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="info-block">
                            <div class="ib-row"><span class="ib-l">Người BC</span><span class="ib-v">{{ selected?.from }}</span></div>
                            <div class="ib-row"><span class="ib-l">Đối tượng</span><span class="ib-v">{{ selected?.target }}</span></div>
                            <div class="ib-row"><span class="ib-l">Loại VP</span><span class="ib-v"><span :class="['type-badge',typeMap[selected?.type]?.class]">{{ typeMap[selected?.type]?.label }}</span></span></div>
                            <div class="ib-row"><span class="ib-l">Trạng thái</span><span class="ib-v"><span :class="['status-chip',statusMap[selected?.status]?.class]">{{ statusMap[selected?.status]?.label }}</span></span></div>
                        </div>
                        <label class="form-label mt-4">Ghi chú admin:</label>
                        <textarea v-model="adminNote" class="form-textarea" rows="3" placeholder="Ghi chú sau khi xử lý..."></textarea>
                    </div>
                    <div class="modal-footer" v-if="selected?.status === 'pending'">
                        <button @click="showModal=false" class="btn-cancel">Hủy</button>
                        <button @click="handleAction('ignore')" class="btn-ignore"><i class="bi bi-dash-circle"></i> Bỏ qua</button>
                        <button @click="handleAction('resolve')" class="btn-resolve"><i class="bi bi-check-circle-fill"></i> Đã xử lý</button>
                    </div>
                    <div class="modal-footer" v-else>
                        <button @click="showModal=false" class="btn-cancel" style="flex:1">Đóng</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.page-title{font-size:18px;font-weight:700;color:#0f172a;margin:0}.page-sub{font-size:12px;color:#94a3b8;margin:2px 0 0}
.summary-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
.sum-card{background:#fff;border-radius:14px;padding:16px;display:flex;align-items:center;gap:12px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.sum-card i{font-size:26px;flex-shrink:0}
.sum-orange i,.sum-orange .sum-num{color:#f97316} .sum-green i,.sum-green .sum-num{color:#22c55e}
.sum-gray i,.sum-gray .sum-num{color:#94a3b8} .sum-blue i,.sum-blue .sum-num{color:#3b82f6}
.sum-num{font-size:24px;font-weight:800;margin:0;line-height:1}.sum-lbl{font-size:11px;color:#94a3b8;margin:0}
.filter-bar{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.search-wrap{position:relative;flex:1;min-width:180px}.si{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px}
.search-input{width:100%;padding:9px 12px 9px 36px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;box-sizing:border-box}
.search-input:focus{border-color:#7c3aed}
.filter-select{padding:9px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;color:#334155;background:#fff;outline:none}
.table-card{background:#fff;border-radius:14px;border:1px solid #f1f5f9;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05)}
.data-table{width:100%;border-collapse:collapse;font-size:13px}
.data-table th{text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;padding:13px 16px;background:#f8fafc;border-bottom:1px solid #f1f5f9}
.data-table td{padding:12px 16px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.trow:last-child td{border-bottom:none}.trow:hover td{background:#fafbff}
.idx{color:#cbd5e1;font-size:12px;font-weight:600}.fw{font-weight:600;color:#0f172a;margin:0}.sm-gray{font-size:11px;color:#94a3b8;margin:0}
.sm-target{color:#334155;font-size:12px;max-width:200px}
.empty-row{text-align:center;padding:48px !important;color:#94a3b8}.empty-row i{display:block;font-size:40px;margin-bottom:8px}
.type-badge{font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px}
.type-orange{background:#fff7ed;color:#ea580c}.type-red{background:#fef2f2;color:#dc2626}.type-gray{background:#f1f5f9;color:#64748b}
.status-chip{font-size:11px;font-weight:600;padding:3px 9px;border-radius:99px}
.s-orange{background:#fff7ed;color:#ea580c}.s-green{background:#f0fdf4;color:#16a34a}.s-gray{background:#f1f5f9;color:#64748b}
.act-btn{padding:7px 12px;border-radius:9px;border:none;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
.act-primary{background:#7c3aed;color:#fff}.act-primary:hover{background:#6d28d9}
.act-view{background:#f1f5f9;color:#64748b}.act-view:hover{background:#e2e8f0}
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(3px)}
.modal-box{background:#fff;border-radius:18px;width:480px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,0.15);overflow:hidden}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #f1f5f9}
.modal-header h3{font-size:15px;font-weight:700;color:#0f172a;margin:0}
.modal-close{width:30px;height:30px;border-radius:8px;border:none;background:#f8fafc;color:#64748b;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center}
.modal-body{padding:18px 22px}
.info-block{background:#f8fafc;border-radius:12px;padding:12px 14px;display:flex;flex-direction:column;gap:8px}
.ib-row{display:flex;gap:12px;font-size:13px}.ib-l{width:90px;color:#94a3b8;font-weight:500;flex-shrink:0}.ib-v{color:#0f172a;font-weight:500}
.form-label{font-size:13px;font-weight:600;color:#0f172a;display:block;margin-bottom:6px}.mt-4{margin-top:14px}
.form-textarea{width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;resize:none;outline:none;box-sizing:border-box}
.form-textarea:focus{border-color:#7c3aed}
.modal-footer{display:flex;gap:8px;padding:14px 22px;border-top:1px solid #f1f5f9}
.btn-cancel{flex:1;padding:9px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer}
.btn-ignore{flex:1;padding:9px;border-radius:10px;border:none;background:#f1f5f9;color:#64748b;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px}
.btn-resolve{flex:2;padding:9px;border-radius:10px;border:none;background:#22c55e;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px}
.btn-resolve:hover{background:#16a34a}
</style>
