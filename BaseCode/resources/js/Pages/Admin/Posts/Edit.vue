<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { showSuccess, showError } from '@/Utils/swal'

const props = defineProps({
    post: { type: Object, required: true }
})

const form = useForm({
    title: props.post.title || '',
    summary: props.post.summary || '',
    content: props.post.content || '',
    category: props.post.category || 'Tin Tức',
    tags: props.post.tags || '',
    image_file: null,
    image_url: props.post.image && !props.post.image.startsWith('/storage/') ? props.post.image : ''
})

const categories = ['Tin Tức', 'Việc Làm', 'Hướng Dẫn', 'Khác']
const showCustomCategory = ref(false)
const customCategory = ref('')

onMounted(() => {
    // Check if the current category is a custom one
    if (props.post.category && !categories.includes(props.post.category)) {
        showCustomCategory.value = true
        customCategory.value = props.post.category
    }
})

function handleCategoryChange(e) {
    if (e.target.value === 'custom') {
        showCustomCategory.value = true
        form.category = customCategory.value
    } else {
        showCustomCategory.value = false
        form.category = e.target.value
    }
}

function handleCustomCategoryInput() {
    form.category = customCategory.value
}

// Image preview state
const imagePreviewSrc = ref(props.post.image || null)
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

// HTML editor helper
const contentTextarea = ref(null)
const activeTab = ref('write')

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
    if (!form.title.trim() || !form.category.trim() || !form.content.trim()) {
        showError("Lỗi nhập liệu", "Vui lòng điền đầy đủ tiêu đề, danh mục và nội dung bài viết!");
        return;
    }
    form.post(route('admin.posts.update', props.post.id), {
        onSuccess: () => {
            showSuccess("Thành công", "Cập nhật bài viết thành công!");
        },
        onError: (errs) => {
            showError("Lỗi", Object.values(errs).join("\n"));
        }
    })
}
</script>

<template>
    <Head title="Admin - Chỉnh Sửa Bài Viết" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Chỉnh Sửa Bài Viết</h1>
                <p class="page-sub">Chỉnh sửa nội dung bài viết và lưu thay đổi lên hệ thống</p>
            </div>
        </template>

        <div class="editor-container">
            <form @submit.prevent="submit" novalidate class="editor-form">
                <div class="form-grid">
                    <!-- Left section: Form fields -->
                    <div class="form-left">
                        <div class="card">
                            <div class="form-group">
                                <label for="title" class="form-label">Tiêu đề bài viết <span class="required">*</span></label>
                                <input v-model="form.title" type="text" id="title" class="form-control" placeholder="Nhập tiêu đề hấp dẫn..." />
                                <span v-if="form.errors.title" class="error-msg">{{ form.errors.title }}</span>
                            </div>

                            <div class="form-group">
                                <label for="summary" class="form-label">Mô tả ngắn (Tóm tắt)</label>
                                <textarea v-model="form.summary" id="summary" rows="3" class="form-control" placeholder="Tóm tắt ngắn gọn nội dung bài viết..."></textarea>
                                <span v-if="form.errors.summary" class="error-msg">{{ form.errors.summary }}</span>
                            </div>

                            <!-- Content Editor with Custom Toolbar -->
                            <div class="form-group">
                                <div class="editor-header">
                                    <label class="form-label">Nội dung bài viết <span class="required">*</span></label>
                                    <div class="tab-buttons">
                                        <button type="button" :class="['tab-btn', activeTab === 'write' ? 'tab-active' : '']" @click="activeTab = 'write'">
                                            <i class="bi bi-pencil"></i> Soạn thảo
                                        </button>
                                        <button type="button" :class="['tab-btn', activeTab === 'preview' ? 'tab-active' : '']" @click="activeTab = 'preview'">
                                            <i class="bi bi-eye"></i> Xem trước
                                        </button>
                                    </div>
                                </div>

                                <!-- Editor Pane -->
                                <div v-show="activeTab === 'write'" class="editor-pane">
                                    <div class="editor-toolbar">
                                        <button type="button" class="tool-btn" @click="insertTag('<b>', '</b>')" title="In đậm">
                                            <i class="bi bi-type-bold"></i>
                                        </button>
                                        <button type="button" class="tool-btn" @click="insertTag('<i>', '</i>')" title="In nghiêng">
                                            <i class="bi bi-type-italic"></i>
                                        </button>
                                        <button type="button" class="tool-btn" @click="insertTag('<h4>', '</h4>')" title="Tiêu đề (H4)">
                                            <i class="bi bi-type-h4"></i>
                                        </button>
                                        <button type="button" class="tool-btn" @click="insertTag('<p>', '</p>')" title="Đoạn văn">
                                            <i class="bi bi-paragraph"></i>
                                        </button>
                                        <button type="button" class="tool-btn" @click="insertTag('<ul>\n  <li>', '</li>\n</ul>')" title="Danh sách không thứ tự">
                                            <i class="bi bi-list-ul"></i>
                                        </button>
                                        <button type="button" class="tool-btn" @click="insertTag('  <li>', '</li>\n')" title="Mục danh sách">
                                            <i class="bi bi-list-task"></i>
                                        </button>
                                        <button type="button" class="tool-btn" @click="insertTag('<a href=\x22#\x22 target=\x22_blank\x22>', '</a>')" title="Liên kết URL">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>
                                    </div>
                                    <textarea 
                                        ref="contentTextarea" 
                                        v-model="form.content" 
                                        rows="15" 
                                        class="editor-textarea" 
                                        placeholder="Nhập nội dung chi tiết bài viết dưới dạng HTML..."
                                        required
                                    ></textarea>
                                </div>

                                <!-- Preview Pane -->
                                <div v-show="activeTab === 'preview'" class="preview-pane">
                                    <div v-if="form.content" class="post-content-body" v-html="form.content"></div>
                                    <div v-else class="preview-empty text-gray">Nội dung xem trước trống. Hãy viết điều gì đó...</div>
                                </div>
                                <span v-if="form.errors.content" class="error-msg">{{ form.errors.content }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right section: Settings card -->
                    <div class="form-right">
                        <div class="card">
                            <h3 class="card-title">Cấu hình bài đăng</h3>

                            <div class="form-group">
                                <label for="category" class="form-label">Danh mục <span class="required">*</span></label>
                                <select id="category" class="form-control" :value="showCustomCategory ? 'custom' : form.category" @change="handleCategoryChange">
                                    <option v-for="cat in categories" :key="cat" :value="cat">
                                        {{ cat }}
                                    </option>
                                    <option value="custom">-- Danh mục tùy chỉnh --</option>
                                </select>

                                <!-- Custom input if custom selected -->
                                <div v-if="showCustomCategory" class="custom-category-group mt-2">
                                    <input 
                                        v-model="customCategory" 
                                        type="text" 
                                        class="form-control" 
                                        placeholder="Nhập danh mục mới..." 
                                        @input="handleCustomCategoryInput"
                                        required
                                    />
                                </div>
                                <span v-if="form.errors.category" class="error-msg">{{ form.errors.category }}</span>
                            </div>

                            <div class="form-group">
                                <label for="tags" class="form-label">Từ khóa (Tags)</label>
                                <input v-model="form.tags" type="text" id="tags" class="form-control" placeholder="Ninh Binh, Du Lich, Homestay..." />
                                <small class="form-hint">Phân tách các từ khóa bằng dấu phẩy</small>
                                <span v-if="form.errors.tags" class="error-msg">{{ form.errors.tags }}</span>
                            </div>

                            <!-- Cover Image Selectors -->
                            <div class="form-group">
                                <label class="form-label">Ảnh bìa bài viết</label>
                                <div class="image-selector-tabs">
                                    <div class="image-upload-area">
                                        <label for="image_file" class="image-upload-label">
                                            <i class="bi bi-cloud-arrow-up"></i> Chọn file ảnh mới
                                        </label>
                                        <input type="file" id="image_file" accept="image/*" @change="handleFileChange" class="hidden-input" />
                                    </div>
                                    
                                    <div class="divider-text">hoặc dán đường dẫn ảnh mới</div>
                                    
                                    <input v-model="form.image_url" type="url" class="form-control" placeholder="https://example.com/image.jpg" />
                                </div>
                                <span v-if="form.errors.image_file" class="error-msg">{{ form.errors.image_file }}</span>
                            </div>

                            <!-- Image Preview Thumbnail -->
                            <div class="form-group" v-if="imagePreviewSrc || form.image_url">
                                <label class="form-label">Xem trước ảnh bìa</label>
                                <div class="preview-thumb-container">
                                    <img :src="imagePreviewSrc || form.image_url" class="preview-thumb-img" alt="Cover preview" />
                                </div>
                            </div>
                        </div>

                        <!-- Actions block -->
                        <div class="actions-card mt-3">
                            <button type="submit" :disabled="form.processing" class="btn-submit">
                                <i class="bi bi-check2-circle"></i> Lưu bài viết
                            </button>
                            <Link :href="route('admin.posts.index')" class="btn-cancel-link">Hủy bỏ</Link>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.page-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }
.page-sub   { font-size: 12px; color: #94a3b8; margin: 2px 0 0; }

.editor-container { margin-top: 20px; }
.form-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start; }

@media (max-width: 992px) {
    .form-grid { grid-template-columns: 1fr; }
}

.card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
.card-title { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }

.form-group { margin-bottom: 20px; }
.form-group:last-child { margin-bottom: 0; }
.form-label { display: block; font-size: 13.5px; font-weight: 600; color: #334155; margin-bottom: 8px; }
.required { color: #ef4444; }

.form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13.5px; color: #0f172a; outline: none; background: #fff; box-sizing: border-box; }
.form-control:focus { border-color: #166ea9; box-shadow: 0 0 0 3px rgba(22, 110, 169, 0.08); }
textarea.form-control { resize: vertical; }

.form-hint { font-size: 11px; color: #94a3b8; display: block; margin-top: 4px; }
.error-msg { font-size: 12px; color: #ef4444; display: block; margin-top: 6px; font-weight: 500; }

/* Editor custom styling */
.editor-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.tab-buttons { display: flex; gap: 4px; background: #f1f5f9; padding: 3px; border-radius: 8px; }
.tab-btn { padding: 6px 12px; border: none; background: none; font-size: 12px; font-weight: 600; color: #64748b; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px; }
.tab-active { background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

.editor-pane { border: 1px solid #cbd5e1; border-radius: 12px; overflow: hidden; background: #fff; }
.editor-toolbar { display: flex; gap: 4px; padding: 8px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; }
.tool-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #475569; font-size: 14px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.tool-btn:hover { background: #f1f5f9; color: #166ea9; border-color: #166ea9; }

.editor-textarea { width: 100%; border: none; outline: none; padding: 14px; font-family: 'Courier New', Courier, monospace; font-size: 14px; color: #0f172a; background: #fff; resize: vertical; box-sizing: border-box; }

.preview-pane { border: 1px solid #cbd5e1; border-radius: 12px; padding: 20px; background: #f8fafc; min-height: 350px; max-height: 480px; overflow-y: auto; box-sizing: border-box; }
.preview-empty { display: flex; align-items: center; justify-content: center; height: 300px; font-style: italic; }

/* Preview rendered html formatting */
.post-content-body { font-size: 15px; line-height: 1.6; color: #334155; }
.post-content-body :deep(h4) { font-size: 17px; font-weight: 700; color: #0f172a; margin-top: 20px; margin-bottom: 10px; }
.post-content-body :deep(p) { margin-bottom: 12px; }
.post-content-body :deep(ul) { padding-left: 20px; margin-bottom: 12px; list-style-type: disc; }
.post-content-body :deep(li) { margin-bottom: 4px; }
.post-content-body :deep(a) { color: #166ea9; text-decoration: underline; }

/* Image Upload UI */
.image-selector-tabs { background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #cbd5e1; display: flex; flex-direction: column; gap: 10px; }
.image-upload-area { text-align: center; }
.image-upload-label { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: 1px dashed #cbd5e1; border-radius: 10px; background: #fff; cursor: pointer; font-size: 13px; font-weight: 600; color: #475569; transition: all 0.15s; }
.image-upload-label:hover { border-color: #166ea9; color: #166ea9; background: rgba(22, 110, 169, 0.02); }
.hidden-input { display: none; }
.divider-text { text-align: center; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin: 4px 0; }

.preview-thumb-container { border-radius: 10px; overflow: hidden; border: 1px solid #cbd5e1; margin-top: 6px; }
.preview-thumb-img { width: 100%; height: auto; display: block; max-height: 160px; object-fit: cover; }

/* Actions Card */
.actions-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; padding: 16px; display: flex; flex-direction: column; gap: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
.btn-submit { width: 100%; padding: 12px; border: none; border-radius: 10px; background: #166ea9; color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; transition: background 0.15s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-submit:hover { background: #0f4f7a; }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-cancel-link { text-align: center; font-size: 13.5px; font-weight: 600; color: #64748b; text-decoration: none; display: block; padding: 6px; }
.btn-cancel-link:hover { color: #ef4444; }

.mt-2 { margin-top: 8px; }
.mt-3 { margin-top: 12px; }
</style>
