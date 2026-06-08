<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref } from 'vue'

const fmtMoney = (n) => new Intl.NumberFormat('vi-VN').format(n)+'đ'

// --- SERVICES STATE & LOGIC ---
const services = ref([
    { id: 1, name: 'Điện sinh hoạt', price: 3500, type: 'per_kwh', icon: 'bi-lightning-charge-fill', color: 'amber', description: 'Đơn giá tính theo lượng tiêu thụ thực tế.' },
    { id: 2, name: 'Nước sinh hoạt', price: 25000, type: 'per_m3', icon: 'bi-droplet-fill', color: 'blue', description: 'Đơn giá tính theo lượng tiêu thụ m³ thực tế.' },
    { id: 3, name: 'Internet cáp quang', price: 100000, type: 'fixed', icon: 'bi-wifi', color: 'cyan', description: 'Tính cố định trên từng phòng hàng tháng.' },
    { id: 4, name: 'Phí rác thải', price: 30000, type: 'fixed', icon: 'bi-trash-fill', color: 'rose', description: 'Tính cố định trên từng phòng hàng tháng.' },
    { id: 5, name: 'Phí trông giữ xe', price: 50000, type: 'per_person', icon: 'bi-bicycle', color: 'purple', description: 'Tính theo đầu người đăng ký giữ xe.' },
    { id: 6, name: 'Phí quản lý & An ninh', price: 50000, type: 'fixed', icon: 'bi-shield-lock-fill', color: 'emerald', description: 'Chi phí bảo vệ và duy trì camera giám sát.' }
])

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

const serviceForm = ref({
    name: '',
    price: 0,
    type: 'fixed',
    icon: 'bi-lightning-charge-fill',
    color: 'emerald',
    description: ''
})

const openAddService = () => {
    isEditService.value = false
    selectedService.value = null
    serviceForm.value = {
        name: '',
        price: 0,
        type: 'fixed',
        icon: 'bi-lightning-charge-fill',
        color: 'emerald',
        description: ''
    }
    showServiceModal.value = true
}

const openEditService = (srv) => {
    isEditService.value = true
    selectedService.value = srv
    serviceForm.value = { ...srv }
    showServiceModal.value = true
}

const submitService = () => {
    if(!serviceForm.value.name.trim()) { alert('Vui lòng điền tên dịch vụ'); return }
    if(serviceForm.value.price < 0) { alert('Giá phải lớn hơn hoặc bằng 0'); return }
    
    if(isEditService.value && selectedService.value) {
        const idx = services.value.findIndex(s => s.id === selectedService.value.id)
        if(idx !== -1) services.value[idx] = { ...selectedService.value, ...serviceForm.value }
    } else {
        const nextId = services.value.length ? Math.max(...services.value.map(s => s.id)) + 1 : 1
        services.value.push({ id: nextId, ...serviceForm.value })
    }
    showServiceModal.value = false
}

const deleteService = (id) => {
    if(confirm('Bạn có chắc muốn xóa dịch vụ này? Hành động này sẽ không thể hoàn tác.')) {
        services.value = services.value.filter(s => s.id !== id)
    }
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
                    class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition-all duration-200"
                >
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-lg border" :class="[colorsConfig[srv.color]?.bg, colorsConfig[srv.color]?.text, colorsConfig[srv.color]?.border]">
                                    <i :class="['bi', srv.icon]"></i>
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
                    <div class="flex items-center gap-2 mt-6 pt-4 border-t border-slate-50">
                        <button @click="openEditService(srv)" class="flex-1 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold text-xs rounded-xl transition-colors">
                            Chỉnh sửa
                        </button>
                        <button @click="deleteService(srv.id)" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-500 rounded-xl transition-colors">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
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
                        </div>

                        <!-- Type Select -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Cách thức tính phí <span class="text-rose-500">*</span></label>
                            <select v-model="serviceForm.type" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all bg-white">
                                <option v-for="(lbl, key) in typeLabels" :key="key" :value="key">{{ lbl }}</option>
                            </select>
                        </div>

                        <!-- Price -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Đơn giá (đ) <span class="text-rose-500">*</span></label>
                            <input v-model.number="serviceForm.price" type="number" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
                        </div>

                        <!-- Description -->
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Mô tả dịch vụ</label>
                            <textarea v-model="serviceForm.description" rows="3" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all resize-none" placeholder="Mô tả cách thức thu hoặc lưu ý cho khách thuê..."></textarea>
                        </div>
                    </div>

                    <!-- Foot -->
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50/50">
                        <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="showServiceModal = false">Hủy</button>
                        <button class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors" @click="submitService">
                            {{ isEditService ? 'Cập nhật' : 'Thêm mới' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>
