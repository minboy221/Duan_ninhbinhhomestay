<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const form = useForm({
    title: '',
    summary: '',
    content: '',
    category: 'Tin Tức',
    tags: '',
    image_file: null,
    image_url: ''
})

const categories = ['Tin Tức', 'Việc Làm', 'Hướng Dẫn', 'Khác']
const showCustomCategory = ref(false)
const customCategory = ref('')

function handleCategoryChange(e) {
    if (e.target.value === 'custom') {
        showCustomCategory.value = true
        form.category = ''
    } else {
        showCustomCategory.value = false
        form.category = e.target.value
    }
}

function handleCustomCategoryInput() {
    form.category = customCategory.value
}

// Image preview state
const imagePreviewSrc = ref(null)
function handleFileChange(e) {
    const file = e.target.files[0]
    if (file) {
        form.image_file = file
        form.image_url = '' // clear URL if file uploaded
        const reader = new FileReader()
        reader.onload = (event) => {
            imagePreviewSrc.value = event.target.result
        }
        reader.readAsDataURL(file)
    }
}

function clearImage() {
    form.image_file = null
    form.image_url = ''
    imagePreviewSrc.value = null
}

// HTML editor helper
const contentTextarea = ref(null)
const activeTab = ref('write') // 'write' or 'preview'

function insertTag(startTag, endTag = '') {
    const el = contentTextarea.value
    if (!el) return

    const startPos = el.selectionStart
    const endPos = el.selectionEnd
    const text = form.content

    const selectedText = text.substring(startPos, endPos)
    const replacement = startTag + selectedText + endTag

    form.content = text.substring(0, startPos) + replacement + text.substring(endPos)

    setTimeout(() => {
        el.focus()
        el.setSelectionRange(startPos + startTag.length, startPos + startTag.length + selectedText.length)
    }, 10)
}

function submit() {
    form.post(route('admin.posts.store'))
}
</script>

<template>
    <Head title="Admin - Viết Bài Mới" />
    <AdminLayout>
        <template #header-title>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.posts.index')" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-all" title="Quay lại danh sách">
                    <i class="bi bi-arrow-left text-lg"></i>
                </Link>
                <div>
                    <h1 class="text-xl font-black text-slate-900 leading-tight">Viết Bài Mới</h1>
                    <p class="text-xs text-slate-500 font-medium">Tạo bài viết và phát hành lên trang tin tức Ninh Bình Homestay</p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6">
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- Left Section: Form fields -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Title & Summary Card -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-5">
                        <!-- Tiêu đề -->
                        <div>
                            <label for="title" class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider mb-2">
                                Tiêu đề bài viết <span class="text-rose-500">*</span>
                            </label>
                            <input v-model="form.title" type="text" id="title"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 placeholder:font-normal"
                                placeholder="Nhập tiêu đề hấp dẫn bài viết..." required />
                            <span v-if="form.errors.title" class="text-xs text-rose-500 font-bold block mt-1.5">{{ form.errors.title }}</span>
                        </div>

                        <!-- Tóm tắt -->
                        <div>
                            <label for="summary" class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider mb-2">
                                Tóm tắt bài viết
                            </label>
                            <textarea v-model="form.summary" id="summary" rows="3"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
                                placeholder="Tóm tắt ngắn gọn nội dung hiển thị ở danh sách bài viết..."></textarea>
                            <span v-if="form.errors.summary" class="text-xs text-rose-500 font-bold block mt-1.5">{{ form.errors.summary }}</span>
                        </div>
                    </div>

                    <!-- Content Editor Card -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                            <label class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider">
                                Nội dung bài viết <span class="text-rose-500">*</span>
                            </label>
                            
                            <!-- Tab switch: Write / Preview -->
                            <div class="flex p-1 bg-slate-100 rounded-xl gap-1">
                                <button type="button" 
                                    @click="activeTab = 'write'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                                    :class="activeTab === 'write' ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-500 hover:text-slate-800'">
                                    <i class="bi bi-pencil-square"></i> Soạn thảo
                                </button>
                                <button type="button" 
                                    @click="activeTab = 'preview'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                                    :class="activeTab === 'preview' ? 'bg-white text-indigo-600 shadow-xs' : 'text-slate-500 hover:text-slate-800'">
                                    <i class="bi bi-eye-fill"></i> Xem trước
                                </button>
                            </div>
                        </div>

                        <!-- Editor Pane -->
                        <div v-show="activeTab === 'write'" class="border border-slate-200 rounded-xl overflow-hidden focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20 transition-all">
                            <!-- Toolbar -->
                            <div class="flex items-center gap-1.5 p-2 bg-slate-50/80 border-b border-slate-200 flex-wrap">
                                <button type="button" @click="insertTag('<b>', '</b>')" class="p-2 hover:bg-slate-200/70 text-slate-700 rounded-lg text-xs font-bold transition-all cursor-pointer" title="In đậm">
                                    <i class="bi bi-type-bold text-sm"></i>
                                </button>
                                <button type="button" @click="insertTag('<i>', '</i>')" class="p-2 hover:bg-slate-200/70 text-slate-700 rounded-lg text-xs font-bold transition-all cursor-pointer" title="In nghiêng">
                                    <i class="bi bi-type-italic text-sm"></i>
                                </button>
                                <div class="w-px h-4 bg-slate-300 mx-1"></div>
                                <button type="button" @click="insertTag('<h4>', '</h4>')" class="p-2 hover:bg-slate-200/70 text-slate-700 rounded-lg text-xs font-bold transition-all cursor-pointer" title="Tiêu đề H4">
                                    <i class="bi bi-type-h4 text-sm"></i>
                                </button>

                                <button type="button" @click="insertTag('<p>', '</p>')" class="p-2 hover:bg-slate-200/70 text-slate-700 rounded-lg text-xs font-bold transition-all cursor-pointer" title="Đoạn văn">
                                    <i class="bi bi-paragraph text-sm"></i>
                                </button>
                                <div class="w-px h-4 bg-slate-300 mx-1"></div>
                                <button type="button" @click="insertTag('<ul>\n  <li>', '</li>\n</ul>')" class="p-2 hover:bg-slate-200/70 text-slate-700 rounded-lg text-xs font-bold transition-all cursor-pointer" title="Danh sách">
                                    <i class="bi bi-list-ul text-sm"></i>
                                </button>
                                <button type="button" @click="insertTag('  <li>', '</li>\n')" class="p-2 hover:bg-slate-200/70 text-slate-700 rounded-lg text-xs font-bold transition-all cursor-pointer" title="Mục trong danh sách">
                                    <i class="bi bi-list-task text-sm"></i>
                                </button>
                                <div class="w-px h-4 bg-slate-300 mx-1"></div>
                                <button type="button" @click="insertTag('<a href=\x22#\x22 target=\x22_blank\x22>', '</a>')" class="p-2 hover:bg-slate-200/70 text-slate-700 rounded-lg text-xs font-bold transition-all cursor-pointer" title="Chèn đường dẫn">
                                    <i class="bi bi-link-45deg text-sm"></i>
                                </button>
                            </div>
                            <!-- Textarea -->
                            <textarea 
                                ref="contentTextarea" 
                                v-model="form.content" 
                                rows="16" 
                                class="w-full p-4 text-sm font-mono text-slate-800 bg-white focus:outline-none resize-y leading-relaxed" 
                                placeholder="Nhập nội dung chi tiết bài viết (Hỗ trợ thẻ HTML)..."
                                required
                            ></textarea>
                        </div>

                        <!-- Preview Pane -->
                        <div v-show="activeTab === 'preview'" class="border border-slate-200 rounded-xl p-6 bg-slate-50/50 min-h-[380px] max-h-[550px] overflow-y-auto">
                            <div v-if="form.content" class="post-content-body prose prose-slate max-w-none" v-html="form.content"></div>
                            <div v-else class="flex flex-col items-center justify-center h-64 text-slate-400 space-y-2">
                                <i class="bi bi-file-earmark-richtext text-4xl text-slate-300"></i>
                                <p class="text-xs italic">Nội dung xem trước đang trống. Hãy nhập nội dung ở tab Soạn thảo...</p>
                            </div>
                        </div>

                        <span v-if="form.errors.content" class="text-xs text-rose-500 font-bold block mt-1.5">{{ form.errors.content }}</span>
                    </div>
                </div>

                <!-- Right Section: Settings & Actions Cards -->
                <div class="space-y-6">
                    <!-- Card 1: Cấu hình bài đăng -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3 flex items-center gap-2">
                            <i class="bi bi-sliders text-indigo-600"></i> Cấu hình bài đăng
                        </h3>

                        <!-- Danh mục -->
                        <div>
                            <label for="category" class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider mb-2">
                                Danh mục <span class="text-rose-500">*</span>
                            </label>
                            <select id="category" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer" @change="handleCategoryChange">
                                <option v-for="cat in categories" :key="cat" :value="cat">
                                    {{ cat }}
                                </option>
                                <option value="custom">-- Danh mục tùy chỉnh --</option>
                            </select>

                            <!-- Custom Category Input -->
                            <div v-if="showCustomCategory" class="mt-2">
                                <input 
                                    v-model="customCategory" 
                                    type="text" 
                                    class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" 
                                    placeholder="Nhập danh mục mới..." 
                                    @input="handleCustomCategoryInput"
                                    required
                                />
                            </div>
                            <span v-if="form.errors.category" class="text-xs text-rose-500 font-bold block mt-1.5">{{ form.errors.category }}</span>
                        </div>

                        <!-- Từ khóa Tags -->
                        <div>
                            <label for="tags" class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider mb-2">
                                Từ khóa (Tags)
                            </label>
                            <input v-model="form.tags" type="text" id="tags"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                placeholder="Ví dụ: Ninh Binh, Du Lich..." />
                            <p class="text-[10px] text-slate-400 font-medium mt-1">Phân tách các từ khóa bằng dấu phẩy (,)</p>
                            <span v-if="form.errors.tags" class="text-xs text-rose-500 font-bold block mt-1.5">{{ form.errors.tags }}</span>
                        </div>
                    </div>

                    <!-- Card 2: Ảnh bìa -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-4">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3 flex items-center gap-2">
                            <i class="bi bi-image-fill text-indigo-600"></i> Ảnh bìa bài viết
                        </h3>

                        <div class="space-y-3">
                            <!-- File Upload Area -->
                            <label for="image_file" class="flex flex-col items-center justify-center p-4 border-2 border-dashed border-slate-200 hover:border-indigo-500 rounded-xl bg-slate-50 hover:bg-indigo-50/30 cursor-pointer transition-all text-center group">
                                <i class="bi bi-cloud-arrow-up text-2xl text-slate-400 group-hover:text-indigo-600 transition-colors mb-1"></i>
                                <span class="text-xs font-bold text-slate-700 group-hover:text-indigo-600 transition-colors">Tải ảnh từ máy tính</span>
                                <span class="text-[10px] text-slate-400 font-medium">PNG, JPG, WEBP tối đa 5MB</span>
                            </label>
                            <input type="file" id="image_file" accept="image/*" @change="handleFileChange" class="hidden" />

                            <div class="flex items-center gap-2 my-2">
                                <div class="h-px bg-slate-200 flex-1"></div>
                                <span class="text-[10px] uppercase font-bold text-slate-400">Hoặc URL</span>
                                <div class="h-px bg-slate-200 flex-1"></div>
                            </div>

                            <!-- URL Input -->
                            <input v-model="form.image_url" type="url"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                                placeholder="Dán link ảnh https://..." />

                            <span v-if="form.errors.image_file" class="text-xs text-rose-500 font-bold block mt-1.5">{{ form.errors.image_file }}</span>
                        </div>

                        <!-- Image Preview -->
                        <div v-if="imagePreviewSrc || form.image_url" class="space-y-2 pt-2 border-t border-slate-100">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-700">Xem trước ảnh bìa:</span>
                                <button type="button" @click="clearImage" class="text-xs text-rose-500 hover:text-rose-700 font-bold cursor-pointer">Xóa ảnh</button>
                            </div>
                            <div class="rounded-xl overflow-hidden border border-slate-200 max-h-40 bg-slate-900/5">
                                <img :src="imagePreviewSrc || form.image_url" class="w-full h-full object-cover" alt="Ảnh bìa bài viết" />
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Thao tác chính -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 space-y-3">
                        <button type="submit" :disabled="form.processing"
                            class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl font-bold text-sm shadow-md shadow-indigo-500/20 hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <i class="bi bi-send-fill text-base"></i>
                            <span>{{ form.processing ? 'Đang lưu...' : 'Đăng Bài Viết' }}</span>
                        </button>

                        <Link :href="route('admin.posts.index')" class="w-full py-2.5 text-center text-xs font-bold text-slate-500 hover:text-rose-600 transition-colors block">
                            Hủy bỏ & Quay lại
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.post-content-body :deep(h4) { font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-top: 1rem; margin-bottom: 0.5rem; }
.post-content-body :deep(p) { margin-bottom: 0.75rem; line-height: 1.6; }
.post-content-body :deep(ul) { padding-left: 1.25rem; margin-bottom: 0.75rem; list-style-type: disc; }
.post-content-body :deep(li) { margin-bottom: 0.25rem; }
.post-content-body :deep(a) { color: #2563eb; text-decoration: underline; font-weight: 600; }
</style>
