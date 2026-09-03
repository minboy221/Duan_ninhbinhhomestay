<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

import { showSuccess, showError, showConfirm } from '@/Utils/swal'

const props = defineProps({
    reasons: Array
})

const showModal = ref(false)
const isEditMode = ref(false)
const editingId = ref(null)

const form = useForm({
    reason: '',
    is_active: true
})

function openCreateModal() {
    isEditMode.value = false
    editingId.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

function openEditModal(item) {
    isEditMode.value = true
    editingId.value = item.id
    form.reason = item.reason
    form.is_active = !!item.is_active
    form.clearErrors()
    showModal.value = true
}

function submitForm() {
    if (isEditMode.value) {
        form.put(route('admin.report-reasons.update', editingId.value), {
            onSuccess: () => {
                showModal.value = false
                showSuccess('Thành công', 'Cập nhật lý do thành công!')
            },
            onError: (errors) => {
                showError('Lỗi', Object.values(errors).join('\n'))
            }
        })
    } else {
        form.post(route('admin.report-reasons.store'), {
            onSuccess: () => {
                showModal.value = false
                showSuccess('Thành công', 'Thêm lý do mới thành công!')
            },
            onError: (errors) => {
                showError('Lỗi', Object.values(errors).join('\n'))
            }
        })
    }
}

async function deleteItem(id) {
    const confirmed = await showConfirm(
        'Xác nhận xóa',
        'Bạn có chắc chắn muốn xóa lý do báo cáo này không?',
        'Xóa',
        'Hủy'
    )
    if (confirmed) {
        form.delete(route('admin.report-reasons.destroy', id), {
            onSuccess: () => {
                showSuccess('Thành công', 'Xóa lý do thành công!')
            },
            onError: (errors) => {
                showError('Lỗi', Object.values(errors).join('\n'))
            }
        })
    }
}
</script>

<template>
    <Head title="Quản Lý Lý Do Báo Cáo" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="text-lg font-bold text-slate-800">Quản Lý Lý Do Báo Cáo</h1>
                <p class="text-xs text-slate-400 mt-1">Quản lý danh sách các lý do vi phạm hiển thị ở Client</p>
            </div>
        </template>

        <div class="p-6 bg-white rounded-lg border border-slate-200">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-sm text-slate-700">Danh sách lý do</h3>
                <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition">
                    + Thêm lý do mới
                </button>
            </div>

            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-3 font-bold text-slate-700 w-16">STT</th>
                        <th class="p-3 font-bold text-slate-700">Lý do báo cáo</th>
                        <th class="p-3 font-bold text-slate-700 w-32 text-center">Trạng thái</th>
                        <th class="p-3 font-bold text-slate-700 w-48 text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!reasons.length">
                        <td colspan="4" class="p-6 text-center text-slate-400 font-semibold">Chưa có lý do báo cáo nào.</td>
                    </tr>
                    <tr v-for="(item, idx) in reasons" :key="item.id" class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="p-3">{{ idx + 1 }}</td>
                        <td class="p-3 font-semibold text-slate-800">{{ item.reason }}</td>
                        <td class="p-3 text-center">
                            <span :class="['px-2.5 py-1 rounded-full text-[10px] font-bold', item.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700']">
                                {{ item.is_active ? 'Kích hoạt' : 'Khóa' }}
                            </span>
                        </td>
                        <td class="p-3 text-center space-x-2">
                            <button @click="openEditModal(item)" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded text-xs font-bold transition">
                                Sửa
                            </button>
                            <button @click="deleteItem(item.id)" class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 rounded text-xs font-bold transition">
                                Xóa
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal Thêm/Sửa -->
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200">
                <h3 class="text-sm font-bold text-slate-800 mb-4">{{ isEditMode ? 'Cập Nhật Lý Do' : 'Thêm Lý Do Mới' }}</h3>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-2">Nội dung lý do báo cáo</label>
                        <input v-model="form.reason" type="text" class="w-full border rounded-lg p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 outline-none" required placeholder="Nhập lý do ví dụ: Chủ trọ lừa tiền cọc..." />
                        <p v-if="form.errors.reason" class="text-red-500 text-xs mt-1">{{ form.errors.reason }}</p>
                    </div>

                    <div v-if="isEditMode" class="flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox" id="is_active_checkbox" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500" />
                        <label for="is_active_checkbox" class="text-xs font-bold text-slate-600 select-none cursor-pointer">Kích hoạt hiển thị ở client</label>
                    </div>

                    <div class="flex justify-end gap-2 border-t pt-3 mt-4">
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-bold transition">Hủy</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition">{{ form.processing ? 'Đang gửi...' : 'Lưu lại' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
