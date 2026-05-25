<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const activeTab = ref('types')

const tabs = [
    { key: 'types',      label: 'Loại Phòng' },
    { key: 'areas',      label: 'Khu Vực' },
    { key: 'amenities',  label: 'Tiện Ích' },
]

const data = ref({
    types: [
        { id:1, name:'Phòng đơn', icon:'bi-door-closed', count: 24, active: true },
        { id:2, name:'Phòng ghép', icon:'bi-people', count: 8, active: true },
        { id:3, name:'Nhà nguyên căn', icon:'bi-house', count: 15, active: true },
        { id:4, name:'Studio', icon:'bi-building', count: 11, active: true },
        { id:5, name:'Căn hộ dịch vụ', icon:'bi-buildings', count: 6, active: false },
    ],
    areas: [
        { id:1, name:'Hoa Lư', icon:'bi-geo-alt', count: 32, active: true },
        { id:2, name:'Duy Tiên', icon:'bi-geo-alt', count: 18, active: true },
        { id:3, name:'Gia Viễn', icon:'bi-geo-alt', count: 14, active: true },
        { id:4, name:'Yên Khánh', icon:'bi-geo-alt', count: 9, active: true },
        { id:5, name:'Kim Sơn', icon:'bi-geo-alt', count: 7, active: false },
    ],
    amenities: [
        { id:1, name:'WiFi', icon:'bi-wifi', count: 48, active: true },
        { id:2, name:'Điều hoà', icon:'bi-thermometer-snow', count: 35, active: true },
        { id:3, name:'Nóng lạnh', icon:'bi-droplet-half', count: 42, active: true },
        { id:4, name:'Bãi xe', icon:'bi-bicycle', count: 28, active: true },
        { id:5, name:'Bảo vệ 24/7', icon:'bi-shield-check', count: 12, active: true },
        { id:6, name:'Giặt sấy', icon:'bi-basket2', count: 20, active: false },
    ],
})

const showForm = ref(false)
const editItem = ref(null)
const newName  = ref('')

function openAdd()  { editItem.value = null; newName.value = ''; showForm.value = true }
function openEdit(item) { editItem.value = item; newName.value = item.name; showForm.value = true }
function saveItem() {
    if (!newName.value.trim()) return
    const list = data.value[activeTab.value]
    if (editItem.value) {
        editItem.value.name = newName.value
    } else {
        list.push({ id: Date.now(), name: newName.value, icon: 'bi-tag', count: 0, active: true })
    }
    showForm.value = false
}
function toggleActive(item) { item.active = !item.active }
function deleteItem(item) {
    data.value[activeTab.value] = data.value[activeTab.value].filter(i => i.id !== item.id)
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

                <div class="cat-grid">
                    <div v-for="item in data[activeTab]" :key="item.id" class="cat-card" :class="!item.active ? 'cat-inactive' : ''">
                        <div class="cat-icon-wrap">
                            <i :class="['bi', item.icon]"></i>
                        </div>
                        <div class="cat-info">
                            <p class="cat-name">{{ item.name }}</p>
                            <p class="cat-count">{{ item.count }} tin đăng</p>
                        </div>
                        <div class="cat-actions">
                            <button @click="toggleActive(item)" :class="['toggle-btn', item.active ? 'toggle-on' : 'toggle-off']" :title="item.active ? 'Ẩn' : 'Hiện'">
                                <i :class="['bi', item.active ? 'bi-eye-fill' : 'bi-eye-slash']"></i>
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
                        <h3>{{ editItem ? 'Chỉnh Sửa' : 'Thêm Mới' }}</h3>
                        <button @click="showForm=false" class="modal-close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Tên danh mục</label>
                        <input v-model="newName" @keyup.enter="saveItem" type="text" class="form-input" placeholder="Nhập tên..." />
                    </div>
                    <div class="modal-footer">
                        <button @click="showForm=false" class="btn-cancel">Hủy</button>
                        <button @click="saveItem" class="btn-save">
                            <i class="bi bi-check-lg"></i> Lưu
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

.cat-layout { display:flex;gap:20px;align-items:flex-start; }

.cat-sidebar { width:200px;flex-shrink:0;background:#fff;border-radius:14px;border:1px solid #f1f5f9;padding:8px;box-shadow:0 1px 3px rgba(0,0,0,0.05); }
.cat-tab { width:100%;display:flex;align-items:center;gap:8px;padding:11px 12px;border-radius:10px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:600;color:#64748b;text-align:left;transition:all 0.15s; }
.cat-tab:hover { background:#f8fafc; }
.cat-tab-active { background:#7c3aed;color:#fff; }
.cat-tab i { font-size:15px; }
.cat-tab-count { margin-left:auto;min-width:20px;height:20px;border-radius:99px;background:rgba(0,0,0,0.08);font-size:11px;display:flex;align-items:center;justify-content:center;padding:0 5px; }

.cat-content { flex:1; }
.cat-header  { display:flex;align-items:center;justify-content:space-between;margin-bottom:16px; }
.cat-list-title { font-size:16px;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:8px; }
.cat-badge { font-size:11px;background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:99px;font-weight:600; }
.btn-add { padding:9px 16px;border-radius:10px;border:none;background:#7c3aed;color:#fff;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px; }
.btn-add:hover { background:#6d28d9; }

.cat-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px; }
.cat-card { background:#fff;border-radius:14px;border:1px solid #f1f5f9;padding:16px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 3px rgba(0,0,0,0.04);transition:all 0.15s; }
.cat-card:hover { box-shadow:0 4px 12px rgba(0,0,0,0.08); }
.cat-inactive { opacity:0.5; }
.cat-icon-wrap { width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
.cat-info { flex:1; }
.cat-name  { font-size:13px;font-weight:700;color:#0f172a;margin:0; }
.cat-count { font-size:11px;color:#94a3b8;margin:2px 0 0; }
.cat-actions { display:flex;flex-direction:column;gap:4px; }
.toggle-btn,.edit-btn,.del-btn { width:26px;height:26px;border-radius:7px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;transition:all 0.15s; }
.toggle-on  { background:#f0fdf4;color:#16a34a; } .toggle-on:hover  { background:#16a34a;color:#fff; }
.toggle-off { background:#fef2f2;color:#ef4444; } .toggle-off:hover { background:#ef4444;color:#fff; }
.edit-btn   { background:#eff6ff;color:#3b82f6; } .edit-btn:hover   { background:#3b82f6;color:#fff; }
.del-btn    { background:#fef2f2;color:#ef4444; } .del-btn:hover    { background:#ef4444;color:#fff; }

.modal-overlay { position:fixed;inset:0;background:rgba(15,23,42,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(3px); }
.modal-box  { background:#fff;border-radius:18px;width:400px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,0.15);overflow:hidden; }
.modal-header { display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #f1f5f9; }
.modal-header h3 { font-size:15px;font-weight:700;color:#0f172a;margin:0; }
.modal-close { width:30px;height:30px;border-radius:8px;border:none;background:#f8fafc;color:#64748b;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center; }
.modal-body { padding:18px 22px; }
.form-label { font-size:13px;font-weight:600;color:#0f172a;display:block;margin-bottom:6px; }
.form-input { width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;box-sizing:border-box; }
.form-input:focus { border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,0.08); }
.modal-footer { display:flex;gap:8px;padding:14px 22px;border-top:1px solid #f1f5f9; }
.btn-cancel { flex:1;padding:9px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer; }
.btn-save   { flex:2;padding:9px;border-radius:10px;border:none;background:#7c3aed;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px; }
.btn-save:hover { background:#6d28d9; }
</style>
