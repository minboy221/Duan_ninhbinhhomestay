<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    contacts: {
        type: Array,
        default: () => []
    }
})

// Status definitions
const statusLabels = {
    pending: 'Chưa đọc',
    read: 'Đã đọc',
    replied: 'Đã phản hồi'
}

const statusBadgeClasses = {
    pending: 'bg-amber-50 text-amber-600 border border-amber-200',
    read: 'bg-blue-50 text-blue-600 border border-blue-200',
    replied: 'bg-emerald-50 text-emerald-600 border border-emerald-200'
}

// Modal State
const showViewModal = ref(false)
const selectedContact = ref(null)

const openViewModal = (contact) => {
    selectedContact.value = contact
    showViewModal.value = true

    // If contact is pending, automatically mark as read when viewed
    if (contact.status === 'pending') {
        router.patch(route('admin.contacts.status', contact.id), {
            status: 'read'
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Update local state in modal if still open
                if (selectedContact.value && selectedContact.value.id === contact.id) {
                    selectedContact.value.status = 'read'
                }
            }
        })
    }
}

// Form logic to change status manually
const updateStatus = (contact, newStatus) => {
    router.patch(route('admin.contacts.status', contact.id), {
        status: newStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedContact.value && selectedContact.value.id === contact.id) {
                selectedContact.value.status = newStatus
            }
            showAlert('Thành công', 'Cập nhật trạng thái liên hệ thành công!', 'success')
        }
    })
}

// Delete Logic
const deleteContact = (contact) => {
    showConfirm('Xác nhận xóa', `Bạn có chắc chắn muốn xóa tin nhắn của <strong>${contact.name}</strong>? Hành động này sẽ không thể hoàn tác.`, 'danger', () => {
        router.delete(route('admin.contacts.delete', contact.id), {
            onSuccess: () => {
                showViewModal.value = false
                showAlert('Thành công', 'Xóa thư liên hệ thành công!', 'success')
            }
        })
    })
}

// Custom Alerts & Confirms
const confirmModal = ref({ show: false, title: '', message: '', type: 'danger', onConfirm: null, isAlert: false })
const showConfirm = (title, message, type, onConfirm) => { confirmModal.value = { show: true, title, message, type, onConfirm, isAlert: false } }
const showAlert = (title, message, type) => { confirmModal.value = { show: true, title, message, type, onConfirm: null, isAlert: true } }

const handleConfirm = () => { 
    if (confirmModal.value.onConfirm) {
        confirmModal.value.onConfirm();
    }
    confirmModal.value.show = false 
}

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>

<template>
    <Head title="Admin - Quản Lý Liên Hệ" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="header-page-title">Quản Lý Thư Liên Hệ</h1>
                <p class="header-page-sub">Xem và phản hồi các thông tin liên hệ từ khách hàng gửi qua form website.</p>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats overview cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Tổng thư nhận</span>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ contacts.length }}</h3>
                    </div>
                    <div class="w-12 h-12 bg-slate-50 text-slate-500 rounded-xl flex items-center justify-center text-xl border border-slate-100">
                        <i class="bi bi-inboxes-fill"></i>
                    </div>
                </div>

                <!-- Unread -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Thư chưa đọc</span>
                        <h3 class="text-2xl font-extrabold text-amber-500">
                            {{ contacts.filter(c => c.status === 'pending').length }}
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center text-xl border border-amber-200">
                        <i class="bi bi-envelope-exclamation-fill"></i>
                    </div>
                </div>

                <!-- Replied -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Đã phản hồi</span>
                        <h3 class="text-2xl font-extrabold text-emerald-500">
                            {{ contacts.filter(c => c.status === 'replied').length }}
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-xl flex items-center justify-center text-xl border border-emerald-200">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>

            <!-- Table section -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Danh sách liên hệ</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/20 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                                <th class="px-6 py-4">Khách hàng</th>
                                <th class="px-6 py-4">Tiêu đề / Nội dung</th>
                                <th class="px-6 py-4">Thời gian</th>
                                <th class="px-6 py-4">Trạng thái</th>
                                <th class="px-6 py-4 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr 
                                v-for="contact in contacts" 
                                :key="contact.id" 
                                class="hover:bg-slate-50/50 transition-colors"
                                :class="{ 'bg-amber-50/10 font-medium': contact.status === 'pending' }"
                            >
                                <!-- Customer Info -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-bold text-slate-800 text-sm">{{ contact.name }}</span>
                                        <span class="text-slate-400 font-semibold">{{ contact.email }}</span>
                                        <span class="text-[10px] text-slate-400" v-if="contact.phone">SĐT: {{ contact.phone }}</span>
                                    </div>
                                </td>

                                <!-- Subject & Preview -->
                                <td class="px-6 py-4 max-w-xs">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-bold text-slate-700 truncate">{{ contact.subject || 'Không có tiêu đề' }}</span>
                                        <span class="text-slate-400 truncate text-[11px]">{{ contact.message }}</span>
                                    </div>
                                </td>

                                <!-- Time -->
                                <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                    {{ formatDate(contact.created_at) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2.5 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider', statusBadgeClasses[contact.status]]">
                                        {{ statusLabels[contact.status] }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button 
                                            @click="openViewModal(contact)" 
                                            class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-xl transition-colors border border-slate-200/50"
                                            title="Xem chi tiết"
                                        >
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                        
                                        <button 
                                            v-if="contact.status !== 'replied'" 
                                            @click="updateStatus(contact, 'replied')" 
                                            class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-xl transition-colors border border-emerald-200/50"
                                            title="Đánh dấu đã phản hồi"
                                        >
                                            <i class="bi bi-check-lg"></i>
                                        </button>

                                        <button 
                                            @click="deleteContact(contact)" 
                                            class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-500 rounded-xl transition-colors border border-rose-200/50"
                                            title="Xóa thư"
                                        >
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="contacts.length === 0">
                                <td colspan="5" class="py-12 text-center">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 text-2xl mx-auto mb-4 border border-slate-100">
                                        <i class="bi bi-envelope-open"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-700 mb-1">Chưa nhận được liên hệ nào</h4>
                                    <p class="text-xs text-slate-400 max-w-sm mx-auto">Thư liên hệ gửi từ trang chủ và trang liên hệ của khách hàng sẽ xuất hiện tại đây.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View Contact Details Modal -->
        <Teleport to="body">
            <div v-if="showViewModal && selectedContact" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showViewModal = false">
                <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Head -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">Chi Tiết Thư Liên Hệ</h3>
                        <button @click="showViewModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-5 overflow-y-auto flex-1 text-xs">
                        <!-- Top Metadata -->
                        <div class="grid grid-cols-2 gap-4 bg-slate-50 rounded-2xl p-4 border border-slate-100">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">Tên khách hàng</span>
                                <span class="text-sm font-bold text-slate-800">{{ selectedContact.name }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">Thời gian nhận</span>
                                <span class="text-xs font-semibold text-slate-700">{{ formatDate(selectedContact.created_at) }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">Email liên hệ</span>
                                <span class="text-xs font-semibold text-blue-600 underline">{{ selectedContact.email }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">Số điện thoại</span>
                                <span class="text-xs font-semibold text-slate-700">{{ selectedContact.phone || 'Chưa cập nhật' }}</span>
                            </div>
                        </div>

                        <!-- Subject -->
                        <div class="space-y-1.5">
                            <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">Tiêu đề thư</span>
                            <div class="text-sm font-bold text-slate-800 bg-slate-50/50 px-4 py-2.5 rounded-xl border border-slate-100">
                                {{ selectedContact.subject || '(Không có chủ đề)' }}
                            </div>
                        </div>

                        <!-- Message Content -->
                        <div class="space-y-1.5">
                            <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">Nội dung liên hệ</span>
                            <div class="text-xs text-slate-600 bg-slate-50/50 p-4 rounded-2xl border border-slate-100 whitespace-pre-wrap leading-relaxed">
                                {{ selectedContact.message }}
                            </div>
                        </div>

                        <!-- Status Controls inside details -->
                        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400 font-bold uppercase tracking-wider text-[10px]">Trạng thái:</span>
                                <span :class="['px-2 py-0.5 rounded-full font-bold uppercase text-[9px]', statusBadgeClasses[selectedContact.status]]">
                                    {{ statusLabels[selectedContact.status] }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button 
                                    v-if="selectedContact.status !== 'replied'"
                                    @click="updateStatus(selectedContact, 'replied')" 
                                    class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-[10px] rounded-lg transition-colors flex items-center gap-1"
                                >
                                    <i class="bi bi-check-lg"></i> Đã phản hồi
                                </button>
                                <button 
                                    v-if="selectedContact.status === 'replied'"
                                    @click="updateStatus(selectedContact, 'read')" 
                                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-[10px] rounded-lg transition-colors"
                                >
                                    Chuyển thành Đã đọc
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Foot -->
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <button 
                            @click="deleteContact(selectedContact)" 
                            class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs rounded-xl transition-colors flex items-center gap-1"
                        >
                            <i class="bi bi-trash"></i> Xóa thư này
                        </button>
                        <button 
                            class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" 
                            @click="showViewModal = false"
                        >
                            Đóng
                        </button>
                    </div>
                </div>
            </div>

            <!-- Custom Confirm Modal -->
            <div v-if="confirmModal.show" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[100] p-4" @click.self="confirmModal.show = false">
                <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden text-center transform transition-all">
                    <div class="p-6">
                        <div :class="[
                            'w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4',
                            confirmModal.type === 'danger' ? 'bg-rose-50 text-rose-500' : 
                            confirmModal.type === 'success' ? 'bg-emerald-50 text-emerald-500' : 'bg-amber-50 text-amber-500'
                        ]">
                            <i :class="['bi text-2xl', 
                                confirmModal.type === 'danger' ? 'bi-trash-fill' : 
                                confirmModal.type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'
                            ]"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">{{ confirmModal.title }}</h3>
                        <p class="text-sm text-slate-500" v-html="confirmModal.message"></p>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3">
                        <button v-if="!confirmModal.isAlert" @click="confirmModal.show = false" class="flex-1 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-all">Hủy</button>
                        <button v-if="!confirmModal.isAlert" @click="handleConfirm" :class="[
                            'flex-1 px-4 py-2.5 text-white font-bold text-xs rounded-xl transition-all shadow-md',
                            confirmModal.type === 'danger' ? 'bg-rose-500 hover:bg-rose-600 shadow-rose-500/20' : 
                            confirmModal.type === 'success' ? 'bg-emerald-500 hover:bg-emerald-600 shadow-emerald-500/20' : 
                            'bg-amber-500 hover:bg-amber-600 shadow-amber-500/20'
                        ]">Xác nhận</button>
                        <button v-if="confirmModal.isAlert" @click="confirmModal.show = false" class="flex-1 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 shadow-emerald-500/20 text-white font-bold text-xs rounded-xl transition-all shadow-md">OK</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.header-page-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.75rem;
    margin: 0;
}
.header-page-sub {
    font-size: 0.75rem;
    color: #94a3b8;
    margin: 0.25rem 0 0 0;
    font-weight: 500;
}
</style>
