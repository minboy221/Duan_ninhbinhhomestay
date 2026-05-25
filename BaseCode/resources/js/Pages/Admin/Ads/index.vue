<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const ads = ref([
    { id:1, title:'Google AdSense - Banner Top',    type:'banner',  position:'top',     revenue:125000, clicks:340, active:true,  code:'<ins class="adsbygoogle"...' },
    { id:2, title:'Google AdSense - Sidebar Right', type:'sidebar', position:'sidebar',  revenue:87000,  clicks:210, active:true,  code:'<ins class="adsbygoogle"...' },
    { id:3, title:'Quảng cáo Nội Địa #1',          type:'native',  position:'content',  revenue:54000,  clicks:156, active:false, code:'<div class="ad-native"...' },
])

const showModal  = ref(false)
const editAd     = ref(null)
const form       = ref({ title:'', type:'banner', position:'top', code:'' })

function openAdd()  { editAd.value=null; form.value={title:'',type:'banner',position:'top',code:''}; showModal.value=true }
function openEdit(a){ editAd.value=a; form.value={...a}; showModal.value=true }
function save() {
    if (editAd.value) Object.assign(editAd.value, form.value)
    else ads.value.push({ id:Date.now(), ...form.value, revenue:0, clicks:0, active:true })
    showModal.value = false
}
function toggleAd(a) { a.active = !a.active }
function deleteAd(a) { ads.value = ads.value.filter(x => x.id !== a.id) }

const totalRev    = ads.value.reduce((s,a) => s + a.revenue, 0)
const totalClicks = ads.value.reduce((s,a) => s + a.clicks, 0)

const posLabel = { top:'Header Top', sidebar:'Sidebar', content:'Trong nội dung', footer:'Footer' }
const typeColor = { banner:'type-blue', sidebar:'type-purple', native:'type-green' }
</script>

<template>
    <Head title="Admin - Quảng Cáo" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Quản Lý Quảng Cáo</h1>
                <p class="page-sub">Quản lý vị trí quảng cáo Google AdSense và nội địa</p>
            </div>
        </template>

        <!-- Stats -->
        <div class="stats-row">
            <div class="scard sc-green"><div class="sc-icon"><i class="bi bi-cash-stack"></i></div><div><p class="snum">{{ totalRev.toLocaleString() }}đ</p><p class="slbl">Tổng doanh thu quảng cáo</p></div></div>
            <div class="scard sc-blue"><div class="sc-icon"><i class="bi bi-cursor-fill"></i></div><div><p class="snum">{{ totalClicks }}</p><p class="slbl">Tổng lượt click</p></div></div>
            <div class="scard sc-purple"><div class="sc-icon"><i class="bi bi-display-fill"></i></div><div><p class="snum">{{ ads.filter(a=>a.active).length }}</p><p class="slbl">Đang chạy</p></div></div>
        </div>

        <!-- Header action -->
        <div class="section-header">
            <h3 class="sec-title">Danh Sách Vị Trí Quảng Cáo</h3>
            <button @click="openAdd" class="btn-add"><i class="bi bi-plus-lg"></i> Thêm vị trí</button>
        </div>

        <!-- Ad cards -->
        <div class="ads-grid">
            <div v-for="ad in ads" :key="ad.id" :class="['ad-card', !ad.active ? 'ad-inactive' : '']">
                <div class="ad-top">
                    <div class="ad-icon"><i class="bi bi-megaphone-fill"></i></div>
                    <div class="ad-meta">
                        <span :class="['type-badge', typeColor[ad.type]]">{{ ad.type }}</span>
                        <span :class="['status-chip', ad.active ? 's-green' : 's-gray']">{{ ad.active ? 'Đang chạy' : 'Tắt' }}</span>
                    </div>
                </div>
                <h4 class="ad-title">{{ ad.title }}</h4>
                <p class="ad-pos"><i class="bi bi-geo-alt"></i> Vị trí: {{ posLabel[ad.position] || ad.position }}</p>
                <div class="ad-stats">
                    <div class="ad-stat">
                        <span class="stat-v">{{ ad.revenue.toLocaleString() }}đ</span>
                        <span class="stat-l">Doanh thu</span>
                    </div>
                    <div class="ad-stat">
                        <span class="stat-v">{{ ad.clicks }}</span>
                        <span class="stat-l">Lượt click</span>
                    </div>
                    <div class="ad-stat">
                        <span class="stat-v">{{ ad.clicks > 0 ? (ad.revenue/ad.clicks).toFixed(0) : 0 }}đ</span>
                        <span class="stat-l">CPC</span>
                    </div>
                </div>
                <div class="code-preview">
                    <code>{{ ad.code }}</code>
                </div>
                <div class="ad-actions">
                    <button @click="openEdit(ad)" class="act-btn act-edit"><i class="bi bi-pencil-fill"></i> Chỉnh sửa</button>
                    <button @click="toggleAd(ad)" :class="['act-btn', ad.active ? 'act-hide' : 'act-show']">
                        <i :class="['bi', ad.active ? 'bi-pause-fill' : 'bi-play-fill']"></i>
                        {{ ad.active ? 'Tắt' : 'Bật' }}
                    </button>
                    <button @click="deleteAd(ad)" class="act-btn act-del"><i class="bi bi-trash3-fill"></i></button>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="showModal=false">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3>{{ editAd ? 'Chỉnh Sửa Quảng Cáo' : 'Thêm Vị Trí Quảng Cáo' }}</h3>
                        <button @click="showModal=false" class="modal-close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Tên vị trí</label>
                            <input v-model="form.title" class="form-input" placeholder="VD: Google AdSense - Header" />
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Loại</label>
                                <select v-model="form.type" class="form-input">
                                    <option value="banner">Banner</option>
                                    <option value="sidebar">Sidebar</option>
                                    <option value="native">Nội địa</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vị trí</label>
                                <select v-model="form.position" class="form-input">
                                    <option value="top">Header Top</option>
                                    <option value="sidebar">Sidebar</option>
                                    <option value="content">Trong nội dung</option>
                                    <option value="footer">Footer</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mã quảng cáo (HTML/Script)</label>
                            <textarea v-model="form.code" class="form-textarea" rows="4" placeholder="Dán mã quảng cáo vào đây..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button @click="showModal=false" class="btn-cancel">Hủy</button>
                        <button @click="save" class="btn-save"><i class="bi bi-check-lg"></i> Lưu</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.page-title{font-size:18px;font-weight:700;color:#0f172a;margin:0}.page-sub{font-size:12px;color:#94a3b8;margin:2px 0 0}
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}
.scard{background:#fff;border-radius:14px;padding:16px;display:flex;align-items:center;gap:12px;border:1px solid #f1f5f9;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.sc-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.sc-green .sc-icon{background:#f0fdf4;color:#22c55e}.sc-green .snum{color:#16a34a}
.sc-blue .sc-icon{background:#eff6ff;color:#3b82f6}.sc-blue .snum{color:#2563eb}
.sc-purple .sc-icon{background:#faf5ff;color:#7c3aed}.sc-purple .snum{color:#7c3aed}
.snum{font-size:20px;font-weight:800;margin:0;line-height:1}.slbl{font-size:11px;color:#94a3b8;margin:2px 0 0}
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.sec-title{font-size:15px;font-weight:700;color:#0f172a;margin:0}
.btn-add{padding:9px 16px;border-radius:10px;border:none;background:#7c3aed;color:#fff;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px}
.btn-add:hover{background:#6d28d9}
.ads-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
.ad-card{background:#fff;border-radius:14px;border:1px solid #f1f5f9;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.05);transition:box-shadow .2s}
.ad-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.ad-inactive{opacity:.6;border-style:dashed}
.ad-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.ad-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px}
.ad-meta{display:flex;gap:6px}
.type-badge{font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px}
.type-blue{background:#eff6ff;color:#2563eb}.type-purple{background:#faf5ff;color:#7c3aed}.type-green{background:#f0fdf4;color:#16a34a}
.status-chip{font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px}
.s-green{background:#f0fdf4;color:#16a34a}.s-gray{background:#f1f5f9;color:#64748b}
.ad-title{font-size:14px;font-weight:700;color:#0f172a;margin:0 0 4px}
.ad-pos{font-size:12px;color:#94a3b8;margin:0 0 12px;display:flex;align-items:center;gap:4px}
.ad-stats{display:flex;gap:0;border:1px solid #f1f5f9;border-radius:10px;overflow:hidden;margin-bottom:12px}
.ad-stat{flex:1;padding:8px 10px;text-align:center;border-right:1px solid #f1f5f9}
.ad-stat:last-child{border-right:none}
.stat-v{display:block;font-size:13px;font-weight:700;color:#0f172a}
.stat-l{display:block;font-size:10px;color:#94a3b8;margin-top:2px}
.code-preview{background:#0f172a;border-radius:8px;padding:8px 12px;margin-bottom:12px;overflow:hidden}
.code-preview code{font-size:11px;color:#7dd3fc;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}
.ad-actions{display:flex;gap:6px}
.act-btn{flex:1;padding:7px 10px;border-radius:9px;border:none;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;transition:all .15s}
.act-edit{background:#faf5ff;color:#7c3aed;flex:2}.act-edit:hover{background:#7c3aed;color:#fff}
.act-show{background:#f0fdf4;color:#16a34a}.act-show:hover{background:#16a34a;color:#fff}
.act-hide{background:#f8fafc;color:#64748b}.act-hide:hover{background:#e2e8f0}
.act-del{background:#fef2f2;color:#ef4444;flex:0 0 auto;width:34px}.act-del:hover{background:#ef4444;color:#fff}
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.5);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(3px)}
.modal-box{background:#fff;border-radius:18px;width:500px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.15);overflow:hidden}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #f1f5f9}
.modal-header h3{font-size:15px;font-weight:700;color:#0f172a;margin:0}
.modal-close{width:30px;height:30px;border-radius:8px;border:none;background:#f8fafc;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center}
.modal-body{padding:18px 22px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-group{margin-bottom:12px}
.form-label{font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px}
.form-input{width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;box-sizing:border-box}
.form-input:focus{border-color:#7c3aed}
.form-textarea{width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;font-family:monospace}
.form-textarea:focus{border-color:#7c3aed}
.modal-footer{display:flex;gap:8px;padding:14px 22px;border-top:1px solid #f1f5f9}
.btn-cancel{flex:1;padding:9px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer}
.btn-save{flex:2;padding:9px;border-radius:10px;border:none;background:#7c3aed;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px}
.btn-save:hover{background:#6d28d9}
</style>
