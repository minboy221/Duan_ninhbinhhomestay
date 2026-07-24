<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref,computed  } from 'vue'
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    services: {
        type: Array,
        default: () => []
    }
})

const fmtMoney = (n) => new Intl.NumberFormat('vi-VN').format(n)+'đ'

// --- SERVICES STATE & LOGIC ---

const typeLabels = {
    per_kwh: 'Theo chỉ số điện (kWh)',
    per_m3: 'Theo chỉ số nước (m³)',
    fixed: 'Cố định mỗi phòng',
    per_person: 'Tính theo đầu người'
}

const typeUnits = {
    per_kwh: 'kWh',
    per_m3: 'm³',
    fixed: 'Phòng',
    per_person: 'Người'
}

const colorsConfig = {
    amber: { text: 'text-amber-500', bg: 'bg-amber-50', border: 'border-amber-200' },
    blue: { text: 'text-blue-500', bg: 'bg-blue-50', border: 'border-blue-200' },
    cyan: { text: 'text-cyan-500', bg: 'bg-cyan-50', border: 'border-cyan-200' },
    rose: { text: 'text-rose-500', bg: 'bg-rose-50', border: 'border-rose-200' },
    purple: { text: 'text-purple-500', bg: 'bg-purple-50', border: 'border-purple-200' },
    emerald: { text: 'text-emerald-500', bg: 'bg-emerald-50', border: 'border-emerald-200' }
}

const iconsList = [
    'bi-lightning-charge-fill',
    'bi-droplet-fill',
    'bi-wifi',
    'bi-trash-fill',
    'bi-bicycle',
    'bi-shield-lock-fill',
    'bi-wrench',
    'bi-tv-fill',
    'bi-snow',
    'bi-house-heart-fill'
]

// Modal Form for individual service
const showServiceModal = ref(false)
const isEditService = ref(false)
const selectedService = ref(null)

const serviceForm = useForm({
    name: '',
    price: 0,
    type: 'fixed',
    icon: 'bi-lightning-charge-fill',
    color: 'emerald',
    description: ''
})

const displayServicePrice = computed({
    get() {
        if (serviceForm.price === null || serviceForm.price === undefined || serviceForm.price === '') return ''
        return new Intl.NumberFormat('en-US').format(serviceForm.price)
    },
    set(val) {
        const raw = String(val).replace(/\D/g, '')
        serviceForm.price = raw ? parseInt(raw, 10) : 0
    }
})

const openAddService = () => {
    isEditService.value = false
    selectedService.value = null
    serviceForm.reset()
    serviceForm.clearErrors()
    serviceForm.icon = 'bi-lightning-charge-fill'
    serviceForm.color = 'emerald'
    serviceForm.type = 'fixed'
    showServiceModal.value = true
}

const openEditService = (srv) => {
    isEditService.value = true
    selectedService.value = srv
    serviceForm.name = srv.name
    serviceForm.price = srv.price
    serviceForm.type = srv.type
    serviceForm.icon = srv.icon || 'bi-lightning-charge-fill'
    serviceForm.color = srv.color || 'emerald'
    serviceForm.description = srv.description || ''
    serviceForm.clearErrors()
    showServiceModal.value = true
}

const submitService = () => {
    if(isEditService.value && selectedService.value) {
        serviceForm.put(route('landlord.services.update', selectedService.value.id), {
            onSuccess: () => {
                showServiceModal.value = false
                showAlert('Thành công', 'Cập nhật thông tin dịch vụ thành công!', 'success')
            }
        })
    } else {
        serviceForm.post(route('landlord.services.store'), {
            onSuccess: () => {
                showServiceModal.value = false
                showAlert('Thành công', 'Thêm dịch vụ mới thành công!', 'success')
            }
        })
    }
}

const deleteService = (srv) => {
    showConfirm('Xác nhận xóa', `Xóa dịch vụ "<strong>${srv.name}</strong>"? Hành động này sẽ không thể hoàn tác.`, 'danger', () => {
        router.delete(route('landlord.services.delete', srv.id))
    })
}

const toggleStatus = (srv) => {
    const action = srv.is_active ? 'khóa' : 'mở khóa'
    const type = srv.is_active ? 'warning' : 'success'
    showConfirm('Xác nhận', `Bạn có chắc muốn ${action} dịch vụ "<strong>${srv.name}</strong>"?`, type, () => {
        router.patch(route('landlord.services.status', srv.id), {
            is_active: !srv.is_active
        })
    })
}

// Custom Confirm Modal
const confirmModal = ref({ show: false, title: '', message: '', type: 'danger', onConfirm: null, isAlert: false })
const showConfirm = (title, message, type, onConfirm) => { confirmModal.value = { show: true, title, message, type, onConfirm, isAlert: false } }
const showAlert = (title, message, type) => { confirmModal.value = { show: true, title, message, type, onConfirm: null, isAlert: true } }

const handleConfirm = () => { 
    if (confirmModal.value.onConfirm) {
        confirmModal.value.onConfirm();
    }
    confirmModal.value.show = false 
}
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Dịch vụ</span>
            </div>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">Quản lý Dịch vụ</h2>
                    <p class="text-xs text-slate-400">Cài đặt bảng giá các tiện ích và phí sinh hoạt áp dụng cho phòng trọ</p>
                </div>
                <button @click="openAddService" class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-emerald-500/10 flex items-center gap-1.5">
                    <i class="bi bi-plus-lg"></i> Thêm dịch vụ mới
                </button>
            </div>

            <!-- Grid Services -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="srv in services" 
                    :key="srv.id" 
                    class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200 relative overflow-hidden"
                >
                    <!-- Status Banner -->
                    <div v-if="!srv.is_active" class="absolute top-4 right-[-35px] bg-rose-500 text-white text-[10px] font-bold py-1 px-10 rotate-45 z-10 shadow-sm">
                        ĐÃ KHÓA
                    </div>

                    <div class="space-y-4" :class="{ 'opacity-60': !srv.is_active }">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg border" :class="[colorsConfig[srv.color || 'emerald']?.bg, colorsConfig[srv.color || 'emerald']?.text, colorsConfig[srv.color || 'emerald']?.border]">
                                    <i :class="['bi', srv.icon || 'bi-lightning-charge-fill']"></i>
                                </div>
                                <div class="space-y-0.5">
                                    <h4 class="text-xs font-bold text-slate-800">{{ srv.name }}</h4>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ typeLabels[srv.type] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Price Detail -->
                        <div class="bg-slate-50 rounded-2xl p-4 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400">Đơn giá</span>
                            <span class="text-sm font-extrabold text-slate-800">
                                {{ fmtMoney(srv.price) }} <span class="text-xs text-slate-400 font-bold">/ {{ typeUnits[srv.type] }}</span>
                            </span>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-slate-400 leading-relaxed min-h-[40px]">
                            {{ srv.description || 'Chưa cấu hình mô tả chi tiết cho dịch vụ này.' }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 mt-6 pt-4 border-t border-slate-50 relative z-20">
                        <button @click="openEditService(srv)" class="flex-1 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold text-xs rounded-xl transition-colors">
                            Chỉnh sửa
                        </button>
                        <button @click="toggleStatus(srv)" :class="[
                            'px-3 py-2 rounded-xl transition-colors',
                            srv.is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-500' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-500'
                        ]" :title="srv.is_active ? 'Khóa dịch vụ' : 'Mở khóa dịch vụ'">
                            <i :class="['bi', srv.is_active ? 'bi-lock-fill' : 'bi-unlock-fill']"></i>
                        </button>
                        <button @click="deleteService(srv)" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-500 rounded-xl transition-colors">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div v-if="services.length === 0" class="col-span-full bg-white border border-slate-100 border-dashed rounded-3xl p-12 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 text-2xl mb-4">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 mb-1">Chưa có dịch vụ nào</h3>
                    <p class="text-xs text-slate-400 max-w-sm mb-6">Bạn chưa thêm dịch vụ nào cho nhà trọ của mình. Hãy thêm các dịch vụ như điện, nước, internet để dễ dàng quản lý thu phí.</p>
                    <button @click="openAddService" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition-all shadow-md flex items-center gap-2">
                        <i class="bi bi-plus-lg"></i> Thêm dịch vụ đầu tiên
                    </button>
                </div>
            </div>
        </div>

        <!-- Create/Edit Service Modal -->
        <Teleport to="body">
            <div v-if="showServiceModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showServiceModal = false">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Head -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">{{ isEditService ? 'Cập Nhật Dịch Vụ' : 'Thêm Dịch Vụ Mới' }}</h3>
                        <button @click="showServiceModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- Icon Selector -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500">Biểu tượng</label>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="ic in iconsList" 
                                    :key="ic"
                                    @click="serviceForm.icon = ic"
                                    :class="[
                                        'w-9 h-9 border rounded-xl flex items-center justify-center text-base transition-all',
                                        serviceForm.icon === ic ? 'border-emerald-500 bg-emerald-50 text-emerald-600' : 'border-slate-200 hover:bg-slate-50 text-slate-400'
                                    ]"
                                >
                                    <i :class="['bi', ic]"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Color Selector -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500">Màu sắc</label>
                            <div class="flex items-center gap-3">
                                <button 
                                    v-for="(cfg, key) in colorsConfig" 
                                    :key="key"
                                    @click="serviceForm.color = key"
                                    :class="[
                                        'w-7 h-7 rounded-full transition-all border-2',
                                        serviceForm.color === key ? 'border-slate-800 scale-110' : 'border-transparent'
                                    ]"
                                    :style="{ backgroundColor: `var(--color-${key}-500, ${key === 'emerald' ? '#10b981' : key === 'blue' ? '#3b82f6' : key === 'amber' ? '#f59e0b' : key === 'cyan' ? '#06b6d4' : key === 'rose' ? '#f43f5e' : '#a855f7'})` }"
                                ></button>
                            </div>
                        </div>

                        <!-- Name -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Tên dịch vụ <span class="text-rose-500">*</span></label>
                            <input v-model="serviceForm.name" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all" placeholder="VD: Điện tiêu dùng"/>
                            <div v-if="serviceForm.errors.name" class="text-rose-500 text-[10px]">{{ serviceForm.errors.name }}</div>
                        </div>

                        <!-- Type Select -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Cách thức tính phí <span class="text-rose-500">*</span></label>
                            <select v-model="serviceForm.type" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all bg-white">
                                <option v-for="(lbl, key) in typeLabels" :key="key" :value="key">{{ lbl }}</option>
                            </select>
                            <div v-if="serviceForm.errors.type" class="text-rose-500 text-[10px]">{{ serviceForm.errors.type }}</div>
                        </div>

                        <!-- Price -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Đơn giá (đ) <span class="text-rose-500">*</span></label>
                            <input v-model="displayServicePrice" type="text" placeholder="0" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-bold text-slate-700 outline-none transition-all"/>
                            <div v-if="serviceForm.errors.price" class="text-rose-500 text-[10px]">{{ serviceForm.errors.price }}</div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Mô tả dịch vụ</label>
                            <textarea v-model="serviceForm.description" rows="3" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all resize-none" placeholder="Mô tả cách thức thu hoặc lưu ý cho khách thuê..."></textarea>
                            <div v-if="serviceForm.errors.description" class="text-rose-500 text-[10px]">{{ serviceForm.errors.description }}</div>
                        </div>
                    </div>

                    <!-- Foot -->
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50/50">
                        <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="showServiceModal = false">Hủy</button>
                        <button class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors flex items-center gap-2" :disabled="serviceForm.processing" @click="submitService">
                            <span v-if="serviceForm.processing" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            {{ isEditService ? 'Cập nhật' : 'Thêm mới' }}
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
    </LandlordLayout>
</template>
