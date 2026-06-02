<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import RoomFormModal from './RoomFormModal.vue'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ floors: { type: Array, default: () => [] }, statusCounts: { type: Object, default: () => ({}) } })
const floors = computed(() => props.floors)

const statusConfig = {
    available:       { label:'Còn Trống',     icon:'bi-door-open',         cls:'st-blue',   dot:'dot-blue' },
    rented:          { label:'Đã Thuê',       icon:'bi-person-check-fill', cls:'st-green',  dot:'dot-green' },
    maintenance:     { label:'Bảo Trì',       icon:'bi-wrench-adjustable', cls:'st-yellow', dot:'dot-yellow' },
    deposited:       { label:'Đã Đặt Cọc',   icon:'bi-cash-stack',        cls:'st-purple', dot:'dot-purple' },
    expiring_soon:   { label:'Sắp Hết HĐ',   icon:'bi-clock-history',     cls:'st-orange', dot:'dot-orange' },
    pending_renewal: { label:'Chờ Gia Hạn',   icon:'bi-hourglass-split',   cls:'st-cyan',   dot:'dot-cyan' },
    suspended:       { label:'Tạm Ngưng',     icon:'bi-pause-circle',      cls:'st-red',    dot:'dot-red' },
    under_construction: { label:'Đang Xây Dựng', icon:'bi-tools',            cls:'st-cyan',   dot:'dot-cyan' },
}

const totalRooms = computed(() => floors.value.reduce((s,f) => s + f.rooms.length, 0))
const countSt = (st) => floors.value.reduce((s,f) => s + f.rooms.filter(r=>r.status===st).length, 0)
const fmtMoney = (n) => new Intl.NumberFormat('vi-VN').format(n)+'đ'

const searchQuery = ref('')
const statusFilter = ref('')
const floorFilter = ref('')

const filteredFloors = computed(() => {
    return floors.value.map(f => {
        if (floorFilter.value && f.id !== floorFilter.value) return null
        let r = f.rooms
        if (statusFilter.value) r = r.filter(x => x.status === statusFilter.value)
        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase()
            r = r.filter(x => x.name.toLowerCase().includes(q))
        }
        return { ...f, rooms: r }
    }).filter(f => f !== null && (f.rooms.length > 0 || (!searchQuery.value && !statusFilter.value && !floorFilter.value)))
})

// Quy tắc chuyển trạng thái: từ trạng thái hiện tại -> được phép chuyển sang
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
const getAllowedStatuses = (current) => statusTransitions[current] || []

// Floor modals
const showFloorModal = ref(false)
const editingFloor = ref(null)
const floorName = ref('')
const floorError = ref('')

const capitalize = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : ''

const openAddFloor = () => { editingFloor.value=null; floorName.value=''; floorError.value=''; showFloorModal.value=true }
const openEditFloor = (f) => { editingFloor.value=f; floorName.value=f.name; floorError.value=''; showFloorModal.value=true }
const submitFloor = () => {
    floorError.value = ''
    if(!floorName.value.trim()) { floorError.value = 'Vui lòng nhập tên tầng'; return }
    const name = capitalize(floorName.value.trim())
    floorName.value = name
    const isDup = floors.value.some(f => {
        if(editingFloor.value && f.id === editingFloor.value.id) return false
        return f.name.toLowerCase() === name.toLowerCase()
    })
    if(isDup) { floorError.value = `Tên "${name}" đã tồn tại`; return }
    
    if(editingFloor.value) {
        router.put(route('landlord.floors.update', editingFloor.value.id), {name}, {onSuccess:()=>showFloorModal.value=false})
    } else {
        router.post(route('landlord.floors.store'), {name}, {onSuccess:()=>showFloorModal.value=false})
    }
}
const delFloor = (f) => { if(confirm('Xóa tầng "'+f.name+'" và toàn bộ phòng?')) router.delete(route('landlord.floors.delete', f.id)) }

// Room detail
const showDetail = ref(false)
const selRoom = ref(null)
const selFloorId = ref(null)
const openDetail = (room,fid) => { selRoom.value={...room}; selFloorId.value=fid; showDetail.value=true }
const quickSt = (st) => {
    router.patch(route('landlord.rooms.status', selRoom.value.id), {status:st}, {onSuccess:()=>{selRoom.value.status=st}})
}

// Room form
const showForm = ref(false)
const isEditing = ref(false)
const formFloorId = ref(null)
const currentFloor = computed(() => floors.value.find(fl => fl.id === formFloorId.value))
const currentFloorRooms = computed(() => currentFloor.value ? currentFloor.value.rooms : [])
const currentFloorName = computed(() => currentFloor.value ? currentFloor.value.name : '')
const openAddRoom = (fid) => { isEditing.value=false; formFloorId.value=fid; selRoom.value=null; showDetail.value=false; showForm.value=true }
const openEditRoom = () => { isEditing.value=true; formFloorId.value=selFloorId.value; showDetail.value=false; showForm.value=true }
const submitRoom = (fd) => {
    if(isEditing.value && selRoom.value) {
        router.post(route('landlord.rooms.update', selRoom.value.id), fd, {onSuccess:()=>showForm.value=false, forceFormData:true})
    } else {
        router.post(route('landlord.rooms.store'), fd, {onSuccess:()=>showForm.value=false, forceFormData:true})
    }
}
</script>

<template>
<LandlordLayout>
    <template #header-title><h1 class="ll-header-title">Quản Lý Trọ</h1></template>
    <div class="wrap">
        <!-- Stats -->
        <div class="stats">
            <div class="st-item st-total"><i class="bi bi-building"></i> {{ totalRooms }} phòng</div>
            <div v-for="(cfg,key) in statusConfig" :key="key" class="st-item"><span :class="['dot',cfg.dot]"></span>{{ countSt(key) }} {{ cfg.label }}</div>
            <button class="btn-add-floor" @click="openAddFloor"><i class="bi bi-plus-circle"></i> Thêm tầng</button>
        </div>

        <div v-if="floors.length===0" class="empty-msg">
            <i class="bi bi-inbox" style="font-size:48px;color:#94a3b8"></i>
            <p>Chưa có tầng nào. Bấm <strong>"Thêm tầng"</strong> để bắt đầu.</p>
        </div>

        <!-- Filters -->
        <div class="filters" v-if="floors.length > 0">
            <div class="f-search"><i class="bi bi-search"></i><input v-model="searchQuery" @input="searchQuery = $event.target.value.replace(/\D/g, '')" placeholder="Tìm số phòng..."/></div>
            <select v-model="floorFilter" class="f-sel">
                <option value="">Tất cả tầng</option>
                <option v-for="f in floors" :key="f.id" :value="f.id">{{ f.name }}</option>
            </select>
            <select v-model="statusFilter" class="f-sel">
                <option value="">Tất cả trạng thái</option>
                <option v-for="(cfg,key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
            </select>
        </div>

        <!-- Floors -->
        <div v-for="floor in filteredFloors" :key="floor.id" class="floor-block">
            <div class="floor-head">
                <h3 class="floor-name"><i class="bi bi-layers-fill"></i> {{ floor.name }}</h3>
                <div class="floor-actions">
                    <button class="btn-sm btn-edit" @click="openEditFloor(floor)"><i class="bi bi-pencil"></i></button>
                    <button class="btn-sm btn-del" @click="delFloor(floor)"><i class="bi bi-trash3"></i></button>
                    <button class="btn-add-room" @click="openAddRoom(floor.id)"><i class="bi bi-plus"></i> Thêm phòng</button>
                </div>
            </div>
            <div class="floor-rooms">
                <div v-for="room in floor.rooms" :key="room.id" :class="['room-cell', statusConfig[room.status]?.cls]" @click="openDetail(room,floor.id)">
                    <div class="room-num">{{ room.name }}</div>
                    <div :class="['room-badge', (statusConfig[room.status]?.cls||'')+ '-badge']">
                        <i :class="['bi', statusConfig[room.status]?.icon]"></i> {{ statusConfig[room.status]?.label }}
                    </div>
                    <div class="room-price">{{ fmtMoney(room.price) }}/th</div>
                </div>
                <div v-if="floor.rooms.length===0" class="floor-empty">Chưa có phòng</div>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <!-- Floor Modal -->
        <div v-if="showFloorModal" class="mo" @click.self="showFloorModal=false">
            <div class="mo-box mo-sm">
                <div class="mo-head"><h3>{{ editingFloor?'Sửa Tầng':'Thêm Tầng' }}</h3><button @click="showFloorModal=false" class="mo-x"><i class="bi bi-x-lg"></i></button></div>
                <div class="mo-body">
                    <div class="fg">
                        <label class="fl">Tên tầng</label>
                        <input v-model="floorName" :class="['fi', floorError?'fi-err':'']" placeholder="VD: Tầng 1" @input="floorError=''" @keyup.enter="submitFloor"/>
                        <span v-if="floorError" class="err-msg"><i class="bi bi-exclamation-circle"></i> {{ floorError }}</span>
                    </div>
                </div>
                <div class="mo-foot"><button class="btn-outline" @click="showFloorModal=false">Hủy</button><button class="btn-primary" @click="submitFloor">{{ editingFloor?'Lưu':'Thêm' }}</button></div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div v-if="showDetail&&selRoom" class="mo" @click.self="showDetail=false">
            <div class="mo-box">
                <div class="mo-head"><h3>Phòng {{ selRoom.name }}</h3><button @click="showDetail=false" class="mo-x"><i class="bi bi-x-lg"></i></button></div>
                <div class="mo-body">
                    <div class="dr"><span class="dl">Trạng thái:</span><span :class="['stag',(statusConfig[selRoom.status]?.cls||'')+'-badge']"><i :class="['bi',statusConfig[selRoom.status]?.icon]"></i> {{ statusConfig[selRoom.status]?.label }}</span></div>
                    <div class="dr"><span class="dl">Giá:</span><span>{{ fmtMoney(selRoom.price) }}/th</span></div>
                    <div class="dr"><span class="dl">Địa chỉ:</span><span>{{ selRoom.address || 'Chưa cập nhật' }}</span></div>
                    <div class="dr"><span class="dl">Diện tích:</span><span>{{ selRoom.area }} m²</span></div>
                    <div class="st-section">
                        <p class="dl" style="margin-bottom:8px">Đổi trạng thái:</p>
                        <div class="st-btns">
                            <button v-for="(cfg,key) in statusConfig" :key="key" v-show="getAllowedStatuses(selRoom.status).includes(key)" :class="['sbtn',cfg.cls+'-btn']" @click="quickSt(key)">
                                <i :class="['bi',cfg.icon]"></i> {{ cfg.label }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mo-foot">
                    <button v-if="['under_construction', 'available', 'maintenance'].includes(selRoom.status)" class="btn-lock" @click="quickSt('suspended')"><i class="bi bi-lock-fill"></i> Khóa phòng</button>
                    <button class="btn-outline" @click="showDetail=false">Đóng</button>
                    <button class="btn-primary" @click="openEditRoom"><i class="bi bi-pencil-square"></i> Sửa</button>
                </div>
            </div>
        </div>

        <RoomFormModal :show="showForm" :isEdit="isEditing" :room="selRoom" :floorId="formFloorId" :floorName="currentFloorName" :statusConfig="statusConfig" :existingRooms="currentFloorRooms" @close="showForm=false" @submitted="submitRoom"/>
    </Teleport>
</LandlordLayout>
</template>

<style scoped>
.wrap{display:flex;flex-direction:column;gap:20px}
.stats{display:flex;align-items:center;gap:12px;background:#fff;border-radius:14px;padding:14px 20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f0fdf4;flex-wrap:wrap}
.st-item{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#374151}
.st-total{color:#0f766e;font-size:15px;margin-right:6px}
.dot{width:9px;height:9px;border-radius:50%}
.dot-green{background:#16a34a}.dot-blue{background:#2563eb}.dot-yellow{background:#d97706}.dot-purple{background:#7c3aed}.dot-orange{background:#ea580c}.dot-cyan{background:#0891b2}.dot-red{background:#dc2626}
.btn-add-floor{margin-left:auto;display:flex;align-items:center;gap:6px;padding:8px 16px;background:#0f766e;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer}
.btn-add-floor:hover{background:#0d9488}
.empty-msg{text-align:center;padding:60px 20px;color:#64748b;font-size:15px}
.floor-block{background:#fff;border-radius:16px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f0fdf4}
.floor-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.floor-name{font-size:15px;font-weight:700;color:#064e3b;display:flex;align-items:center;gap:7px;margin:0}
.floor-actions{display:flex;align-items:center;gap:6px}
.btn-sm{width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:all .15s}
.btn-edit{color:#0f766e}.btn-edit:hover{background:#d1fae5}
.btn-del{color:#dc2626}.btn-del:hover{background:#fee2e2}
.btn-add-room{display:flex;align-items:center;gap:5px;padding:6px 14px;background:#f0fdf4;color:#0f766e;border:1px solid #bbf7d0;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer}
.btn-add-room:hover{background:#d1fae5}
.floor-rooms{display:flex;flex-wrap:wrap;gap:12px}
.floor-empty{color:#94a3b8;font-size:14px;padding:20px 0}
.room-cell{width:165px;border-radius:12px;padding:16px 14px;cursor:pointer;transition:transform .15s,box-shadow .15s;border:2px solid transparent;border-left-width:5px}
.room-cell:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,.12)}
.st-green{background:#f0fdf4;border-color:#86efac;border-left-color:#16a34a}
.st-blue{background:#eff6ff;border-color:#93c5fd;border-left-color:#2563eb}
.st-yellow{background:#fffbeb;border-color:#fcd34d;border-left-color:#d97706}
.st-purple{background:#faf5ff;border-color:#c4b5fd;border-left-color:#7c3aed}
.st-orange{background:#fff7ed;border-color:#fdba74;border-left-color:#ea580c}
.st-cyan{background:#ecfeff;border-color:#67e8f9;border-left-color:#0891b2}
.st-red{background:#fef2f2;border-color:#fca5a5;border-left-color:#dc2626}
.room-num{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:6px}
.room-badge{font-size:11px;font-weight:700;padding:3px 8px;border-radius:100px;display:inline-flex;align-items:center;gap:4px;margin-bottom:8px}
.st-green-badge{background:#dcfce7;color:#15803d}.st-blue-badge{background:#dbeafe;color:#1d4ed8}.st-yellow-badge{background:#fef3c7;color:#b45309}.st-purple-badge{background:#ede9fe;color:#6d28d9}.st-orange-badge{background:#ffedd5;color:#c2410c}.st-cyan-badge{background:#cffafe;color:#0e7490}.st-red-badge{background:#fee2e2;color:#b91c1c}
.room-price{font-size:11px;color:#4b5563;font-weight:600}
/* Modals */
.mo{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:1000;backdrop-filter:blur(2px)}
.mo-box{background:#fff;border-radius:18px;width:480px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto}
.mo-sm{width:340px}
.mo-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #f0fdf4;background:#f8fffe;position:sticky;top:0;z-index:1}
.mo-head h3{margin:0;font-size:16px;font-weight:700;color:#064e3b}
.mo-x{background:none;border:none;font-size:16px;cursor:pointer;color:#6b7280}
.mo-body{padding:20px;display:flex;flex-direction:column;gap:14px}
.mo-foot{padding:16px 20px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:10px;position:sticky;bottom:0;background:#fff}
.btn-lock{padding:9px 16px;background:#fee2e2;color:#b91c1c;border:none;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;margin-right:auto}
.btn-lock:hover{background:#fca5a5}
.fl{font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:4px}
.fi{width:100%;padding:9px 12px;border:1.5px solid #d1fae5;border-radius:8px;font-size:14px;outline:none;box-sizing:border-box}
.fi:focus{border-color:#0f766e}
.fi-err{border-color:#dc2626;background:#fef2f2}
.fi-err:focus{border-color:#dc2626}
.err-msg{font-size:12px;color:#dc2626;font-weight:500;display:flex;align-items:center;gap:4px;margin-top:4px}
.dr{display:flex;align-items:center;gap:10px;font-size:14px;color:#374151}
.dl{font-weight:600;color:#6b7280;min-width:90px}
.stag{padding:3px 10px;border-radius:100px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px}
.st-section{margin-top:6px;padding-top:14px;border-top:1px solid #f1f5f9}
.st-btns{display:flex;flex-wrap:wrap;gap:6px}
.sbtn{padding:6px 10px;border-radius:8px;font-size:11px;font-weight:600;border:1.5px solid transparent;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:all .15s}
.sbtn-active{box-shadow:0 0 0 2px rgba(15,118,110,.3);transform:scale(1.05)}
.st-green-btn{background:#dcfce7;color:#15803d;border-color:#86efac}.st-blue-btn{background:#dbeafe;color:#1d4ed8;border-color:#93c5fd}.st-yellow-btn{background:#fef3c7;color:#b45309;border-color:#fcd34d}.st-purple-btn{background:#ede9fe;color:#6d28d9;border-color:#c4b5fd}.st-orange-btn{background:#ffedd5;color:#c2410c;border-color:#fdba74}.st-cyan-btn{background:#cffafe;color:#0e7490;border-color:#67e8f9}.st-red-btn{background:#fee2e2;color:#b91c1c;border-color:#fca5a5}
.btn-primary{padding:9px 20px;background:#0f766e;color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.btn-primary:hover{background:#0d9488}
.btn-outline{padding:9px 20px;background:#fff;color:#374151;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer}
.filters{display:flex;align-items:center;gap:12px;background:#fff;border-radius:12px;padding:12px 16px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #f0fdf4;flex-wrap:wrap;margin-bottom:-6px}
.f-search{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:6px 12px;flex:1;min-width:200px}
.f-search i{color:#94a3b8}
.f-search input{border:none;background:transparent;outline:none;font-size:14px;width:100%}
.f-sel{padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;color:#374151;outline:none;background:#f8fafc;min-width:160px;cursor:pointer}
.f-sel:focus{border-color:#0f766e}
@media(max-width:640px){.stats{gap:8px;padding:12px 14px}.btn-add-floor{width:100%;justify-content:center;margin-left:0}.room-cell{width:calc(50% - 6px)}.mo-box{width:96vw}.lb-arrow{width:36px;height:36px;font-size:16px}.f-sel{width:100%}}
</style>
