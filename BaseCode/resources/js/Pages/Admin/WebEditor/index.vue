<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const banners = ref([
    { id:1, title:'Banner chính trang chủ', img:'/anh/banner.png', active:true,  order:1 },
    { id:2, title:'Banner khuyến mãi hè',   img:'/anh/banner.png', active:false, order:2 },
])

const heroText = ref({ h1:'Tìm Phòng Và Nhà Trọ Phù Hợp', sub:'Hệ thống tìm kiếm và quản lý phòng trọ thông minh số 1 tại Ninh Bình.' })
const contact  = ref({ phone:'0912 345 678', email:'contact@ninhbinhhomestay.vn', address:'Ninh Bình, Việt Nam' })
const saved    = ref(false)

function save() { saved.value = true; setTimeout(() => saved.value = false, 2000) }
function toggleBanner(b) { b.active = !b.active }
function addBanner()     { banners.value.push({ id: Date.now(), title:'Banner mới', img:'', active:false, order: banners.value.length+1 }) }
function delBanner(b)    { banners.value = banners.value.filter(x => x.id !== b.id) }
</script>

<template>
    <Head title="Admin - Chỉnh Sửa Website" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Chỉnh Sửa Giao Diện Website</h1>
                <p class="page-sub">Quản lý banner, nội dung trang chủ và thông tin liên hệ</p>
            </div>
        </template>

        <div class="editor-layout">
            <!-- Left: Editors -->
            <div class="editor-main">

                <!-- Banner manager -->
                <div class="section-card">
                    <div class="sec-header">
                        <h3 class="sec-title"><i class="bi bi-images"></i> Quản Lý Banner</h3>
                        <button @click="addBanner" class="btn-sm-add"><i class="bi bi-plus-lg"></i> Thêm banner</button>
                    </div>
                    <div class="banner-list">
                        <div v-for="b in banners" :key="b.id" class="banner-item">
                            <div class="banner-thumb">
                                <img v-if="b.img" :src="b.img" alt="" />
                                <i v-else class="bi bi-image text-slate-300 text-2xl"></i>
                            </div>
                            <div class="banner-info">
                                <input v-model="b.title" class="banner-title-input" />
                                <div class="banner-meta">
                                    <span :class="['status-chip', b.active ? 's-green' : 's-gray']">{{ b.active ? 'Đang hiển thị' : 'Đã ẩn' }}</span>
                                    <span class="order-badge">Vị trí: {{ b.order }}</span>
                                </div>
                            </div>
                            <div class="banner-acts">
                                <button @click="toggleBanner(b)" :class="['act-btn', b.active ? 'act-hide' : 'act-show']">
                                    <i :class="['bi', b.active ? 'bi-eye-slash' : 'bi-eye']"></i>
                                </button>
                                <button class="act-btn act-upload" title="Upload ảnh"><i class="bi bi-upload"></i></button>
                                <button @click="delBanner(b)" class="act-btn act-del"><i class="bi bi-trash3"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hero text -->
                <div class="section-card">
                    <div class="sec-header">
                        <h3 class="sec-title"><i class="bi bi-type-h1"></i> Nội Dung Trang Chủ</h3>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tiêu đề chính (H1)</label>
                        <input v-model="heroText.h1" class="form-input" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mô tả phụ</label>
                        <textarea v-model="heroText.sub" class="form-textarea" rows="3"></textarea>
                    </div>
                </div>

                <!-- Contact info -->
                <div class="section-card">
                    <div class="sec-header">
                        <h3 class="sec-title"><i class="bi bi-telephone-fill"></i> Thông Tin Liên Hệ</h3>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Số điện thoại</label>
                            <input v-model="contact.phone" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input v-model="contact.email" class="form-input" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Địa chỉ</label>
                        <input v-model="contact.address" class="form-input" />
                    </div>
                </div>

                <button @click="save" :class="['save-btn', saved ? 'saved' : '']">
                    <i :class="['bi', saved ? 'bi-check-circle-fill' : 'bi-floppy-fill']"></i>
                    {{ saved ? 'Đã lưu!' : 'Lưu Thay Đổi' }}
                </button>
            </div>

            <!-- Right: Preview -->
            <div class="preview-panel">
                <h4 class="preview-title">Xem Trước</h4>
                <div class="preview-box">
                    <div class="preview-banner">
                        <i class="bi bi-image"></i>
                        <span>{{ heroText.h1 }}</span>
                        <p>{{ heroText.sub }}</p>
                    </div>
                    <div class="preview-contact">
                        <p><i class="bi bi-telephone"></i> {{ contact.phone }}</p>
                        <p><i class="bi bi-envelope"></i> {{ contact.email }}</p>
                        <p><i class="bi bi-geo-alt"></i> {{ contact.address }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.page-title{font-size:18px;font-weight:700;color:#0f172a;margin:0}.page-sub{font-size:12px;color:#94a3b8;margin:2px 0 0}
.editor-layout{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
@media(max-width:900px){.editor-layout{grid-template-columns:1fr}}
.editor-main{display:flex;flex-direction:column;gap:16px}
.section-card{background:#fff;border-radius:8px;border:1px solid #f1f5f9;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.sec-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.sec-title{font-size:14px;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:7px}
.btn-sm-add{padding:7px 12px;border-radius:6px;border:none;background:#7c3aed;color:#fff;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px}
.banner-list{display:flex;flex-direction:column;gap:10px}
.banner-item{display:flex;align-items:center;gap:12px;padding:12px;background:#f8fafc;border-radius:8px;border:1px solid #f1f5f9}
.banner-thumb{width:80px;height:50px;border-radius:6px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
.banner-thumb img{width:100%;height:100%;object-fit:cover}
.banner-info{flex:1}
.banner-title-input{width:100%;border:1px solid #e2e8f0;border-radius:6px;padding:5px 8px;font-size:13px;font-weight:600;color:#0f172a;outline:none;background:#fff;box-sizing:border-box}
.banner-title-input:focus{border-color:#7c3aed}
.banner-meta{display:flex;align-items:center;gap:8px;margin-top:6px}
.status-chip{font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px}
.s-green{background:#f0fdf4;color:#16a34a}.s-gray{background:#f1f5f9;color:#64748b}
.order-badge{font-size:11px;color:#94a3b8}
.banner-acts{display:flex;gap:5px}
.act-btn{width:32px;height:32px;border-radius:6px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:all .15s}
.act-hide{background:#f8fafc;color:#64748b}.act-hide:hover{background:#e2e8f0}
.act-show{background:#eff6ff;color:#3b82f6}.act-show:hover{background:#3b82f6;color:#fff}
.act-upload{background:#f0fdf4;color:#16a34a}.act-upload:hover{background:#16a34a;color:#fff}
.act-del{background:#fef2f2;color:#ef4444}.act-del:hover{background:#ef4444;color:#fff}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-group{margin-bottom:12px}
.form-label{font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:5px}
.form-input{width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;outline:none;box-sizing:border-box;color:#0f172a}
.form-input:focus{border-color:#7c3aed}
.form-textarea{width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;outline:none;resize:none;box-sizing:border-box}
.form-textarea:focus{border-color:#7c3aed}
.save-btn{width:100%;padding:12px;border-radius:8px;border:none;background:#7c3aed;color:#fff;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:all .2s}
.save-btn:hover{background:#6d28d9}.saved{background:#22c55e}
.preview-panel{background:#fff;border-radius:8px;border:1px solid #f1f5f9;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.05);position:sticky;top:24px}
.preview-title{font-size:13px;font-weight:700;color:#64748b;margin:0 0 12px;text-transform:uppercase;letter-spacing:.05em}
.preview-box{border:1px solid #e2e8f0;border-radius:6px;overflow:hidden}
.preview-banner{background:linear-gradient(135deg,#1e1b4b,#3730a3);padding:24px 16px;text-align:center;color:#fff;display:flex;flex-direction:column;align-items:center;gap:8px}
.preview-banner i{font-size:32px;opacity:.4}
.preview-banner span{font-size:13px;font-weight:700;line-height:1.3}
.preview-banner p{font-size:11px;opacity:.75;margin:0;line-height:1.4}
.preview-contact{padding:14px;display:flex;flex-direction:column;gap:6px}
.preview-contact p{font-size:12px;color:#475569;margin:0;display:flex;align-items:center;gap:6px}
.preview-contact i{color:#7c3aed}
</style>
