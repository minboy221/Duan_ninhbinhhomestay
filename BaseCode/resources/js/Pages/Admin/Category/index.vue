<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { showConfirm } from '@/Utils/swal'

// Props nhận dữ liệu từ CategoryController (Server → Inertia)
const props = defineProps({
    categories: { type: Array, default: () => [] },
    areas:      { type: Array, default: () => [] },
    amenities:  { type: Array, default: () => [] },
})

const activeTab = ref('types')

const tabs = [
    { key: 'types',      label: 'Loại Phòng' },
    { key: 'areas',      label: 'Khu Vực' },
    { key: 'amenities',  label: 'Tiện Ích' },
]

// Map tab key → props data từ server
const data = computed(() => ({
    types:     props.categories,
    areas:     props.areas,
    amenities: props.amenities,
}))

// Map tab key → route prefix để gọi API đúng endpoint
const routeMap = {
    types:     'admin.categories.types',
    areas:     'admin.categories.areas',
    amenities: 'admin.categories.amenities',
}

const showForm = ref(false)
const editItem = ref(null)
const formData = ref({ name: '', icon: '' })
const isSubmitting = ref(false)

// Flash message
const flashMsg = ref('')
const flashType = ref('success')

function showFlash(msg, type = 'success') {
    flashMsg.value = msg
    flashType.value = type
    setTimeout(() => { flashMsg.value = '' }, 3000)
}

// Icon mặc định theo từng tab
const defaultIcons = {
    types:     'bi-door-closed',
    areas:     'bi-geo-alt',
    amenities: 'bi-star',
}

function openAdd() {
    editItem.value = null
    formData.value = { name: '', icon: defaultIcons[activeTab.value], map_embed: '' }
    showForm.value = true
}

function openEdit(item) {
    editItem.value = item
    formData.value = { name: item.name, icon: item.icon, map_embed: item.map_embed || '' }
    showForm.value = true
}

function saveItem() {
    if (!formData.value.name.trim() || isSubmitting.value) return

    isSubmitting.value = true
    const prefix = routeMap[activeTab.value]

    if (editItem.value) {
        // Cập nhật: PUT request → Controller.update → Service.update → Repository.update
        router.put(route(`${prefix}.update`, editItem.value.id), formData.value, {
            preserveScroll: true,
            onSuccess: () => {
                showForm.value = false
                showFlash('Cập nhật thành công!')
            },
            onFinish: () => { isSubmitting.value = false },
        })
    } else {
        // Thêm mới: POST request → Controller.store → Service.create → Repository.create
        router.post(route(`${prefix}.store`), formData.value, {
            preserveScroll: true,
            onSuccess: () => {
                showForm.value = false
                showFlash('Thêm mới thành công!')
            },
            onFinish: () => { isSubmitting.value = false },
        })
    }
}

function toggleActive(item) {
    // Toggle: PATCH request → Controller.toggle → Service.toggle → Repository.update
    const prefix = routeMap[activeTab.value]
    router.patch(route(`${prefix}.toggle`, item.id), {}, {
        preserveScroll: true,
        onSuccess: () => showFlash('Cập nhật trạng thái thành công!'),
    })
}

async function deleteItem(item) {
    const confirmed = await showConfirm(
        "Xác nhận xóa",
        `Bạn có chắc chắn muốn xóa "${item.name}"?`,
        "Xóa",
        "Hủy"
    );
    if (!confirmed) return;

    // Xóa: DELETE request → Controller.delete → Service.delete → Repository.delete
    const prefix = routeMap[activeTab.value]
    router.delete(route(`${prefix}.delete`, item.id), {
        preserveScroll: true,
        onSuccess: () => showFlash('Xóa thành công!'),
    })
}
</script>

<template>
    <Head title="Admin - Quản Lý Danh Mục" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Quản Lý Danh Mục</h1>
                <p class="page-sub">Thêm/Sửa/Xóa loại phòng, khu vực và tiện ích</p>
            </div>
        </template>

        <!-- Flash Message -->
        <Transition name="flash">
            <div v-if="flashMsg" :class="['flash-msg', flashType === 'success' ? 'flash-success' : 'flash-error']">
                <i :class="['bi', flashType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill']"></i>
                {{ flashMsg }}
            </div>
        </Transition>

        <div class="cat-layout">
            <!-- Tabs sidebar -->
            <div class="cat-sidebar">
                <button
                    v-for="tab in tabs" :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="['cat-tab', activeTab === tab.key ? 'cat-tab-active' : '']"
                >
                    <i :class="['bi', tab.key === 'types' ? 'bi-tags-fill' : tab.key === 'areas' ? 'bi-geo-alt-fill' : 'bi-stars']"></i>
                    {{ tab.label }}
                    <span class="cat-tab-count">{{ data[tab.key].length }}</span>
                </button>
            </div>

            <!-- Content -->
            <div class="cat-content">
                <div class="cat-header">
                    <h3 class="cat-list-title">
                        {{ tabs.find(t => t.key === activeTab)?.label }}
                        <span class="cat-badge">{{ data[activeTab].length }} mục</span>
                    </h3>
                    <button @click="openAdd" class="btn-add">
                        <i class="bi bi-plus-lg"></i> Thêm mới
                    </button>
                </div>

                <!-- Empty state -->
                <div v-if="data[activeTab].length === 0" class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Chưa có dữ liệu nào</p>
                    <button @click="openAdd" class="btn-add-empty">
                        <i class="bi bi-plus-lg"></i> Thêm mục đầu tiên
                    </button>
                </div>

                <div v-else class="cat-grid">
                    <div v-for="item in data[activeTab]" :key="item.id" class="cat-card" :class="!item.is_active ? 'cat-inactive' : ''">
                        <div class="cat-icon-wrap">
                            <i :class="['bi', item.icon]"></i>
                        </div>
                        <div class="cat-info">
                            <p class="cat-name">{{ item.name }}</p>
                            <p class="cat-status" :class="item.is_active ? 'status-on' : 'status-off'">
                                <i :class="['bi', item.is_active ? 'bi-check-circle' : 'bi-x-circle']"></i>
                                {{ item.is_active ? 'Đang hoạt động' : 'Đã ẩn' }}
                            </p>
                            <p v-if="activeTab === 'areas'" :class="item.map_embed ? 'cat-map-badge' : 'cat-map-badge-missing'">
                                <i class="bi bi-map"></i> {{ item.map_embed ? 'Có bản đồ' : 'Chưa có bản đồ' }}
                            </p>
                        </div>
                        <div class="cat-actions">
                            <button @click="toggleActive(item)" :class="['toggle-btn', item.is_active ? 'toggle-on' : 'toggle-off']" :title="item.is_active ? 'Ẩn' : 'Hiện'">
                                <i :class="['bi', item.is_active ? 'bi-eye-fill' : 'bi-eye-slash']"></i>
                            </button>
                            <button @click="openEdit(item)" class="edit-btn"><i class="bi bi-pencil-fill"></i></button>
                            <button @click="deleteItem(item)" class="del-btn"><i class="bi bi-trash3-fill"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <Teleport to="body">
            <div v-if="showForm" class="modal-overlay" @click.self="showForm=false">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3>{{ editItem ? 'Chỉnh Sửa' : 'Thêm Mới' }} {{ tabs.find(t => t.key === activeTab)?.label }}</h3>
                        <button @click="showForm=false" class="modal-close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Tên danh mục <span class="required">*</span></label>
                            <input v-model="formData.name" @keyup.enter="saveItem" type="text" class="form-input" placeholder="Nhập tên..." autofocus />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Icon (Bootstrap Icons class)</label>
                            <div class="icon-input-wrap">
                                <div class="icon-preview">
                                    <i :class="['bi', formData.icon || 'bi-tag']"></i>
                                </div>
                                <input v-model="formData.icon" type="text" class="form-input" placeholder="VD: bi-wifi, bi-house..." />
                            </div>
                        </div>
                        <!-- Map Embed chỉ hiển thị khi tab là Khu Vực -->
                        <div v-if="activeTab === 'areas'" class="form-group">
                            <label class="form-label">
                                <i class="bi bi-map"></i> Mã nhúng Google Maps
                            </label>
                            <p class="form-hint">Vào Google Maps → Chọn địa điểm → Chia sẻ → Nhúng bản đồ → Copy mã iframe</p>
                            <textarea
                                v-model="formData.map_embed"
                                class="form-textarea"
                                rows="4"
                                placeholder='Dán mã iframe từ Google Maps vào đây... VD: <iframe src="https://www.google.com/maps/embed?..."></iframe>'
                            ></textarea>
                            <div v-if="formData.map_embed" class="map-preview">
                                <p class="map-preview-label"><i class="bi bi-eye"></i> Xem trước:</p>
                                <div class="map-preview-wrap" v-html="formData.map_embed"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button @click="showForm=false" class="btn-cancel" :disabled="isSubmitting">Hủy</button>
                        <button @click="saveItem" class="btn-save" :disabled="isSubmitting || !formData.name.trim()">
                            <i class="bi" :class="isSubmitting ? 'bi-arrow-repeat spin' : 'bi-check-lg'"></i>
                            {{ isSubmitting ? 'Đang lưu...' : 'Lưu' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.page-title { font-size:18px;font-weight:700;color:#0f172a;margin:0; }
.page-sub   { font-size:12px;color:#94a3b8;margin:2px 0 0; }

/* Flash Message */
.flash-msg { display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:16px;animation:slideDown 0.3s ease; }
.flash-success { background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0; }
.flash-error { background:#fef2f2;color:#dc2626;border:1px solid #fecaca; }
.flash-enter-active, .flash-leave-active { transition:all 0.3s ease; }
.flash-enter-from, .flash-leave-to { opacity:0;transform:translateY(-10px); }
@keyframes slideDown { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }

.cat-layout { display:flex;gap:20px;align-items:flex-start; }

.cat-sidebar { width:200px;flex-shrink:0;background:#fff;border-radius:8px;border:1px solid #f1f5f9;padding:8px;box-shadow:0 1px 3px rgba(0,0,0,0.05); }
.cat-tab { width:100%;display:flex;align-items:center;gap:8px;padding:11px 12px;border-radius:6px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:600;color:#64748b;text-align:left;transition:all 0.15s; }
.cat-tab:hover { background:#f8fafc; }
.cat-tab-active { background:#7c3aed;color:#fff; }
.cat-tab-active:hover { background:#6d28d9; }
.cat-tab i { font-size:15px; }
.cat-tab-count { margin-left:auto;min-width:20px;height:20px;border-radius:99px;background:rgba(0,0,0,0.08);font-size:11px;display:flex;align-items:center;justify-content:center;padding:0 5px; }
.cat-tab-active .cat-tab-count { background:rgba(255,255,255,0.2); }

.cat-content { flex:1; }
.cat-header  { display:flex;align-items:center;justify-content:space-between;margin-bottom:16px; }
.cat-list-title { font-size:16px;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:8px; }
.cat-badge { font-size:11px;background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:99px;font-weight:600; }
.btn-add { padding:9px 16px;border-radius:6px;border:none;background:#7c3aed;color:#fff;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all 0.15s; }
.btn-add:hover { background:#6d28d9;transform:translateY(-1px); }

/* Empty state */
.empty-state { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;background:#fff;border-radius:8px;border:1px dashed #e2e8f0; }
.empty-state i { font-size:48px;color:#cbd5e1;margin-bottom:12px; }
.empty-state p { font-size:14px;color:#94a3b8;margin:0 0 16px; }
.btn-add-empty { padding:9px 20px;border-radius:6px;border:none;background:#7c3aed;color:#fff;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px; }
.btn-add-empty:hover { background:#6d28d9; }

.cat-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px; }
.cat-card { background:#fff;border-radius:8px;border:1px solid #f1f5f9;padding:16px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 3px rgba(0,0,0,0.04);transition:all 0.2s; }
.cat-card:hover { box-shadow:0 4px 12px rgba(0,0,0,0.08);transform:translateY(-1px); }
.cat-inactive { opacity:0.5; }
.cat-icon-wrap { width:42px;height:42px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
.cat-info { flex:1;min-width:0; }
.cat-name  { font-size:13px;font-weight:700;color:#0f172a;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.cat-status { font-size:11px;margin:4px 0 0;display:flex;align-items:center;gap:4px; }
.status-on  { color:#16a34a; }
.status-off { color:#94a3b8; }
.cat-actions { display:flex;flex-direction:column;gap:4px; }
.toggle-btn,.edit-btn,.del-btn { width:26px;height:26px;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;transition:all 0.15s; }
.toggle-on  { background:#f0fdf4;color:#16a34a; } .toggle-on:hover  { background:#16a34a;color:#fff; }
.toggle-off { background:#fef2f2;color:#ef4444; } .toggle-off:hover { background:#ef4444;color:#fff; }
.edit-btn   { background:#eff6ff;color:#3b82f6; } .edit-btn:hover   { background:#3b82f6;color:#fff; }
.del-btn    { background:#fef2f2;color:#ef4444; } .del-btn:hover    { background:#ef4444;color:#fff; }

.modal-overlay { position:fixed;inset:0;background:rgba(15,23,42,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(3px); }
.modal-box  { background:#fff;border-radius:10px;width:600px;max-width:90vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.15);animation:modalIn 0.2s ease; }
@keyframes modalIn { from{opacity:0;transform:scale(0.95)} to{opacity:1;transform:scale(1)} }
.modal-header { display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #f1f5f9; }
.modal-header h3 { font-size:15px;font-weight:700;color:#0f172a;margin:0; }
.modal-close { width:30px;height:30px;border-radius:6px;border:none;background:#f8fafc;color:#64748b;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;transition:all 0.15s; }
.modal-close:hover { background:#f1f5f9; }
.modal-body { padding:18px 22px; }
.form-group { margin-bottom:14px; }
.form-group:last-child { margin-bottom:0; }
.form-label { font-size:13px;font-weight:600;color:#0f172a;display:block;margin-bottom:6px; }
.required { color:#ef4444; }
.form-input { width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;outline:none;box-sizing:border-box;transition:all 0.15s; }
.form-input:focus { border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,0.08); }
.icon-input-wrap { display:flex;gap:8px;align-items:center; }
.icon-preview { width:40px;height:40px;border-radius:6px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
.icon-input-wrap .form-input { flex:1; }
.modal-footer { display:flex;gap:8px;padding:14px 22px;border-top:1px solid #f1f5f9; }
.btn-cancel { flex:1;padding:9px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.15s; }
.btn-cancel:hover { background:#f8fafc; }
.btn-cancel:disabled { opacity:0.5;cursor:not-allowed; }
.btn-save   { flex:2;padding:9px;border-radius:6px;border:none;background:#7c3aed;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;transition:all 0.15s; }
.btn-save:hover { background:#6d28d9; }
.btn-save:disabled { opacity:0.5;cursor:not-allowed; }

@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
.spin { animation:spin 0.8s linear infinite; }

/* Map badge on card */
.cat-map-badge { font-size:10px;color:#7c3aed;margin:2px 0 0;display:flex;align-items:center;gap:3px;font-weight:600; }
.cat-map-badge-missing { font-size:10px;color:#94a3b8;margin:2px 0 0;display:flex;align-items:center;gap:3px;font-weight:600; }

/* Map form elements */
.form-hint { font-size:11px;color:#94a3b8;margin:0 0 8px;line-height:1.4; }
.form-textarea { width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:12px;outline:none;box-sizing:border-box;resize:vertical;font-family:monospace;transition:all 0.15s; }
.form-textarea:focus { border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,0.08); }
.map-preview { margin-top:12px; }
.map-preview-label { font-size:12px;font-weight:600;color:#0f172a;margin:0 0 8px;display:flex;align-items:center;gap:4px; }
.map-preview-wrap { border-radius:6px;overflow:hidden;border:1px solid #c4b5fd;box-shadow:0 0 15px 4px rgba(124,58,237,0.15), 0 4px 20px rgba(0,0,0,0.08); }
.map-preview-wrap :deep(iframe) { width:100%;height:350px;border:none;display:block; }

@media(max-width:768px) {
    .cat-layout { flex-direction:column; }
    .cat-sidebar { width:100%;display:flex;gap:4px;padding:6px; }
    .cat-tab { flex:1;justify-content:center;padding:8px; }
    .cat-grid { grid-template-columns:1fr; }
}
</style>
