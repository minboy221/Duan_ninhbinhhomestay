<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed, reactive, watch } from 'vue'

const currentView = ref('list') // 'list' | 'create' | 'edit'
const selectedRoomName = ref('')
const selectedInvoiceId = ref(null)

const month = ref('2026-06')
const houseFilter = ref('all')
const startDate = ref('')
const endDate = ref('')
const searchQ = ref('')
const activeTab = ref('all') // 'all' | 'open' | 'completed' | 'cancelled'

// Default lists
const DEFAULT_ROOMS = [
    { name: 'Phòng P.101', tenant: 'pham manh dung', rent: 3000000, elecStart: 900, waterStart: 90, tenantsCount: 1 },
    { name: 'Phòng P.102', tenant: 'nguyen van a', rent: 2800000, elecStart: 850, waterStart: 80, tenantsCount: 1 },
    { name: 'Phòng P.201', tenant: 'tran thi b', rent: 3200000, elecStart: 1200, waterStart: 110, tenantsCount: 2 },
    { name: 'Phòng P.202', tenant: 'le minh c', rent: 3200000, elecStart: 1050, waterStart: 95, tenantsCount: 1 },
]

const DEFAULT_INVOICES = [
    {
        id: 'HD001',
        room: 'Phòng P.101',
        tenant: 'pham manh dung',
        rent: 3000000,
        elecOld: 900,
        elecNew: 1000,
        elecPrice: 3000,
        waterOld: 90,
        waterNew: 100,
        waterPrice: 15000,
        internetQty: 1,
        internetPrice: 50000,
        trashQty: 1,
        trashPrice: 30000,
        parkingQty: 1,
        parkingPrice: 15000,
        management: 0,
        status: 'open', // 'open' | 'completed' | 'cancelled'
        dueDate: '2026-06-15',
        house: 'Nhà Trọ Thanh Hóa'
    }
]

const invoices = ref([])
if (typeof window !== 'undefined') {
    const saved = localStorage.getItem('landlord_portal_invoices')
    invoices.value = saved ? JSON.parse(saved) : DEFAULT_INVOICES
} else {
    invoices.value = DEFAULT_INVOICES
}

watch(invoices, (val) => {
    localStorage.setItem('landlord_portal_invoices', JSON.stringify(val))
}, { deep: true })

// Form state
const invoiceForm = reactive({
    tenantName: '',
    rent: 3000000,
    elecOld: 0,
    elecNew: 0,
    elecPrice: 3000,
    waterOld: 0,
    waterNew: 0,
    waterPrice: 15000,
    internetQty: 1,
    internetPrice: 50000,
    trashQty: 1,
    trashPrice: 30000,
    parkingQty: 1,
    parkingPrice: 15000,
    management: 0,
    dueDate: '2026-06-15'
})

// Update form when room changes
watch(selectedRoomName, (newName) => {
    const room = DEFAULT_ROOMS.find(r => r.name === newName)
    if (room) {
        invoiceForm.tenantName = room.tenant
        invoiceForm.rent = room.rent
        invoiceForm.elecOld = room.elecStart
        invoiceForm.elecNew = room.elecStart + 100
        invoiceForm.waterOld = room.waterStart
        invoiceForm.waterNew = room.waterStart + 10
        invoiceForm.internetQty = room.tenantsCount
        invoiceForm.trashQty = room.tenantsCount
        invoiceForm.parkingQty = room.tenantsCount
        invoiceForm.management = 0
    }
})

// Calculations
const elecDiff = computed(() => Math.max(0, invoiceForm.elecNew - invoiceForm.elecOld))
const elecTotal = computed(() => elecDiff.value * invoiceForm.elecPrice)

const waterDiff = computed(() => Math.max(0, invoiceForm.waterNew - invoiceForm.waterOld))
const waterTotal = computed(() => waterDiff.value * invoiceForm.waterPrice)

const internetTotal = computed(() => invoiceForm.internetQty * invoiceForm.internetPrice)
const trashTotal = computed(() => invoiceForm.trashQty * invoiceForm.trashPrice)
const parkingTotal = computed(() => invoiceForm.parkingQty * invoiceForm.parkingPrice)

const formTotal = computed(() => {
    return Number(invoiceForm.rent) +
        elecTotal.value +
        waterTotal.value +
        internetTotal.value +
        trashTotal.value +
        parkingTotal.value +
        Number(invoiceForm.management)
})

const getInvoiceTotal = (inv) => {
    const elec = (inv.elecNew - inv.elecOld) * inv.elecPrice
    const water = (inv.waterNew - inv.waterOld) * inv.waterPrice
    const internet = inv.internetQty * inv.internetPrice
    const trash = inv.trashQty * inv.trashPrice
    const parking = inv.parkingQty * inv.parkingPrice
    return inv.rent + elec + water + internet + trash + parking + inv.management
}

// Filters
const filteredInvoices = computed(() => {
    return invoices.value.filter(inv => {
        if (activeTab.value !== 'all') {
            if (activeTab.value === 'open' && inv.status !== 'open') return false
            if (activeTab.value === 'completed' && inv.status !== 'completed') return false
            if (activeTab.value === 'cancelled' && inv.status !== 'cancelled') return false
        }
        if (searchQ.value) {
            const q = searchQ.value.toLowerCase()
            return inv.room.toLowerCase().includes(q) || inv.tenant.toLowerCase().includes(q)
        }
        return true
    })
})

const formatMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + ' đ'

// Action triggers
const goCreate = () => {
    currentView.value = 'create'
    selectedRoomName.value = DEFAULT_ROOMS[0].name
    invoiceForm.dueDate = new Date().toISOString().split('T')[0]
}

const goEdit = (inv) => {
    selectedInvoiceId.value = inv.id
    selectedRoomName.value = inv.room
    invoiceForm.tenantName = inv.tenant
    invoiceForm.rent = inv.rent
    invoiceForm.elecOld = inv.elecOld
    invoiceForm.elecNew = inv.elecNew
    invoiceForm.elecPrice = inv.elecPrice
    invoiceForm.waterOld = inv.waterOld
    invoiceForm.waterNew = inv.waterNew
    invoiceForm.waterPrice = inv.waterPrice
    invoiceForm.internetQty = inv.internetQty
    invoiceForm.internetPrice = inv.internetPrice
    invoiceForm.trashQty = inv.trashQty
    invoiceForm.trashPrice = inv.trashPrice
    invoiceForm.parkingQty = inv.parkingQty
    invoiceForm.parkingPrice = inv.parkingPrice
    invoiceForm.management = inv.management
    invoiceForm.dueDate = inv.dueDate
    currentView.value = 'edit'
}

const saveInvoice = () => {
    if (currentView.value === 'create') {
        const newInv = {
            id: 'HD' + String(invoices.value.length + 1).padStart(3, '0'),
            room: selectedRoomName.value,
            tenant: invoiceForm.tenantName,
            rent: Number(invoiceForm.rent),
            elecOld: Number(invoiceForm.elecOld),
            elecNew: Number(invoiceForm.elecNew),
            elecPrice: Number(invoiceForm.elecPrice),
            waterOld: Number(invoiceForm.waterOld),
            waterNew: Number(invoiceForm.waterNew),
            waterPrice: Number(invoiceForm.waterPrice),
            internetQty: Number(invoiceForm.internetQty),
            internetPrice: Number(invoiceForm.internetPrice),
            trashQty: Number(invoiceForm.trashQty),
            trashPrice: Number(invoiceForm.trashPrice),
            parkingQty: Number(invoiceForm.parkingQty),
            parkingPrice: Number(invoiceForm.parkingPrice),
            management: Number(invoiceForm.management),
            status: 'open',
            dueDate: invoiceForm.dueDate,
            house: 'Nhà Trọ Thanh Hóa'
        }
        invoices.value.push(newInv)
        alert('Đã tạo hóa đơn thành công!')
    } else {
        const idx = invoices.value.findIndex(i => i.id === selectedInvoiceId.value)
        if (idx !== -1) {
            invoices.value[idx] = {
                ...invoices.value[idx],
                rent: Number(invoiceForm.rent),
                elecOld: Number(invoiceForm.elecOld),
                elecNew: Number(invoiceForm.elecNew),
                elecPrice: Number(invoiceForm.elecPrice),
                waterOld: Number(invoiceForm.waterOld),
                waterNew: Number(invoiceForm.waterNew),
                waterPrice: Number(invoiceForm.waterPrice),
                internetQty: Number(invoiceForm.internetQty),
                internetPrice: Number(invoiceForm.internetPrice),
                trashQty: Number(invoiceForm.trashQty),
                trashPrice: Number(invoiceForm.trashPrice),
                parkingQty: Number(invoiceForm.parkingQty),
                parkingPrice: Number(invoiceForm.parkingPrice),
                management: Number(invoiceForm.management),
                dueDate: invoiceForm.dueDate,
            }
            alert('Đã cập nhật hóa đơn thành công!')
        }
    }
    currentView.value = 'list'
}

const changeStatus = (inv, newStatus) => {
    inv.status = newStatus
}

// Modal view detail
const showViewModal = ref(false)
const selectedInvoice = ref(null)

const openViewModal = (inv) => {
    selectedInvoice.value = inv
    showViewModal.value = true
}
const closeViewModal = () => {
    showViewModal.value = false
    selectedInvoice.value = null
}
</script>

<template>
    <LandlordLayout>
        <!-- LIST VIEW -->
        <div v-if="currentView === 'list'" class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Hóa đơn</span>
            </div>

            <!-- Title Header -->
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold text-slate-800">Hóa đơn</h1>
                <button @click="goCreate" class="px-4 py-2.5 bg-[#0e3b3e] hover:bg-[#09282a] text-white font-semibold text-xs rounded-xl flex items-center gap-1.5 shadow-sm transition-colors">
                    <i class="bi bi-plus-lg"></i> Thêm hóa đơn mới
                </button>
            </div>

            <!-- Tabs Filters -->
            <div class="flex gap-6 text-xs font-bold text-slate-400 border-b border-slate-100 pb-3">
                <button @click="activeTab = 'all'" :class="['pb-2 border-b-2 transition-colors -mb-[13px]', activeTab === 'all' ? 'border-emerald-600 text-emerald-600' : 'border-transparent hover:text-slate-600']">Tất cả <span class="ml-1 px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px]">{{ invoices.length }}</span></button>
                <button @click="activeTab = 'open'" :class="['pb-2 border-b-2 transition-colors -mb-[13px]', activeTab === 'open' ? 'border-emerald-600 text-emerald-600' : 'border-transparent hover:text-slate-600']">Đang mở <span class="ml-1 px-1.5 py-0.5 bg-emerald-50 text-emerald-600 rounded-full text-[10px]">{{ invoices.filter(i=>i.status==='open').length }}</span></button>
                <button @click="activeTab = 'completed'" :class="['pb-2 border-b-2 transition-colors -mb-[13px]', activeTab === 'completed' ? 'border-emerald-600 text-emerald-600' : 'border-transparent hover:text-slate-600']">Hoàn thành <span class="ml-1 px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded-full text-[10px]">{{ invoices.filter(i=>i.status==='completed').length }}</span></button>
                <button @click="activeTab = 'cancelled'" :class="['pb-2 border-b-2 transition-colors -mb-[13px]', activeTab === 'cancelled' ? 'border-emerald-600 text-emerald-600' : 'border-transparent hover:text-slate-600']">Đã hủy <span class="ml-1 px-1.5 py-0.5 bg-slate-200 text-slate-600 rounded-full text-[10px]">{{ invoices.filter(i=>i.status==='cancelled').length }}</span></button>
            </div>

            <!-- Filters Bar (Image 3) -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-white border border-slate-100 rounded-2xl p-4 shadow-sm">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400">Nhà</label>
                    <select v-model="houseFilter" class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/50 cursor-pointer">
                        <option value="all">Nhà Trọ Thanh Hóa</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400">Ngày bắt đầu</label>
                    <input type="date" v-model="startDate" class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none text-slate-600 bg-slate-50/50 cursor-pointer" />
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400">Ngày kết thúc</label>
                    <input type="date" v-model="endDate" class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none text-slate-600 bg-slate-50/50 cursor-pointer" />
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400">Tìm kiếm</label>
                    <div class="flex items-center bg-slate-50/50 border border-slate-200 rounded-xl px-3 py-2 text-slate-400 gap-2">
                        <i class="bi bi-search text-xs"></i>
                        <input v-model="searchQ" class="bg-transparent border-none outline-none text-xs text-slate-700 w-full placeholder-slate-400 font-semibold" placeholder="Tìm kiếm..."/>
                    </div>
                </div>
            </div>

            <!-- Invoices Desktop Table List (hidden on mobile) -->
            <div class="hidden lg:block bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1000px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4 w-12 text-center">
                                    <input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                </th>
                                <th class="py-3.5 px-4">Kỳ thanh toán</th>
                                <th class="py-3.5 px-4">Nhà</th>
                                <th class="py-3.5 px-4 text-right">Doanh thu</th>
                                <th class="py-3.5 px-4 text-right">Đã thanh toán</th>
                                <th class="py-3.5 px-4 text-center">Tình trạng</th>
                                <th class="py-3.5 px-4">Người tạo</th>
                                <th class="py-3.5 px-6 text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-600">
                            <tr v-for="inv in filteredInvoices" :key="inv.id" class="hover:bg-slate-50/40">
                                <td class="py-4 px-4 text-center">
                                    <input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-bold text-slate-800">Kỳ [{{ new Date(inv.dueDate).toLocaleDateString('vi-VN') }}]</div>
                                    <div class="text-[10px] text-slate-400 font-semibold">1 hợp đồng</div>
                                </td>
                                <td class="py-4 px-4 text-slate-500 font-medium">{{ inv.house }}</td>
                                <td class="py-4 px-4 text-right text-slate-800 font-bold">{{ formatMoney(getInvoiceTotal(inv)) }}</td>
                                <td class="py-4 px-4 text-right" :class="inv.status === 'completed' ? 'text-emerald-600 font-bold' : 'text-slate-400 font-medium'">
                                    {{ inv.status === 'completed' ? formatMoney(getInvoiceTotal(inv)) : '0 đ' }}
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex justify-center">
                                        <span v-if="inv.status === 'open'" class="px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-md text-[10px] font-bold">
                                            Đang Mở
                                        </span>
                                        <span v-else-if="inv.status === 'completed'" class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-md text-[10px] font-bold">
                                            Hoàn Thành
                                        </span>
                                        <span v-else class="px-2 py-0.5 bg-slate-50 text-slate-500 border border-slate-200 rounded-md text-[10px] font-bold">
                                            Đã Hủy
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-[10px] overflow-hidden">
                                            D
                                        </div>
                                        <div class="text-slate-700 font-semibold">Phạm Mạnh Dũng</div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openViewModal(inv)" class="w-7 h-7 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-600 text-slate-500 rounded-lg flex items-center justify-center" title="Xem chi tiết hóa đơn">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button @click="goEdit(inv)" class="w-7 h-7 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-600 text-slate-500 rounded-lg flex items-center justify-center" title="Chỉnh sửa hóa đơn">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button v-if="inv.status === 'open'" @click="changeStatus(inv, 'completed')" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-100 rounded-lg text-[10px] font-bold flex items-center gap-1">
                                            <i class="bi bi-check-lg"></i> Thu tiền
                                        </button>
                                        <button class="w-7 h-7 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center"><i class="bi bi-three-dots"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoices Mobile Card List (shown only on mobile) -->
            <div class="block lg:hidden space-y-4">
                <div v-for="inv in filteredInvoices" :key="inv.id" class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ inv.house }}</div>
                            <div class="text-sm font-black text-slate-800 mt-1">{{ inv.room }}</div>
                            <div class="text-xs text-slate-500 font-semibold mt-0.5">Khách: <span class="text-slate-700 font-bold">{{ inv.tenant }}</span></div>
                        </div>
                        <div>
                            <span v-if="inv.status === 'open'" class="px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-md text-[10px] font-bold">
                                Đang Mở
                            </span>
                            <span v-else-if="inv.status === 'completed'" class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-md text-[10px] font-bold">
                                Hoàn Thành
                            </span>
                            <span v-else class="px-2 py-0.5 bg-slate-50 text-slate-500 border border-slate-200 rounded-md text-[10px] font-bold">
                                Đã Hủy
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center pt-3 border-t border-slate-50 text-xs">
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Kỳ đóng tiền</div>
                            <div class="font-bold text-slate-700 mt-0.5">{{ new Date(inv.dueDate).toLocaleDateString('vi-VN') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Tổng thu</div>
                            <div class="font-black text-rose-500 mt-0.5">{{ formatMoney(getInvoiceTotal(inv)) }}</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-1.5 pt-3 border-t border-slate-50">
                        <button @click="openViewModal(inv)" class="w-8 h-8 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-600 text-slate-500 rounded-xl flex items-center justify-center" title="Xem chi tiết">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button @click="goEdit(inv)" class="w-8 h-8 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-600 text-slate-500 rounded-xl flex items-center justify-center" title="Chỉnh sửa">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button v-if="inv.status === 'open'" @click="changeStatus(inv, 'completed')" class="px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-100 rounded-xl text-xs font-extrabold flex items-center gap-1">
                            <i class="bi bi-check-lg"></i> Thu tiền
                        </button>
                    </div>
                </div>
                <div v-if="filteredInvoices.length === 0" class="text-center py-8 text-slate-400 font-bold text-xs">
                    Không tìm thấy hóa đơn nào
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT VIEW (Image 2) -->
        <div v-else class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span class="cursor-pointer hover:text-slate-600" @click="currentView = 'list'">Hóa đơn</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">{{ currentView === 'create' ? 'Tạo hóa đơn' : 'Chỉnh sửa hóa đơn' }}</span>
            </div>

            <div class="flex flex-col md:flex-row gap-6 items-start">
                <!-- Left Form Card -->
                <div class="w-full md:w-2/3 bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-6">
                    <div class="flex justify-between items-center border-b border-slate-50 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg font-bold">
                                <i class="bi bi-house"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm">
                                    {{ selectedRoomName || 'Chọn phòng' }} ({{ invoiceForm.tenantName || 'Khách thuê' }})
                                </h3>
                                <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Vui lòng cập nhật các chỉ số sử dụng</p>
                            </div>
                        </div>
                        
                        <!-- Top Right Big orange price display -->
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Tổng cộng</span>
                            <span class="text-2xl font-black text-rose-500">{{ formatMoney(formTotal) }}</span>
                        </div>
                    </div>

                    <!-- Services Indices List (Image 2) -->
                    <div class="space-y-6">
                        <!-- Room & Billing cycle selectors -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50/50 border border-slate-100 rounded-2xl">
                            <!-- Chọn phòng -->
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Chọn phòng trọ</label>
                                <select v-if="currentView === 'create'" v-model="selectedRoomName" class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white cursor-pointer">
                                    <option v-for="r in DEFAULT_ROOMS" :key="r.name" :value="r.name">
                                        {{ r.name }} ({{ r.tenant }})
                                    </option>
                                </select>
                                <div v-else class="w-full px-3 py-2 border border-slate-100 rounded-xl text-xs font-semibold bg-slate-100/60 text-slate-500">
                                    {{ selectedRoomName }} ({{ invoiceForm.tenantName }})
                                </div>
                            </div>
                            
                            <!-- Kỳ thanh toán -->
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kỳ thanh toán (Hạn đóng)</label>
                                <input type="date" v-model="invoiceForm.dueDate" class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white text-slate-700 cursor-pointer" />
                            </div>
                        </div>

                        <!-- Rent line -->
                        <div class="flex items-center justify-between p-4 bg-slate-50/50 border border-slate-100 rounded-2xl">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="bi bi-house-door-fill"></i></div>
                                <span class="text-xs font-bold text-slate-700">Tiền phòng</span>
                            </div>
                            <div class="flex flex-col items-end gap-1 text-xs text-slate-500 font-bold">
                                <div class="flex items-center gap-1.5">
                                    <span>Thành tiền:</span>
                                    <input type="number" v-model.number="invoiceForm.rent" class="w-28 px-2 py-1 text-right border border-slate-200 focus:border-emerald-500 rounded-lg outline-none font-bold text-slate-800 bg-white" />
                                </div>
                                <div class="text-[10px] text-emerald-600 font-bold" v-if="invoiceForm.rent">
                                    Bằng số: {{ formatMoney(invoiceForm.rent) }}
                                </div>
                            </div>
                        </div>

                        <!-- Electricity section -->
                        <div class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                            <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center"><i class="bi bi-lightning-charge-fill"></i></div>
                                <span class="text-xs font-bold text-slate-700">Điện</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Số mới</label>
                                    <input type="number" v-model.number="invoiceForm.elecNew" class="w-full px-3 py-1.5 border border-slate-200 focus:border-amber-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Số cũ</label>
                                    <input type="number" v-model.number="invoiceForm.elecOld" class="w-full px-3 py-1.5 border border-slate-200 focus:border-amber-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Chênh lệch</label>
                                    <div class="w-full px-3 py-1.5 bg-slate-50 text-slate-600 rounded-xl text-xs font-semibold text-center border border-slate-100">{{ elecDiff }}</div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ)</label>
                                    <input type="number" v-model.number="invoiceForm.elecPrice" class="w-full px-3 py-1.5 border border-slate-200 focus:border-amber-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1 sm:col-span-1 col-span-2">
                                    <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                    <div class="w-full px-3 py-1.5 bg-slate-50 text-amber-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">{{ formatMoney(elecTotal) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Water section -->
                        <div class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                            <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i class="bi bi-droplet-fill"></i></div>
                                <span class="text-xs font-bold text-slate-700">Nước</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Số mới</label>
                                    <input type="number" v-model.number="invoiceForm.waterNew" class="w-full px-3 py-1.5 border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Số cũ</label>
                                    <input type="number" v-model.number="invoiceForm.waterOld" class="w-full px-3 py-1.5 border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Chênh lệch</label>
                                    <div class="w-full px-3 py-1.5 bg-slate-50 text-slate-600 rounded-xl text-xs font-semibold text-center border border-slate-100">{{ waterDiff }}</div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ)</label>
                                    <input type="number" v-model.number="invoiceForm.waterPrice" class="w-full px-3 py-1.5 border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1 sm:col-span-1 col-span-2">
                                    <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                    <div class="w-full px-3 py-1.5 bg-slate-50 text-blue-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">{{ formatMoney(waterTotal) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Internet section -->
                        <div class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                            <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="bi bi-wifi"></i></div>
                                <span class="text-xs font-bold text-slate-700">Internet</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 items-end">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Số lượng / Số người</label>
                                    <input type="number" v-model.number="invoiceForm.internetQty" class="w-full px-3 py-1.5 border border-slate-200 focus:border-indigo-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ)</label>
                                    <input type="number" v-model.number="invoiceForm.internetPrice" class="w-full px-3 py-1.5 border border-slate-200 focus:border-indigo-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1 col-span-2 sm:col-span-1">
                                    <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                    <div class="w-full px-3 py-1.5 bg-slate-50 text-indigo-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">{{ formatMoney(internetTotal) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Trash section -->
                        <div class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                            <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                                <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center"><i class="bi bi-trash-fill"></i></div>
                                <span class="text-xs font-bold text-slate-700">Rác</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 items-end">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Số lượng / Số người</label>
                                    <input type="number" v-model.number="invoiceForm.trashQty" class="w-full px-3 py-1.5 border border-slate-200 focus:border-rose-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ)</label>
                                    <input type="number" v-model.number="invoiceForm.trashPrice" class="w-full px-3 py-1.5 border border-slate-200 focus:border-rose-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1 col-span-2 sm:col-span-1">
                                    <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                    <div class="w-full px-3 py-1.5 bg-slate-50 text-rose-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">{{ formatMoney(trashTotal) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Parking section -->
                        <div class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                            <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                                <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center"><i class="bi bi-bicycle"></i></div>
                                <span class="text-xs font-bold text-slate-700">Gửi xe</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 items-end">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Số lượng / Số người</label>
                                    <input type="number" v-model.number="invoiceForm.parkingQty" class="w-full px-3 py-1.5 border border-slate-200 focus:border-teal-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-400">Đơn giá (đ)</label>
                                    <input type="number" v-model.number="invoiceForm.parkingPrice" class="w-full px-3 py-1.5 border border-slate-200 focus:border-teal-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
                                </div>
                                <div class="space-y-1 col-span-2 sm:col-span-1">
                                    <label class="text-[10px] font-bold text-slate-400">Thành tiền</label>
                                    <div class="w-full px-3 py-1.5 bg-slate-50 text-teal-600 rounded-xl text-xs font-extrabold text-right border border-slate-100">{{ formatMoney(parkingTotal) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Management / Other section -->
                        <div class="p-4 border border-slate-100 rounded-2xl space-y-3 bg-white">
                            <div class="flex items-center gap-3 border-b border-slate-50 pb-2">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center"><i class="bi bi-gear-fill"></i></div>
                                <span class="text-xs font-bold text-slate-700">Quản lý / Phí khác</span>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400">Thành tiền (đ)</label>
                                <input type="number" v-model.number="invoiceForm.management" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-bold outline-none bg-slate-50/40" placeholder="0" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer actions inside form -->
                    <div class="flex items-center justify-between border-t border-slate-100 pt-5">
                        <button class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                            <i class="bi bi-arrow-repeat"></i> Thay đổi dịch vụ
                        </button>
                        <div class="flex items-center gap-2">
                            <button @click="currentView = 'list'" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors">Hủy bỏ</button>
                            <button @click="saveInvoice" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors">
                                <i class="bi bi-save"></i> {{ currentView === 'create' ? 'Tạo hóa đơn' : 'Lưu thay đổi' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right helper card -->
                <div class="w-full md:w-1/3 bg-slate-50 border border-slate-150 rounded-3xl p-6 space-y-4">
                    <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">Hướng dẫn sử dụng</h4>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Nhập chỉ số điện và nước mới chốt cuối tháng để hệ thống tự động trừ chỉ số cũ và nhân đơn giá tương ứng.
                    </p>
                    <p class="text-[11px] text-slate-500 leading-relaxed font-semibold">
                        Lưu ý: Các phí Internet, Rác, Gửi xe được nhân theo số người ở được đồng bộ từ hợp đồng hiện tại.
                    </p>
                </div>
            </div>
        </div>

        <!-- DETAIL MODAL (Image 1) -->
        <Teleport to="body">
            <div v-if="showViewModal && selectedInvoice" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="closeViewModal">
                <div class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Head -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">Chi tiết hóa đơn</h3>
                        <button @click="closeViewModal" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-8 space-y-6 overflow-y-auto flex-1 bg-white">
                        <div class="text-center space-y-2">
                            <h2 class="text-xl font-extrabold text-slate-800">Hóa đơn tiền nhà</h2>
                            
                            <div class="max-w-md mx-auto grid grid-cols-2 gap-y-2 text-xs font-semibold text-slate-600 text-left pt-4">
                                <div class="text-slate-400">Kỳ thanh toán:</div>
                                <div class="text-slate-800 text-right">{{ new Date(selectedInvoice.dueDate).toLocaleDateString('vi-VN') }}</div>
                                
                                <div class="text-slate-400">Hợp đồng:</div>
                                <div class="text-slate-800 text-right">0GIL47</div>
                                
                                <div class="text-slate-400">Phòng:</div>
                                <div class="text-slate-800 text-right font-bold text-emerald-600">{{ selectedInvoice.room }}</div>
                                
                                <div class="text-slate-400">Khách hàng:</div>
                                <div class="text-slate-800 text-right font-bold text-slate-800">{{ selectedInvoice.tenant }}</div>
                            </div>
                        </div>

                        <!-- Bill table -->
                        <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                            <table class="w-full text-xs text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                        <th class="py-3 px-4 w-12 text-center font-bold">#</th>
                                        <th class="py-3 px-4 font-bold">Chi phí</th>
                                        <th class="py-3 px-4 text-center font-bold">Số lượng</th>
                                        <th class="py-3 px-4 text-right font-bold">Đơn giá</th>
                                        <th class="py-3 px-4 text-right font-bold">Tổng cộng</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 font-semibold text-slate-600">
                                    <!-- Tiền nhà -->
                                    <tr>
                                        <td class="py-3 px-4 text-center text-slate-400">1</td>
                                        <td class="py-3 px-4 font-bold text-slate-800">Tiền nhà</td>
                                        <td class="py-3 px-4 text-center">1</td>
                                        <td class="py-3 px-4 text-right">{{ formatMoney(selectedInvoice.rent) }}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-800">{{ formatMoney(selectedInvoice.rent) }}</td>
                                    </tr>
                                    <!-- Điện -->
                                    <tr>
                                        <td class="py-3 px-4 text-center text-slate-400">2</td>
                                        <td class="py-3 px-4 font-bold text-slate-800">Điện ({{ selectedInvoice.elecNew }} - {{ selectedInvoice.elecOld }})</td>
                                        <td class="py-3 px-4 text-center">{{ selectedInvoice.elecNew - selectedInvoice.elecOld }}</td>
                                        <td class="py-3 px-4 text-right">{{ formatMoney(selectedInvoice.elecPrice) }}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-800">{{ formatMoney((selectedInvoice.elecNew - selectedInvoice.elecOld) * selectedInvoice.elecPrice) }}</td>
                                    </tr>
                                    <!-- Nước -->
                                    <tr>
                                        <td class="py-3 px-4 text-center text-slate-400">3</td>
                                        <td class="py-3 px-4 font-bold text-slate-800">Nước ({{ selectedInvoice.waterNew }} - {{ selectedInvoice.waterOld }})</td>
                                        <td class="py-3 px-4 text-center">{{ selectedInvoice.waterNew - selectedInvoice.waterOld }}</td>
                                        <td class="py-3 px-4 text-right">{{ formatMoney(selectedInvoice.waterPrice) }}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-800">{{ formatMoney((selectedInvoice.waterNew - selectedInvoice.waterOld) * selectedInvoice.waterPrice) }}</td>
                                    </tr>
                                    <!-- Internet -->
                                    <tr>
                                        <td class="py-3 px-4 text-center text-slate-400">4</td>
                                        <td class="py-3 px-4 font-bold text-slate-800">Internet</td>
                                        <td class="py-3 px-4 text-center">{{ selectedInvoice.internetQty }}</td>
                                        <td class="py-3 px-4 text-right">{{ formatMoney(selectedInvoice.internetPrice) }}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-800">{{ formatMoney(selectedInvoice.internetQty * selectedInvoice.internetPrice) }}</td>
                                    </tr>
                                    <!-- Rác -->
                                    <tr>
                                        <td class="py-3 px-4 text-center text-slate-400">5</td>
                                        <td class="py-3 px-4 font-bold text-slate-800">Rác</td>
                                        <td class="py-3 px-4 text-center">{{ selectedInvoice.trashQty }}</td>
                                        <td class="py-3 px-4 text-right">{{ formatMoney(selectedInvoice.trashPrice) }}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-800">{{ formatMoney(selectedInvoice.trashQty * selectedInvoice.trashPrice) }}</td>
                                    </tr>
                                    <!-- Giữ xe -->
                                    <tr>
                                        <td class="py-3 px-4 text-center text-slate-400">6</td>
                                        <td class="py-3 px-4 font-bold text-slate-800">Giữ xe</td>
                                        <td class="py-3 px-4 text-center">{{ selectedInvoice.parkingQty }}</td>
                                        <td class="py-3 px-4 text-right">{{ formatMoney(selectedInvoice.parkingPrice) }}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-800">{{ formatMoney(selectedInvoice.parkingQty * selectedInvoice.parkingPrice) }}</td>
                                    </tr>
                                    <!-- Quản lý nếu có -->
                                    <tr v-if="selectedInvoice.management > 0">
                                        <td class="py-3 px-4 text-center text-slate-400">7</td>
                                        <td class="py-3 px-4 font-bold text-slate-800">Quản lý</td>
                                        <td class="py-3 px-4 text-center">1</td>
                                        <td class="py-3 px-4 text-right">{{ formatMoney(selectedInvoice.management) }}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-800">{{ formatMoney(selectedInvoice.management) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Grand Total -->
                        <div class="flex flex-col items-end gap-1.5 pt-2">
                            <div class="flex items-center gap-12 text-sm font-bold text-slate-700">
                                <span>Tổng cộng</span>
                                <span class="text-lg font-extrabold text-emerald-600">{{ formatMoney(getInvoiceTotal(selectedInvoice)) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Foot -->
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50/50">
                        <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="closeViewModal">Đóng</button>
                        <button class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 flex items-center gap-1.5 transition-colors">
                            <i class="bi bi-envelope"></i> Gửi hóa đơn qua email
                        </button>
                        <button class="px-4 py-2.5 bg-sky-50 hover:bg-sky-100 text-sky-600 border border-sky-100 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors">
                            <i class="bi bi-download"></i> Tải hóa đơn
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>
