<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import RoomFormModal from './RoomFormModal.vue'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ floors: { type: Array, default: () => [] }, statusCounts: { type: Object, default: () => ({}) } })
const floors = computed(() => props.floors)

const statusConfig = {
    available:       { label:'Còn Trống',     icon:'bi-door-open',         cls:'bg-emerald-50 text-emerald-600 border-emerald-250',   dot:'bg-emerald-500' },
    rented:          { label:'Đã Thuê',       icon:'bi-person-check-fill', cls:'bg-blue-50 text-blue-600 border-blue-250',  dot:'bg-blue-500' },
    maintenance:     { label:'Bảo Trì',       icon:'bi-wrench-adjustable', cls:'bg-amber-50 text-amber-600 border-amber-250', dot:'bg-amber-500' },
    deposited:       { label:'Đã Đặt Cọc',   icon:'bi-cash-stack',        cls:'bg-purple-50 text-purple-600 border-purple-250', dot:'bg-purple-500' },
    expiring_soon:   { label:'Sắp Hết HĐ',   icon:'bi-clock-history',     cls:'bg-orange-50 text-orange-600 border-orange-250', dot:'bg-orange-500' },
    pending_renewal: { label:'Chờ Gia Hạn',   icon:'bi-hourglass-split',   cls:'bg-cyan-50 text-cyan-600 border-cyan-250',   dot:'bg-cyan-500' },
    suspended:       { label:'Tạm Ngưng',     icon:'bi-pause-circle',      cls:'bg-rose-50 text-rose-600 border-rose-250',    dot:'bg-rose-500' },
    under_construction: { label:'Đang Xây Dựng', icon:'bi-tools',            cls:'bg-slate-50 text-slate-600 border-slate-250',   dot:'bg-slate-500' },
}

const fmtMoney = (n) => new Intl.NumberFormat('vi-VN').format(n)+'đ'

const activeTab = ref('all') // 'all' | 'active' | 'inactive'
const searchQuery = ref('')
const floorFilter = ref('')
const statusFilter = ref('')

const isRoomActive = (status) => {
    return ['available', 'rented', 'deposited', 'expiring_soon', 'pending_renewal'].includes(status)
}

const allFilteredRooms = computed(() => {
    let result = []
    floors.value.forEach(f => {
        f.rooms.forEach(r => {
            // Apply Tab filter
            if (activeTab.value === 'active' && !isRoomActive(r.status)) return
            if (activeTab.value === 'inactive' && isRoomActive(r.status)) return

            // Apply Floor filter
            if (floorFilter.value && f.id !== parseInt(floorFilter.value)) return

            // Apply Status filter
            if (statusFilter.value && r.status !== statusFilter.value) return

            // Apply Search Query
            if (searchQuery.value) {
                const q = searchQuery.value.toLowerCase()
                if (!r.name.toLowerCase().includes(q)) return
            }

            result.push({
                ...r,
                floor_name: f.name,
                floor_id: f.id
            })
        })
    })
    return result
})

// Transitions rules
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

// Floor Modals
const showFloorModal = ref(false)
const editingFloor = ref(null)
const floorName = ref('')
const floorError = ref('')

const openAddFloor = () => { editingFloor.value=null; floorName.value=''; floorError.value=''; showFloorModal.value=true }
const openEditFloor = (f) => { editingFloor.value=f; floorName.value=f.name; floorError.value=''; showFloorModal.value=true }
const submitFloor = () => {
    floorError.value = ''
    if(!floorName.value.trim()) { floorError.value = 'Vui lòng nhập tên tầng'; return }
    const name = floorName.value.trim()
    
    if(editingFloor.value) {
        router.put(route('landlord.floors.update', editingFloor.value.id), {name}, {onSuccess:()=>showFloorModal.value=false})
    } else {
        router.post(route('landlord.floors.store'), {name}, {onSuccess:()=>showFloorModal.value=false})
    }
}
const delFloor = (f) => { if(confirm(`Xóa tầng "${f.name}" và toàn bộ phòng thuộc tầng?`)) router.delete(route('landlord.floors.delete', f.id)) }

// Room Actions
const showDetail = ref(false)
const selRoom = ref(null)
const selFloorId = ref(null)

const openDetail = (room) => {
    selRoom.value = { ...room }
    selFloorId.value = room.floor_id
    showDetail.value = true
}

const quickSt = (st) => {
    router.patch(route('landlord.rooms.status', selRoom.value.id), {status:st}, {onSuccess:()=>{selRoom.value.status=st}})
}

// Room Form Modals
const showForm = ref(false)
const isEditing = ref(false)
const formFloorId = ref(null)
const currentFloor = computed(() => floors.value.find(fl => fl.id === formFloorId.value))
const currentFloorRooms = computed(() => currentFloor.value ? currentFloor.value.rooms : [])
const currentFloorName = computed(() => currentFloor.value ? currentFloor.value.name : '')

const openAddRoom = () => {
    if (floors.value.length === 0) {
        alert('Vui lòng thêm tầng trước!')
        return
    }
    isEditing.value = false
    formFloorId.value = floorFilter.value ? parseInt(floorFilter.value) : floors.value[0].id
    selRoom.value = null
    showForm.value = true
}

const openEditRoom = () => {
    isEditing.value = true
    formFloorId.value = selFloorId.value
    showDetail.value = false
    showForm.value = true
}

const submitRoom = (fd) => {
    if(isEditing.value && selRoom.value) {
        router.post(route('landlord.rooms.update', selRoom.value.id), fd, {onSuccess:()=>showForm.value=false, forceFormData:true})
    } else {
        router.post(route('landlord.rooms.store'), fd, {onSuccess:()=>showForm.value=false, forceFormData:true})
    }
}

const delRoom = (room) => {
    if(confirm(`Xóa phòng "${room.name}"?`)) {
        router.delete(route('landlord.rooms.delete', room.id), {onSuccess:()=>showDetail.value=false})
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
                <span class="text-slate-600">Nhà & Phòng</span>
            </div>

            <!-- Page Title -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">Quản lý Phòng trọ</h2>
                    <p class="text-xs text-slate-400">Danh sách các tầng và các phòng cho thuê hiện tại</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 border border-emerald-200 hover:bg-emerald-50 text-emerald-600 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 bg-white" @click="openAddFloor">
                        <i class="bi bi-plus-lg"></i> Thêm tầng
                    </button>
                    <button class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-emerald-500/10 flex items-center gap-1.5" @click="openAddRoom">
                        <i class="bi bi-plus-lg"></i> Thêm phòng
                    </button>
                </div>
            </div>

            <!-- Tab Filters -->
            <div class="border-b border-slate-100 flex gap-6 text-xs font-bold text-slate-400">
                <button 
                    @click="activeTab = 'all'"
                    :class="[
                        'pb-3 border-b-2 transition-colors',
                        activeTab === 'all' ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-slate-600'
                    ]"
                >
                    Tất cả ({{ floors.reduce((s,f) => s + f.rooms.length, 0) }})
                </button>
                <button 
                    @click="activeTab = 'active'"
                    :class="[
                        'pb-3 border-b-2 transition-colors',
                        activeTab === 'active' ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-slate-600'
                    ]"
                >
                    Đang hoạt động ({{ floors.reduce((s,f) => s + f.rooms.filter(r => isRoomActive(r.status)).length, 0) }})
                </button>
                <button 
                    @click="activeTab = 'inactive'"
                    :class="[
                        'pb-3 border-b-2 transition-colors',
                        activeTab === 'inactive' ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-slate-600'
                    ]"
                >
                    Không hoạt động ({{ floors.reduce((s,f) => s + f.rooms.filter(r => !isRoomActive(r.status)).length, 0) }})
                </button>
            </div>

            <!-- Filter Controls -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 flex flex-wrap items-center gap-3 shadow-sm">
                <!-- Search -->
                <div class="flex items-center bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-slate-400 gap-2 flex-1 min-w-[200px]">
                    <i class="bi bi-search text-xs"></i>
                    <input v-model="searchQuery" class="bg-transparent border-none outline-none text-xs text-slate-700 w-full placeholder-slate-400" placeholder="Tìm số phòng..."/>
                </div>

                <!-- Tầng filter -->
                <select v-model="floorFilter" class="text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-150 rounded-xl px-3 py-2 outline-none cursor-pointer min-w-[150px]">
                    <option value="">Tất cả tầng</option>
                    <option v-for="f in floors" :key="f.id" :value="f.id">{{ f.name }}</option>
                </select>

                <!-- Trạng thái filter -->
                <select v-model="statusFilter" class="text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-150 rounded-xl px-3 py-2 outline-none cursor-pointer min-w-[150px]">
                    <option value="">Tất cả trạng thái</option>
                    <option v-for="(cfg,key) in statusConfig" :key="key" :value="key">{{ cfg.label }}</option>
                </select>
            </div>

            <!-- Rooms Table -->
            <div class="bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
                <div v-if="allFilteredRooms.length === 0" class="p-8 text-center text-slate-400 text-xs font-medium space-y-2">
                    <i class="bi bi-inbox text-3xl text-slate-300 block"></i>
                    <span>Không tìm thấy phòng nào phù hợp bộ lọc.</span>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6">Mã phòng</th>
                                <th class="py-3.5 px-4">Tầng</th>
                                <th class="py-3.5 px-4">Diện tích</th>
                                <th class="py-3.5 px-4">Đơn giá</th>
                                <th class="py-3.5 px-4">Tình trạng</th>
                                <th class="py-3.5 px-4">Trạng thái</th>
                                <th class="py-3.5 px-6 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-600">
                            <tr v-for="room in allFilteredRooms" :key="room.id" class="hover:bg-slate-50/40 cursor-pointer" @click="openDetail(room)">
                                <td class="py-4 px-6 font-bold text-slate-800 flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-black text-sm">
                                        P
                                    </div>
                                    <span>{{ room.name }}</span>
                                </td>
                                <td class="py-4 px-4">{{ room.floor_name }}</td>
                                <td class="py-4 px-4">{{ room.area }} m²</td>
                                <td class="py-4 px-4 text-slate-800">{{ fmtMoney(room.price) }}/tháng</td>
                                <td class="py-4 px-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-md text-[10px] font-bold border flex items-center gap-1.5 w-fit',
                                        statusConfig[room.status]?.cls || 'bg-slate-50 text-slate-600 border-slate-200'
                                    ]">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[room.status]?.dot"></span>
                                        {{ statusConfig[room.status]?.label || 'Không rõ' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <span :class="[
                                        'px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider',
                                        isRoomActive(room.status) ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-50 text-slate-500 border border-slate-100'
                                    ]">
                                        {{ isRoomActive(room.status) ? 'Đang hoạt động' : 'Tạm ngưng' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right" @click.stop>
                                    <button @click="openDetail(room)" class="w-8 h-8 bg-slate-50 hover:bg-slate-100 rounded-lg text-slate-500 inline-flex items-center justify-center transition-colors">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Floor List Settings at bottom -->
            <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-4">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cấu trúc các tầng</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <div v-for="fl in floors" :key="fl.id" class="p-4 bg-slate-50/50 border border-slate-100 rounded-2xl flex items-center justify-between">
                        <div class="space-y-1">
                            <h4 class="text-xs font-bold text-slate-800">{{ fl.name }}</h4>
                            <p class="text-[10px] text-slate-400 font-semibold">{{ fl.rooms.length }} phòng</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button @click="openEditFloor(fl)" class="w-7 h-7 hover:bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center"><i class="bi bi-pencil-square"></i></button>
                            <button @click="delFloor(fl)" class="w-7 h-7 hover:bg-rose-50 text-rose-500 rounded-lg flex items-center justify-center"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <Teleport to="body">
            <!-- Floor Add/Edit Modal -->
            <div v-if="showFloorModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showFloorModal=false">
                <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">{{ editingFloor ? 'Sửa Tầng' : 'Thêm Tầng Mới' }}</h3>
                        <button @click="showFloorModal=false" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Tên tầng <span class="text-rose-500">*</span></label>
                            <input v-model="floorName" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all" placeholder="VD: Tầng 1" @keyup.enter="submitFloor"/>
                            <span v-if="floorError" class="text-[10px] text-rose-500 font-semibold block mt-1"><i class="bi bi-exclamation-circle"></i> {{ floorError }}</span>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50/50">
                        <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="showFloorModal=false">Hủy</button>
                        <button class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors" @click="submitFloor">{{ editingFloor ? 'Cập nhật' : 'Thêm' }}</button>
                    </div>
                </div>
            </div>

            <!-- Detail Modal -->
            <div v-if="showDetail && selRoom" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showDetail=false">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">Chi tiết phòng {{ selRoom.name }}</h3>
                        <button @click="showDetail=false" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="p-6 space-y-4 overflow-y-auto">
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <span class="text-xs font-bold text-slate-400">Trạng thái:</span>
                            <span :class="['px-2.5 py-1 rounded-md text-[10px] font-bold border flex items-center gap-1.5', statusConfig[selRoom.status]?.cls]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[selRoom.status]?.dot"></span>
                                {{ statusConfig[selRoom.status]?.label }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <span class="text-xs font-bold text-slate-400">Giá thuê:</span>
                            <span class="text-xs font-bold text-slate-800">{{ fmtMoney(selRoom.price) }}/tháng</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <span class="text-xs font-bold text-slate-400">Địa chỉ:</span>
                            <span class="text-xs font-bold text-slate-800">{{ selRoom.address || 'Chưa cập nhật' }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-50 pb-2.5">
                            <span class="text-xs font-bold text-slate-400">Diện tích:</span>
                            <span class="text-xs font-bold text-slate-800">{{ selRoom.area }} m²</span>
                        </div>

                        <!-- Status transitions buttons -->
                        <div class="space-y-2 pt-2">
                            <p class="text-xs font-bold text-slate-400">Chuyển đổi trạng thái nhanh:</p>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="(cfg,key) in statusConfig" 
                                    :key="key"
                                    v-show="getAllowedStatuses(selRoom.status).includes(key)"
                                    :class="['px-3 py-2 rounded-xl text-[10px] font-bold border cursor-pointer hover:shadow-sm transition-all', cfg.cls]"
                                    @click="quickSt(key)"
                                >
                                    <i :class="['bi mr-1', cfg.icon]"></i> {{ cfg.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <button class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs rounded-xl transition-colors" @click="delRoom(selRoom)">
                            <i class="bi bi-trash mr-1"></i> Xóa phòng
                        </button>
                        <div class="flex items-center gap-2">
                            <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="showDetail=false">Đóng</button>
                            <button class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors" @click="openEditRoom">Sửa thông tin</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Room Modal Form -->
            <RoomFormModal 
                :show="showForm" 
                :isEdit="isEditing" 
                :room="selRoom" 
                :floorId="formFloorId" 
                :floorName="currentFloorName" 
                :statusConfig="statusConfig" 
                :existingRooms="currentFloorRooms" 
                @close="showForm=false" 
                @submitted="submitRoom"
            />
        </Teleport>
    </LandlordLayout>
</template>
