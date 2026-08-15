<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed, watch } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'

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

// Modal Form for individual service
const showServiceModal = ref(false)
const isEditService = ref(false)
const selectedService = ref(null)

const serviceForm = useForm({
    id: null,
    amenity_id: null,
    name: '',
    price: 0,
    type: 'fixed',
    icon: 'bi-star',
    color: 'emerald',
    description: '',
    is_active: true
})

// Hiển thị giá có dấu phẩy trong input
const priceDisplay = ref('')

const formatPriceInput = (val) => {
    // Chỉ giữ số
    const raw = String(val).replace(/[^0-9]/g, '')
    if (!raw) return ''
    return new Intl.NumberFormat('en-US').format(parseInt(raw))
}

const onPriceInput = (e) => {
    const raw = e.target.value.replace(/[^0-9]/g, '')
    const num = raw ? parseInt(raw) : 0
    // Không cho số âm (raw chỉ có số nên luôn >= 0)
    priceDisplay.value = raw ? new Intl.NumberFormat('en-US').format(num) : ''
    serviceForm.price = num
}

const onPriceBlur = () => {
    // Khi rời input, nếu < 100 thì set về 100
    if (serviceForm.price > 0 && serviceForm.price < 100) {
        serviceForm.price = 100
        priceDisplay.value = '100'
    } else if (serviceForm.price <= 0) {
        serviceForm.price = 0
        priceDisplay.value = ''
    }
}

const openConfigureService = (srv) => {
    isEditService.value = false
    selectedService.value = srv
    serviceForm.reset()
    serviceForm.clearErrors()
    serviceForm.id = null
    serviceForm.amenity_id = srv.amenity_id
    serviceForm.name = srv.name
    serviceForm.icon = srv.icon || 'bi-star'
    serviceForm.price = 0
    serviceForm.type = 'fixed'
    serviceForm.color = 'emerald'
    serviceForm.description = ''
    serviceForm.is_active = true
    priceDisplay.value = ''
    showServiceModal.value = true
}

const openEditService = (srv) => {
    isEditService.value = true
    selectedService.value = srv
    serviceForm.id = srv.id
    serviceForm.amenity_id = srv.amenity_id
    serviceForm.name = srv.name
    serviceForm.price = srv.price
    serviceForm.type = srv.type
    serviceForm.icon = srv.icon || 'bi-star'
    serviceForm.color = srv.color || 'emerald'
    serviceForm.description = srv.description || ''
    serviceForm.is_active = srv.is_active
    priceDisplay.value = srv.price ? formatPriceInput(srv.price) : ''
    serviceForm.clearErrors()
    showServiceModal.value = true
}

const submitService = () => {
    if(isEditService.value && selectedService.value) {
        serviceForm.put(route('landlord.services.update', selectedService.value.id), {
            onSuccess: () => {
                showServiceModal.value = false
            }
        })
    } else {
        serviceForm.post(route('landlord.services.store'), {
            onSuccess: () => {
                showServiceModal.value = false
            }
        })
    }
}

const deleteService = (srv) => {
    showConfirm('Xác nhận xóa', `Xóa cấu hình dịch vụ "<strong>${srv.name}</strong>"? Trạng thái dịch vụ sẽ chuyển về chưa kích hoạt.`, 'danger', () => {
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

// Watch Flash Messages
const page = usePage()
watch(() => page.props.flash, (flash) => {
    if (flash && flash.error) {
        showAlert('Lỗi', flash.error, 'danger')
    } else if (flash && flash.success) {
        showAlert('Thành công', flash.success, 'success')
    }
}, { deep: true })

// Pagination State
const currentPage = ref(1)
const perPage = 6

const totalPages = computed(() => Math.max(1, Math.ceil(props.services.length / perPage)))
const paginatedServices = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return props.services.slice(start, start + perPage)
})
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Dịch vụ & Tiện ích</span>
            </div>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">Cấu hình Dịch vụ & Tiện ích</h2>
                    <p class="text-xs text-slate-400">Thiết lập đơn giá cho các tiện ích từ Admin để áp dụng cho phòng trọ của bạn</p>
                </div>
            </div>

            <!-- Grid Services -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="srv in paginatedServices" 
                    :key="srv.amenity_id || srv.id" 
                    class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200 relative overflow-hidden"
                    :class="{ 'border-dashed border-slate-200 bg-slate-50/50': !srv.id }"
                >
                    <!-- Status Banner -->
                    <div v-if="srv.id && !srv.is_active" class="absolute top-4 right-[-35px] bg-rose-500 text-white text-[10px] font-bold py-1 px-10 rotate-45 z-10 shadow-sm">
                        ĐÃ KHÓA
                    </div>
                    <div v-if="!srv.id" class="absolute top-4 right-[-35px] bg-slate-400 text-white text-[9px] font-bold py-1 px-10 rotate-45 z-10 shadow-sm">
                        CHƯA DÙNG
                    </div>

                    <div class="space-y-4" :class="{ 'opacity-60': srv.id && !srv.is_active }">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg border" :class="[colorsConfig[srv.color || 'emerald']?.bg, colorsConfig[srv.color || 'emerald']?.text, colorsConfig[srv.color || 'emerald']?.border]">
                                    <i :class="['bi', srv.icon || 'bi-star']"></i>
                                </div>
                                <div class="space-y-0.5">
                                    <h4 class="text-xs font-bold text-slate-800">{{ srv.name }}</h4>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                        {{ srv.id ? typeLabels[srv.type] : 'Tiện ích hệ thống' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Price Detail -->
                        <div class="bg-slate-50 rounded-2xl p-4 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400">Đơn giá</span>
                            <span class="text-sm font-extrabold text-slate-800" v-if="srv.id">
                                {{ fmtMoney(srv.price) }} <span class="text-xs text-slate-400 font-bold">/ {{ typeUnits[srv.type] }}</span>
                            </span>
                            <span class="text-xs font-bold text-slate-400" v-else>
                                Chưa cài đặt
                            </span>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-slate-400 leading-relaxed min-h-[40px]">
                            {{ srv.description || 'Chưa cấu hình mô tả chi tiết cho dịch vụ này.' }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 mt-6 pt-4 border-t border-slate-50 relative z-20">
                        <!-- If configured -->
                        <template v-if="srv.id">
                            <button @click="openEditService(srv)" class="flex-1 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold text-xs rounded-xl transition-colors">
                                Chỉnh sửa giá
                            </button>
                            <button @click="toggleStatus(srv)" :class="[
                                'px-3 py-2 rounded-xl transition-colors',
                                srv.is_active ? 'bg-amber-50 hover:bg-amber-100 text-amber-500' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-500'
                            ]" :title="srv.is_active ? 'Khóa dịch vụ' : 'Mở khóa dịch vụ'">
                                <i :class="['bi', srv.is_active ? 'bi-lock-fill' : 'bi-unlock-fill']"></i>
                            </button>
                            <button v-if="!srv.is_active" @click="deleteService(srv)" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-500 rounded-xl transition-colors" title="Hủy kích hoạt tiện ích">
                                <i class="bi bi-trash"></i>
                            </button>
                        </template>

                        <!-- If not configured yet -->
                        <template v-else>
                            <button @click="openConfigureService(srv)" class="w-full py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition-colors text-center">
                                Kích hoạt & Cấu hình giá
                            </button>
                        </template>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div v-if="services.length === 0" class="col-span-full bg-white border border-slate-100 border-dashed rounded-3xl p-12 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 text-2xl mb-4">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700 mb-1">Chưa có tiện ích hệ thống nào</h3>
                    <p class="text-xs text-slate-400 max-w-sm mb-6">Hệ thống chưa thiết lập danh sách tiện ích dùng chung. Vui lòng liên hệ Admin.</p>
                </div>
            </div>

            <!-- Pagination Controls -->
            <div class="flex items-center justify-center gap-2 mt-8" v-if="totalPages > 1">
                <button 
                    :disabled="currentPage === 1" 
                    @click="currentPage--" 
                    class="px-3 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button 
                    v-for="p in totalPages" 
                    :key="p" 
                    @click="currentPage = p" 
                    :class="[
                        'px-3.5 py-2 rounded-xl font-bold text-xs transition-all',
                        currentPage === p 
                            ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/10' 
                            : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'
                    ]"
                >
                    {{ p }}
                </button>
                <button 
                    :disabled="currentPage === totalPages" 
                    @click="currentPage++" 
                    class="px-3 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Create/Edit Service Modal -->
        <Teleport to="body">
            <div v-if="showServiceModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showServiceModal = false">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Head -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">{{ isEditService ? 'Cập Nhật Cấu Hình' : 'Kích Hoạt Tiện Ích' }}</h3>
                        <button @click="showServiceModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- Icon & Name display (locked from Admin) -->
                        <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-2xl mb-2 border border-slate-100">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg border bg-emerald-50 text-emerald-600 border-emerald-200">
                                <i :class="['bi', serviceForm.icon]"></i>
                            </div>
                            <div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Tiện ích đang cấu hình</div>
                                <div class="text-xs font-bold text-slate-800">{{ serviceForm.name }}</div>
                            </div>
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
                            <div class="relative">
                                <input
                                    :value="priceDisplay"
                                    @input="onPriceInput"
                                    @blur="onPriceBlur"
                                    @keydown="(e) => ['-', 'e', 'E', '+', '.'].includes(e.key) && e.preventDefault()"
                                    type="text"
                                    inputmode="numeric"
                                    class="w-full px-3.5 py-2.5 pr-8 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                                    placeholder="Nhập số tiền (tối thiểu 100đ)..."
                                />
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">đ</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Tối thiểu: 100đ</div>
                            <div class="text-[10px] text-emerald-600 font-bold mt-1" v-if="serviceForm.price >= 100">
                                Bằng chữ: {{ fmtMoney(serviceForm.price) }}
                            </div>
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
                            {{ isEditService ? 'Cập nhật' : 'Kích hoạt ngay' }}
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
