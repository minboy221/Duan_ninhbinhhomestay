<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

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
    replied: 'Đã phản hồi',
    spam: 'Spam'
}

const categoryLabels = {
    general: 'Góp ý / Yêu cầu chung',
    consultation: 'Tư vấn & Đặt phòng',
    technical: 'Báo lỗi & Kỹ thuật',
    partnership: 'Hợp tác cho thuê'
}

const statusBadgeClasses = {
    pending: 'bg-amber-50 text-amber-600 border border-amber-200',
    read: 'bg-blue-50 text-blue-600 border border-blue-200',
    replied: 'bg-emerald-50 text-emerald-600 border border-emerald-200',
    spam: 'bg-rose-50 text-rose-600 border border-rose-200'
}

// Quick Reply Templates
const quickTemplates = [
    { title: 'Xác nhận tư vấn', text: 'Chào bạn,\nCảm ơn bạn đã quan tâm đến dịch vụ của Ninh Bình HomeStay. Chúng tôi đã nhận được yêu cầu tư vấn đặt phòng của bạn và sẽ liên hệ hỗ trợ bạn trong thời gian sớm nhất.\nTrân trọng!' },
    { title: 'Tiếp nhận hỗ trợ kỹ thuật', text: 'Chào bạn,\nCảm ơn bạn đã phản hồi. Yêu cầu hỗ trợ kỹ thuật/báo lỗi của bạn đã được chuyển đến bộ phận liên quan để xử lý. Chúng tôi sẽ thông báo lại ngay khi hoàn tất.\nTrân trọng!' },
    { title: 'Cảm ơn đóng góp', text: 'Chào bạn,\nCảm ơn bạn đã gửi ý kiến đóng góp cho Ninh Bình HomeStay. Những góp ý của bạn rất quý giá giúp chúng tôi nâng cao chất lượng dịch vụ tốt hơn mỗi ngày.\nTrân trọng!' }
]

const applyTemplate = (text) => {
    replyForm.reply_message = text
}

// Modal State
const showViewModal = ref(false)
const selectedContact = ref(null)

const openViewModal = (contact) => {
    selectedContact.value = contact
    showViewModal.value = true

    // Reset reply form message
    replyForm.reset()

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
        }
    })
}

// Delete Logic
const deleteContact = (contact) => {
    showConfirm('Xác nhận xóa', `Bạn có chắc chắn muốn xóa tin nhắn của <strong>${contact.name}</strong>? Hành động này sẽ không thể hoàn tác.`, 'danger', () => {
        router.delete(route('admin.contacts.delete', contact.id), {
            onSuccess: () => {
                showViewModal.value = false
            }
        })
    })
}

// Email Reply Form
const replyForm = useForm({
    reply_message: ''
})

const sendReply = () => {
    if (!replyForm.reply_message.trim()) return
    replyForm.post(route('admin.contacts.reply', selectedContact.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedContact.value) {
                selectedContact.value.status = 'replied'
            }
            replyForm.reset()
        }
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

// Watch Flash Messages
const page = usePage()
watch(() => page.props.flash, (flash) => {
    if (flash && flash.error) {
        showAlert('Lỗi', flash.error, 'danger')
    } else if (flash && flash.success) {
        showAlert('Thành công', flash.success, 'success')
    }
}, { deep: true })

// Filter & Pagination State
const activeTab = ref('all')
const currentPage = ref(1)
const perPage = 10

const filteredContacts = computed(() => {
    if (activeTab.value === 'all') {
        return props.contacts
    }
    return props.contacts.filter(c => c.status === activeTab.value)
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredContacts.value.length / perPage)))
const paginatedContacts = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return filteredContacts.value.slice(start, start + perPage)
})

watch(activeTab, () => {
    currentPage.value = 1
})
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
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

                <!-- Spam -->
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Thư rác / Spam</span>
                        <h3 class="text-2xl font-extrabold text-rose-500">
                            {{ contacts.filter(c => c.status === 'spam').length }}
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center text-xl border border-rose-200">
                        <i class="bi bi-shield-fill-x"></i>
                    </div>
                </div>
            </div>

            <!-- Table section -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Danh sách liên hệ</h3>
                    
                    <!-- Status classification tabs -->
                    <div class="flex items-center gap-1.5 overflow-x-auto">
                        <button 
                            v-for="tab in ['all', 'pending', 'read', 'replied', 'spam']" 
                            :key="tab"
                            @click="activeTab = tab"
                            :class="[
                                'px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-xl transition-all border',
                                activeTab === tab 
                                    ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-500/20' 
                                    : 'bg-white border-slate-200 hover:bg-slate-100 text-slate-500'
                            ]"
                        >
                            {{ tab === 'all' ? 'Tất cả' : statusLabels[tab] }}
                            <span class="ml-1 text-[9px] px-1 bg-slate-200/50 text-slate-600 rounded-full font-bold" :class="{'!text-white !bg-white/20': activeTab === tab}">
                                {{ tab === 'all' ? contacts.length : contacts.filter(c => c.status === tab).length }}
                            </span>
                        </button>
                    </div>
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
                                v-for="contact in paginatedContacts" 
                                :key="contact.id" 
                                class="hover:bg-slate-50/50 transition-colors"
                                :class="{ 'bg-amber-50/10 font-medium': contact.status === 'pending' }"
                            >
                                <!-- Customer Info -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 text-sm">{{ contact.name }}</span>
                                            <span v-if="contact.ticket_code" class="text-[9px] font-mono font-bold px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded border border-slate-200">
                                                {{ contact.ticket_code }}
                                            </span>
                                        </div>
                                        <span class="text-slate-400 font-semibold">{{ contact.email }}</span>
                                        <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                            <span v-if="contact.phone">SĐT: {{ contact.phone }}</span>
                                            <span v-if="contact.user" class="text-blue-600 font-bold bg-blue-50 px-1.5 py-0.2 rounded">
                                                <i class="bi bi-person-check-fill"></i> Thành viên
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Subject & Category -->
                                <td class="px-6 py-4 max-w-xs">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">
                                            {{ categoryLabels[contact.category] || 'Góp ý / Yêu cầu chung' }}
                                        </span>
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
                                            v-if="contact.status !== 'replied' && contact.status !== 'spam'" 
                                            @click="updateStatus(contact, 'replied')" 
                                            class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-xl transition-colors border border-emerald-200/50"
                                            title="Đánh dấu đã phản hồi"
                                        >
                                            <i class="bi bi-check-lg"></i>
                                        </button>

                                        <button 
                                            v-if="contact.status !== 'spam'" 
                                            @click="updateStatus(contact, 'spam')" 
                                            class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-500 rounded-xl transition-colors border border-rose-200/50"
                                            title="Đánh dấu là Spam"
                                        >
                                            <i class="bi bi-shield-slash-fill"></i>
                                        </button>

                                        <button 
                                            v-if="contact.status === 'spam'" 
                                            @click="updateStatus(contact, 'read')" 
                                            class="p-2 bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-xl transition-colors border border-slate-200/50"
                                            title="Khôi phục thư liên hệ"
                                        >
                                            <i class="bi bi-arrow-counterclockwise"></i>
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

                <!-- Client-side Pagination -->
                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50" v-if="totalPages > 1">
                    <span class="text-xs text-slate-500 font-semibold">Trang {{ currentPage }} / {{ totalPages }} (Tổng {{ contacts.length }} liên hệ)</span>
                    <div class="flex items-center gap-1">
                        <button 
                            @click="currentPage > 1 && (currentPage--)" 
                            :disabled="currentPage === 1"
                            class="px-3 py-1.5 border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 text-slate-600 font-bold text-xs rounded-xl transition-all"
                        >
                            Trước
                        </button>
                        <button 
                            v-for="page in totalPages" 
                            :key="page" 
                            @click="currentPage = page" 
                            :class="[
                                'px-3 py-1.5 font-bold text-xs rounded-xl transition-all',
                                currentPage === page ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'border border-slate-200 bg-white hover:bg-slate-50 text-slate-600'
                            ]"
                        >
                            {{ page }}
                        </button>
                        <button 
                            @click="currentPage < totalPages && (currentPage++)" 
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1.5 border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-50 text-slate-600 font-bold text-xs rounded-xl transition-all"
                        >
                            Sau
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Contact Details Modal -->
        <Teleport to="body">
            <div v-if="showViewModal && selectedContact" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showViewModal = false">
                <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Head -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-slate-800">Chi Tiết Thư Liên Hệ</h3>
                            <span v-if="selectedContact.ticket_code" class="text-[11px] font-mono font-bold px-2 py-0.5 bg-blue-50 text-blue-600 rounded border border-blue-200">
                                {{ selectedContact.ticket_code }}
                            </span>
                        </div>
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
                                <span v-if="selectedContact.user" class="block text-[10px] text-blue-600 font-bold mt-0.5">
                                    <i class="bi bi-person-check-fill"></i> TK: {{ selectedContact.user.name }}
                                </span>
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
                                <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">Phân loại yêu cầu</span>
                                <span class="text-xs font-bold text-emerald-600">{{ categoryLabels[selectedContact.category] || 'Góp ý / Yêu cầu chung' }}</span>
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

                        <!-- Email Reply Section -->
                        <div v-if="selectedContact.email" class="border-t border-slate-100 pt-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">Phản hồi qua Email khách hàng</span>
                            </div>

                            <!-- Quick Reply Templates -->
                            <div class="flex items-center gap-1.5 flex-wrap bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                <span class="text-[10px] text-slate-400 font-bold uppercase">Mẫu phản hồi nhanh:</span>
                                <button 
                                    v-for="(tpl, idx) in quickTemplates" 
                                    :key="idx" 
                                    type="button"
                                    @click="applyTemplate(tpl.text)"
                                    class="px-2.5 py-1 text-[10px] font-bold bg-white hover:bg-blue-50 hover:text-blue-600 text-slate-600 rounded-lg transition-colors border border-slate-200 shadow-sm"
                                >
                                    {{ tpl.title }}
                                </button>
                            </div>

                            <div class="space-y-2">
                                <textarea 
                                    v-model="replyForm.reply_message" 
                                    rows="4" 
                                    class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-semibold outline-none transition-all resize-none leading-relaxed" 
                                    placeholder="Nhập nội dung phản hồi gửi trực tiếp đến email của khách hàng..."
                                    :disabled="replyForm.processing"
                                ></textarea>
                                <div class="flex justify-end">
                                    <button 
                                        @click="sendReply" 
                                        :disabled="replyForm.processing || !replyForm.reply_message.trim()"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center gap-1.5"
                                    >
                                        <span v-if="replyForm.processing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                        <i class="bi bi-send-fill" v-else></i>
                                        Gửi Phản Hồi
                                    </button>
                                </div>
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
                                    v-if="selectedContact.status !== 'replied' && selectedContact.status !== 'spam'"
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
                                <button 
                                    v-if="selectedContact.status !== 'spam'"
                                    @click="updateStatus(selectedContact, 'spam')" 
                                    class="px-3 py-1.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-[10px] rounded-lg transition-colors flex items-center gap-1"
                                >
                                    <i class="bi bi-shield-slash"></i> Đánh dấu Spam
                                </button>
                                <button 
                                    v-if="selectedContact.status === 'spam'"
                                    @click="updateStatus(selectedContact, 'read')" 
                                    class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white font-bold text-[10px] rounded-lg transition-colors flex items-center gap-1"
                                >
                                    <i class="bi bi-arrow-counterclockwise"></i> Khôi phục
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
