<script setup>
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({ show: Boolean, isEdit: Boolean, room: Object, floorId: [Number,String], floorName: String, statusConfig: Object, existingRooms: { type: Array, default: () => [] } })
const emit = defineEmits(['close','submitted'])

const form = ref({ room_number:'', address:'', price:3000000, area:20, capacity:2, status:'available', amenities:'' })
const submitting = ref(false)
const originalStatus = ref('available')

const capitalize = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : ''

// Quy tắc chuyển trạng thái
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
// Khi sửa: chỉ hiện trạng thái hiện tại + các trạng thái được phép. Khi thêm: hiện tất cả.
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

    // Kiểm tra số phòng bắt đầu bằng số tầng
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

    // Kiểm tra trùng số phòng trong cùng tầng
    const isDuplicate = props.existingRooms.some(r => {
        if (props.isEdit && props.room && r.id === props.room.id) return false // bỏ qua chính nó
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
<div v-if="show" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-box">
        <div class="modal-head">
            <h3>{{ isEdit ? 'Sửa Phòng' : 'Thêm Phòng Mới' }}</h3>
            <button @click="emit('close')" class="modal-close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
            <div class="fg">
                <label class="fl">Số phòng <span style="color:#dc2626">*</span></label>
                <input v-model="form.room_number" :class="['fi', errors.room_number?'fi-err':'']" placeholder="VD: 101, 102" @input="errors.room_number=''"/>
                <span v-if="errors.room_number" class="err-msg"><i class="bi bi-exclamation-circle"></i> {{ errors.room_number }}</span>
            </div>
            <div class="fg">
                <label class="fl">Địa chỉ trọ</label>
                <input v-model="form.address" class="fi" placeholder="VD: Ngõ 123, đường ABC..."/>
            </div>
            <div class="fr2">
                <div class="fg">
                    <label class="fl">Giá thuê (đ) <span style="color:#dc2626">*</span></label>
                    <input v-model.number="form.price" type="number" :class="['fi', errors.price?'fi-err':'']" @input="errors.price=''"/>
                    <span v-if="errors.price" class="err-msg"><i class="bi bi-exclamation-circle"></i> {{ errors.price }}</span>
                </div>
                <div class="fg">
                    <label class="fl">Diện tích (m²) <span style="color:#dc2626">*</span></label>
                    <input v-model.number="form.area" type="number" :class="['fi', errors.area?'fi-err':'']" @input="errors.area=''"/>
                    <span v-if="errors.area" class="err-msg"><i class="bi bi-exclamation-circle"></i> {{ errors.area }}</span>
                </div>
            </div>
            <div class="fg">
                <label class="fl">Trạng thái <span style="color:#dc2626">*</span></label>
                <div class="st-grid">
                    <label v-for="(cfg,key) in statusConfig" :key="key"
                        v-show="isEdit ? allowedKeys(originalStatus).includes(key) : ['available', 'under_construction'].includes(key)"
                        :class="['st-opt', cfg.cls+'-btn', form.status===key?'st-active':'']">
                        <input type="radio" :value="key" v-model="form.status" class="sr-only"/>
                        <i :class="['bi',cfg.icon]"></i> {{ cfg.label }}
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn-outline" @click="emit('close')">Hủy</button>
            <button class="btn-primary" @click="submit" :disabled="submitting">
                <i :class="isEdit?'bi bi-check-lg':'bi bi-plus-lg'"></i>
                {{ submitting ? 'Đang xử lý...' : (isEdit?'Lưu Thay Đổi':'Thêm Phòng') }}
            </button>
        </div>
    </div>
</div>
</template>

<style scoped>
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(2px)}
.modal-box{background:#fff;border-radius:18px;width:480px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #f0fdf4;background:#f8fffe;position:sticky;top:0;z-index:1}
.modal-head h3{margin:0;font-size:16px;font-weight:700;color:#064e3b}
.modal-close{background:none;border:none;font-size:16px;cursor:pointer;color:#6b7280}
.modal-body{padding:20px;display:flex;flex-direction:column;gap:14px}
.modal-foot{padding:16px 20px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:10px;position:sticky;bottom:0;background:#fff}
.fg{display:flex;flex-direction:column;gap:5px}
.fr2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fl{font-size:13px;font-weight:600;color:#374151}
.fi{width:100%;padding:9px 12px;border:1.5px solid #d1fae5;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box}
.fi:focus{border-color:#0f766e}
.fi-err{border-color:#dc2626;background:#fef2f2}
.fi-err:focus{border-color:#dc2626}
.err-msg{font-size:12px;color:#dc2626;font-weight:500;display:flex;align-items:center;gap:4px;margin-top:2px}
.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
.st-grid{display:flex;flex-wrap:wrap;gap:6px}
.st-opt{display:inline-flex;align-items:center;gap:4px;padding:6px 10px;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;border:1.5px solid transparent;transition:all .15s}
.st-active{transform:scale(1.08);box-shadow:0 0 0 2px rgba(15,118,110,.3)}
.st-green-btn{background:#dcfce7;color:#15803d;border-color:#86efac}
.st-blue-btn{background:#dbeafe;color:#1d4ed8;border-color:#93c5fd}
.st-yellow-btn{background:#fef3c7;color:#b45309;border-color:#fcd34d}
.st-purple-btn{background:#ede9fe;color:#6d28d9;border-color:#c4b5fd}
.st-orange-btn{background:#ffedd5;color:#c2410c;border-color:#fdba74}
.st-cyan-btn{background:#cffafe;color:#0e7490;border-color:#67e8f9}
.st-red-btn{background:#fee2e2;color:#b91c1c;border-color:#fca5a5}
.btn-primary{padding:9px 20px;background:#0f766e;color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.btn-primary:hover{background:#0d9488}
.btn-primary:disabled{opacity:.6;cursor:not-allowed}
.btn-outline{padding:9px 20px;background:#fff;color:#374151;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer}
</style>
