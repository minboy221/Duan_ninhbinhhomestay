<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    posts: { type: Array, default: () => [] }
})

const search = ref('')
const categoryFilter = ref('all')
const currentPage = ref(1)
const perPage = 10

// Unique categories derived from posts
const categories = computed(() => {
    const cats = props.posts.map(p => p.category).filter(Boolean)
    return ['all', ...new Set(cats)]
})

const filtered = computed(() => {
    return props.posts.filter(p => {
        const q = search.value.toLowerCase()
        const matchSearch = !q || p.title.toLowerCase().includes(q) || (p.summary && p.summary.toLowerCase().includes(q))
        const matchCategory = categoryFilter.value === 'all' || p.category === categoryFilter.value
        return matchSearch && matchCategory
    })
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage)))
const paginated = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return filtered.value.slice(start, start + perPage)
})

// Delete Modal State
const showDeleteModal = ref(false)
const targetPost = ref(null)

function openDeleteModal(post) {
    targetPost.value = post
    showDeleteModal.value = true
}

function confirmDelete() {
    if (!targetPost.value) return
    router.delete(route('admin.posts.destroy', targetPost.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            targetPost.value = null
        }
    })
}

function resetFilters() {
    search.value = ''
    categoryFilter.value = 'all'
    currentPage.value = 1
}
</script>

<template>
    <Head title="Admin - Quản Lý Tin Tức" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Quản Lý Bài Viết & Tin Tức</h1>
                <p class="page-sub">Tổng cộng {{ filtered.length }} bài viết</p>
            </div>
        </template>

        <!-- Filter bar -->
        <div class="filter-bar">
            <div class="search-wrap">
                <i class="bi bi-search search-icon"></i>
                <input v-model="search" @input="currentPage=1" type="text" placeholder="Tìm kiếm theo tiêu đề..." class="search-input" />
            </div>
            <select v-model="categoryFilter" @change="currentPage=1" class="filter-select">
                <option value="all">Tất cả danh mục</option>
                <option v-for="cat in categories.filter(c => c !== 'all')" :key="cat" :value="cat">
                    {{ cat }}
                </option>
            </select>
            <button @click="resetFilters" class="btn-reset">
                <i class="bi bi-x-circle"></i> Reset
            </button>
            <Link :href="route('admin.posts.create')" class="btn-add">
                <i class="bi bi-file-earmark-plus-fill"></i> Viết bài mới
            </Link>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="width:100px">Ảnh bìa</th>
                        <th>Tiêu đề</th>
                        <th>Danh mục</th>
                        <th>Người đăng</th>
                        <th>Lượt xem</th>
                        <th>Ngày tạo</th>
                        <th style="width:120px;text-align:center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="paginated.length === 0">
                        <td colspan="8" class="empty-row">
                            <i class="bi bi-inbox text-3xl text-gray-300"></i>
                            <p>Không tìm thấy bài viết nào</p>
                        </td>
                    </tr>
                    <tr v-for="(p, i) in paginated" :key="p.id" class="table-row">
                        <td class="idx">{{ (currentPage - 1) * perPage + i + 1 }}</td>
                        <td>
                            <img :src="p.image || '/anh/banner_tro.png'" class="post-thumb" alt="Cover Image" />
                        </td>
                        <td>
                            <div class="post-title-cell">
                                <p class="post-title">{{ p.title }}</p>
                                <p class="post-summary-sub text-gray line-clamp-1">{{ p.summary }}</p>
                            </div>
                        </td>
                        <td>
                            <span :class="['category-badge', p.category === 'Việc Làm' ? 'cat-work' : 'cat-news']">
                                {{ p.category }}
                            </span>
                        </td>
                        <td class="text-gray">{{ p.author_name }}</td>
                        <td>
                            <i class="bi bi-eye"></i> {{ p.views }}
                        </td>
                        <td class="text-gray">{{ p.created_at }}</td>
                        <td>
                            <div class="action-btns">
                                <Link :href="route('chitiettintuc', p.slug)" target="_blank" class="act-btn act-view" title="Xem trên trang khách">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </Link>
                                <Link :href="route('admin.posts.edit', p.id)" class="act-btn act-edit" title="Chỉnh sửa">
                                    <i class="bi bi-pencil-fill"></i>
                                </Link>
                                <button class="act-btn act-delete" title="Xóa" @click="openDeleteModal(p)">
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
                    v-for="pg in totalPages" :key="pg"
                    @click="currentPage = pg"
                    :class="['page-btn', currentPage === pg ? 'page-active' : '']"
                >{{ pg }}</button>
                <button :disabled="currentPage === totalPages" @click="currentPage++" class="page-btn">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Delete Confirm Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal=false">
                <div class="modal-box">
                    <div class="modal-icon icon-red">
                        <i class="bi bi-trash3-fill"></i>
                    </div>
                    <h3 class="modal-title">Xác nhận xóa bài viết?</h3>
                    <p class="modal-desc">
                        Bạn có chắc chắn muốn xóa bài viết "{{ targetPost?.title }}"? Hành động này sẽ xóa vĩnh viễn dữ liệu và hình ảnh liên quan khỏi hệ thống.
                    </p>
                    <div class="modal-actions">
                        <button @click="showDeleteModal=false" class="btn-cancel">Hủy</button>
                        <button @click="confirmDelete" class="btn-confirm btn-danger">Xác nhận</button>
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
.search-input { width: 100%; padding: 9px 12px 9px 36px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; color: #0f172a; background:#fff; outline:none; transition: border 0.15s; box-sizing: border-box; }
.search-input:focus { border-color: #166ea9; box-shadow: 0 0 0 3px rgba(22, 110, 169, 0.08); }
.filter-select { padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; color: #334155; background:#fff; outline:none; cursor:pointer; }
.filter-select:focus { border-color: #166ea9; }
.btn-reset { padding: 9px 14px; border-radius: 10px; border: 1px solid #e2e8f0; background:#fff; color:#64748b; font-size:13px; cursor:pointer; display:flex;align-items:center;gap:5px; }
.btn-reset:hover { background: #f8fafc; }
.btn-add { padding: 9px 16px; border-radius: 10px; border: none; background: #166ea9; color:#fff; font-size:13px; font-weight:600; cursor:pointer; display:flex;align-items:center;gap:6px; transition: background 0.15s; text-decoration: none; }
.btn-add:hover { background: #0f4f7a; }

/* Table card */
.table-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 4px rgba(0,0,0,0.05); overflow: hidden; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th { text-align: left; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; padding: 14px 16px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; }
.data-table td { padding: 13px 16px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.table-row:last-child td { border-bottom: none; }
.table-row:hover td { background: #fafbff; }
.idx { color: #cbd5e1; font-weight: 600; font-size: 12px; }

.empty-row { text-align: center; padding: 48px !important; color: #94a3b8; }
.empty-row i { display: block; font-size: 40px; margin-bottom: 8px; }

.post-thumb { width: 80px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
.post-title-cell { display: flex; flex-direction: column; gap: 2px; }
.post-title { font-size: 13.5px; font-weight: 600; color: #0f172a; margin: 0; }
.post-summary-sub { font-size: 11.5px; margin: 0; width: 380px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.text-gray  { color: #64748b; }

.category-badge { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 99px; }
.cat-work { background: #faf5ff; color: #7c3aed; }
.cat-news { background: #eff6ff; color: #2563eb; }

.action-btns { display: flex; align-items: center; justify-content: center; gap: 6px; }
.act-btn { width: 30px; height: 30px; border-radius: 8px; border: none; cursor: pointer; display:flex;align-items:center;justify-content:center; font-size: 14px; transition: all 0.15s; text-decoration: none; }
.act-view   { background: #eff6ff; color: #3b82f6; }
.act-view:hover   { background: #3b82f6; color: #fff; }
.act-edit   { background: #f0fdf4; color: #16a34a; }
.act-edit:hover   { background: #16a34a; color: #fff; }
.act-delete { background: #fef2f2; color: #ef4444; }
.act-delete:hover { background: #ef4444; color: #fff; }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 16px; border-top: 1px solid #f1f5f9; }
.page-btn { min-width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-size: 13px; cursor: pointer; display:flex;align-items:center;justify-content:center; transition: all 0.15s; }
.page-btn:hover:not(:disabled) { border-color: #166ea9; color: #166ea9; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-active { background: #166ea9; border-color: #166ea9; color: #fff; font-weight: 700; }

/* Modal */
.modal-overlay { position:fixed;inset:0;background:rgba(15,23,42,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(2px); }
.modal-box { background:#fff;border-radius:20px;padding:32px;width:380px;max-width:90vw;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.15); }
.modal-icon { width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 16px; }
.icon-red    { background:#fef2f2;color:#ef4444; }
.modal-title { font-size:17px;font-weight:700;color:#0f172a;margin:0 0 8px; }
.modal-desc  { font-size:13px;color:#64748b;margin:0 0 24px;line-height:1.5; }
.modal-actions { display:flex;gap:10px; }
.btn-cancel  { flex:1;padding:10px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer; }
.btn-confirm { flex:1;padding:10px;border-radius:10px;border:none;color:#fff;font-size:13px;font-weight:600;cursor:pointer; }
.btn-danger  { background:#ef4444; }
.btn-danger:hover { background:#dc2626; }
</style>
