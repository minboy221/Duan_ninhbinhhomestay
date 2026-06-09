<script setup>
import { ref, watch } from 'vue'

const props = defineProps({ show: Boolean, isEdit: Boolean, room: Object, floorId: [Number,String], floorName: String, statusConfig: Object, existingRooms: { type: Array, default: () => [] }, floors: { type: Array, default: () => [] } })
const emit = defineEmits(['close','submitted','update:floorId'])

const form = ref({ room_number:'', address:'', price:3000000, area:20, capacity:2, status:'available', amenities:'' })
const submitting = ref(false)
const originalStatus = ref('available')

const capitalize = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : ''

// Transitions for room statuses
const statusTransitions = {
    available:       ['deposited', 'maintenance'],
    deposited:       ['rented', 'available'],
    rented:          ['expiring_soon', 'maintenance'],
    expiring_soon:   ['pending_renewal', 'available'],
    pending_renewal: ['rented', 'available'],
    maintenance:     ['available'],
    suspended:       ['available'],
    under_construction: ['available'],
}

const allowedKeys = (currentSt) => {
    const allowed = statusTransitions[currentSt] || []
    return [currentSt, ...allowed]
}

const errors = ref({})

watch(() => props.show, (v) => {
    if (!v) return
    submitting.value = false; errors.value = {}
    if (props.isEdit && props.room) {
        form.value = {
            room_number: props.room.name||'',
            address: props.room.address||'',
            price: props.room.price||3000000,
            area: props.room.area||20,
            capacity: props.room.capacity||2,
            status: props.room.status||'available',
            amenities: props.room.amenities||'',
        }
        originalStatus.value = props.room.status||'available'
    } else {
        form.value = { room_number:'', address:'', price:3000000, area:20, capacity:2, status:'available', amenities:'' }
    }
})

const validate = () => {
    errors.value = {}
    const name = capitalize(form.value.room_number.trim())
    form.value.room_number = name
    if (form.value.address) {
        form.value.address = capitalize(form.value.address.trim())
    }
    if (!name) { errors.value.room_number = 'Vui lòng nhập số phòng'; return false }

    if (props.floorName) {
        const floorMatch = props.floorName.match(/\d+/)
        if (floorMatch) {
            const prefix = floorMatch[0]
            if (!name.startsWith(prefix)) {
                errors.value.room_number = `Phòng thuộc "${props.floorName}" phải bắt đầu bằng số ${prefix} (VD: ${prefix}01)`
                return false
            }
        }
    }

    const isDuplicate = props.existingRooms.some(r => {
        if (props.isEdit && props.room && r.id === props.room.id) return false
        return r.name.trim().toLowerCase() === name.toLowerCase()
    })
    if (isDuplicate) { errors.value.room_number = `Số phòng "${name}" đã tồn tại trong tầng này`; return false }
    if (!form.value.price || form.value.price <= 0) { errors.value.price = 'Giá thuê phải lớn hơn 0'; return false }
    if (!form.value.area || form.value.area <= 0) { errors.value.area = 'Diện tích phải lớn hơn 0'; return false }
    return true
}

const submit = () => {
    if (!validate() || submitting.value) return
    submitting.value = true

    const fd = new FormData()
    fd.append('floor_id', props.floorId)
    fd.append('room_number', form.value.room_number)
    if (form.value.address) fd.append('address', form.value.address)
    fd.append('price', form.value.price)
    fd.append('area', form.value.area)
    fd.append('capacity', form.value.capacity)
    fd.append('status', form.value.status)
    if (form.value.amenities) fd.append('amenities', form.value.amenities)

    emit('submitted', fd)
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="emit('close')">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <h3 class="text-sm font-bold text-slate-800">{{ isEdit ? 'Sửa Phòng' : 'Thêm Phòng Mới' }}</h3>
                <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                <!-- Chọn Tầng (chỉ hiển thị khi thêm mới phòng) -->
                <div v-if="!isEdit" class="space-y-1">
                    <label class="text-xs font-bold text-slate-500">Tầng <span class="text-rose-500">*</span></label>
                    <select 
                        :value="floorId" 
                        @change="emit('update:floorId', parseInt($event.target.value))"
                        class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none transition-all cursor-pointer bg-white"
                    >
                        <option v-for="f in floors" :key="f.id" :value="f.id">{{ f.name }}</option>
                    </select>
                </div>

                <!-- Room Number -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500">Số phòng <span class="text-rose-500">*</span></label>
                    <input 
                        v-model="form.room_number" 
                        :class="[
                            'w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium outline-none transition-all',
                            errors.room_number ? 'border-rose-300 bg-rose-50/50 focus:border-rose-500' : 'border-slate-200 focus:border-emerald-500'
                        ]" 
                        placeholder="VD: P.101" 
                        @input="errors.room_number=''"
                    />
                    <span v-if="errors.room_number" class="text-[10px] text-rose-500 font-semibold flex items-center gap-1 mt-1">
                        <i class="bi bi-exclamation-circle"></i> {{ errors.room_number }}
                    </span>
                </div>

                <!-- Address -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500">Địa chỉ cụ thể</label>
                    <input 
                        v-model="form.address" 
                        class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all" 
                        placeholder="VD: Tầng 1, phòng phía ngoài"
                    />
                </div>

                <!-- Price and Area Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Giá thuê (đ) <span class="text-rose-500">*</span></label>
                        <input 
                            v-model.number="form.price" 
                            type="number"
                            :class="[
                                'w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium outline-none transition-all',
                                errors.price ? 'border-rose-300 bg-rose-50/50 focus:border-rose-500' : 'border-slate-200 focus:border-emerald-500'
                            ]" 
                            @input="errors.price=''"
                        />
                        <span v-if="errors.price" class="text-[10px] text-rose-500 font-semibold flex items-center gap-1 mt-1">
                            <i class="bi bi-exclamation-circle"></i> {{ errors.price }}
                        </span>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Diện tích (m²) <span class="text-rose-500">*</span></label>
                        <input 
                            v-model.number="form.area" 
                            type="number"
                            :class="[
                                'w-full px-3.5 py-2.5 border rounded-xl text-xs font-medium outline-none transition-all',
                                errors.area ? 'border-rose-300 bg-rose-50/50 focus:border-rose-500' : 'border-slate-200 focus:border-emerald-500'
                            ]" 
                            @input="errors.area=''"
                        />
                        <span v-if="errors.area" class="text-[10px] text-rose-500 font-semibold flex items-center gap-1 mt-1">
                            <i class="bi bi-exclamation-circle"></i> {{ errors.area }}
                        </span>
                    </div>
                </div>

                <!-- Status Select Grid -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500">Trạng thái phòng <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <label 
                            v-for="(cfg,key) in statusConfig" 
                            :key="key"
                            v-show="isEdit ? allowedKeys(originalStatus).includes(key) : ['available', 'under_construction'].includes(key)"
                            :class="[
                                'flex items-center gap-2 p-2.5 border rounded-xl text-[11px] font-bold cursor-pointer transition-all',
                                form.status === key 
                                    ? 'bg-emerald-50 border-emerald-500 text-emerald-600 shadow-sm shadow-emerald-500/10' 
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                            ]"
                        >
                            <input type="radio" :value="key" v-model="form.status" class="hidden"/>
                            <i :class="['bi', cfg.icon, form.status === key ? 'text-emerald-500' : 'text-slate-400']"></i>
                            <span>{{ cfg.label }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50/50">
                <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="emit('close')">
                    Hủy
                </button>
                <button 
                    class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed" 
                    @click="submit" 
                    :disabled="submitting"
                >
                    <i :class="isEdit ? 'bi bi-check-lg' : 'bi-plus-lg'"></i>
                    <span>{{ submitting ? 'Đang xử lý...' : (isEdit ? 'Lưu Thay Đổi' : 'Thêm Phòng') }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
