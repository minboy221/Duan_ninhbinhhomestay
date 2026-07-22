<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed, reactive, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { showSuccess, showError, showWarning, showConfirm, showToast } from '@/Utils/swal'

const props = defineProps({
    invoices: {
        type: Array,
        default: () => []
    },
    activeContracts: {
        type: Array,
        default: () => []
    },
    services: {
        type: Array,
        default: () => []
    }
})

const currentView = ref('list') // 'list' | 'create' | 'edit'
const selectedContractId = ref(null)
const selectedInvoiceId = ref(null)

const month = ref(new Date().toISOString().substring(0, 7)) // e.g. "2026-06"
const houseFilter = ref('all')
const startDate = ref('')
const endDate = ref('')
const searchQ = ref('')
const activeTab = ref('all') // 'all' | 'open' | 'completed'

// Form state
const invoiceForm = reactive({
    rent: 0,
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
    dueDate: new Date().toISOString().split('T')[0]
})

const makeMoneyComputed = (field) => computed({
    get() {
        const val = invoiceForm[field]
        if (val === null || val === undefined || val === '') return ''
        return new Intl.NumberFormat('en-US').format(val)
    },
    set(v) {
        const raw = String(v).replace(/\D/g, '')
        invoiceForm[field] = raw ? parseInt(raw, 10) : 0
    }
})

const displayRentPrice = makeMoneyComputed('rent')
const displayElecPrice = makeMoneyComputed('elecPrice')
const displayWaterPrice = makeMoneyComputed('waterPrice')
const displayInternetPrice = makeMoneyComputed('internetPrice')
const displayTrashPrice = makeMoneyComputed('trashPrice')
const displayParkingPrice = makeMoneyComputed('parkingPrice')
const displayManagementPrice = makeMoneyComputed('management')

// Find services lookup helper
const elecService = computed(() => props.services.find(s => s.type === 'per_kwh' || s.name.includes('Điện')))
const waterService = computed(() => props.services.find(s => s.type === 'per_m3' || s.name.includes('Nước')))
const internetService = computed(() => props.services.find(s => s.name.includes('Mạng') || s.name.includes('Internet')))
const trashService = computed(() => props.services.find(s => s.name.includes('Rác')))
const parkingService = computed(() => props.services.find(s => s.name.includes('Xe') || s.name.includes('Gửi xe')))

// Update form when contract changes
watch(selectedContractId, (newContractId) => {
    if (!newContractId) return
    const contract = props.activeContracts.find(c => c.id === newContractId)
    if (contract) {
        invoiceForm.rent = contract.room?.price || 0
        
        // Find last invoice for old indexes
        const lastInv = props.invoices.find(i => i.contract_id === newContractId)
        
        // Electricity
        const lastElecDetail = lastInv?.details?.find(d => d.item_name.includes('Điện'))
        invoiceForm.elecOld = lastElecDetail ? (lastElecDetail.new_index ?? lastElecDetail.new_index ?? 0) : 0
        invoiceForm.elecNew = invoiceForm.elecOld + 100
        invoiceForm.elecPrice = elecService.value ? Number(elecService.value.price) : 3000

        // Water
        const lastWaterDetail = lastInv?.details?.find(d => d.item_name.includes('Nước'))
        invoiceForm.waterOld = lastWaterDetail ? (lastWaterDetail.new_index ?? lastWaterDetail.new_index ?? 0) : 0
        invoiceForm.waterNew = invoiceForm.waterOld + 10
        invoiceForm.waterPrice = waterService.value ? Number(waterService.value.price) : 15000

        // Fixed services
        invoiceForm.internetPrice = internetService.value ? Number(internetService.value.price) : 50000
        invoiceForm.trashPrice = trashService.value ? Number(trashService.value.price) : 30000
        invoiceForm.parkingPrice = parkingService.value ? Number(parkingService.value.price) : 15000
        
        invoiceForm.internetQty = 1
        invoiceForm.trashQty = 1
        invoiceForm.parkingQty = 1
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

// Filters
const filteredInvoices = computed(() => {
    return props.invoices.filter(inv => {
        if (activeTab.value !== 'all') {
            const isPaid = inv.status === 'paid'
            if (activeTab.value === 'open' && isPaid) return false
            if (activeTab.value === 'completed' && !isPaid) return false
        }
        if (searchQ.value) {
            const q = searchQ.value.toLowerCase()
            const roomNum = inv.contract?.room?.room_number?.toLowerCase() || ''
            const tenantName = inv.contract?.tenant?.name?.toLowerCase() || ''
            return roomNum.includes(q) || tenantName.includes(q)
        }
        return true
    })
})

const formatMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + ' đ'

// Action triggers
const goCreate = () => {
    currentView.value = 'create'
    selectedContractId.value = props.activeContracts[0]?.id || null
    invoiceForm.dueDate = new Date().toISOString().split('T')[0]
}

const goEdit = (inv) => {
    selectedInvoiceId.value = inv.id
    selectedContractId.value = inv.contract_id
    
    invoiceForm.rent = Number(inv.details.find(d => d.item_name === 'Tiền thuê nhà')?.price || 0)
    
    const elec = inv.details.find(d => d.item_name.includes('Điện'))
    invoiceForm.elecOld = elec ? (elec.old_index ?? 0) : 0
    invoiceForm.elecNew = elec ? (elec.new_index ?? elec.new_index ?? 0) : 0
    invoiceForm.elecPrice = elec ? Number(elec.price) : 3000

    const water = inv.details.find(d => d.item_name.includes('Nước'))
    invoiceForm.waterOld = water ? (water.old_index ?? 0) : 0
    invoiceForm.waterNew = water ? (water.new_index ?? water.new_index ?? 0) : 0
    invoiceForm.waterPrice = water ? Number(water.price) : 15000

    const internet = inv.details.find(d => d.item_name.includes('internet'))
    invoiceForm.internetQty = internet ? Number(internet.quantity) : 1
    invoiceForm.internetPrice = internet ? Number(internet.price) : 50000

    const trash = inv.details.find(d => d.item_name.includes('rác'))
    invoiceForm.trashQty = trash ? Number(trash.quantity) : 1
    invoiceForm.trashPrice = trash ? Number(trash.price) : 30000

    const parking = inv.details.find(d => d.item_name.includes('gửi xe'))
    invoiceForm.parkingQty = parking ? Number(parking.quantity) : 1
    invoiceForm.parkingPrice = parking ? Number(parking.price) : 15000

    const management = inv.details.find(d => d.item_name.includes('khác') || d.item_name.includes('Quản lý') || d.item_name.includes('dịch vụ'))
    invoiceForm.management = management ? Number(management.price) : 0

    invoiceForm.dueDate = inv.due_date || new Date().toISOString().split('T')[0]
    
    currentView.value = 'edit'
}

const submitForm = useForm({
    contract_id: null,
    billing_month: '',
    due_date: '',
    details: []
})

const saveInvoice = () => {
    if (!selectedContractId.value) {
        showWarning('Thiếu thông tin', 'Vui lòng chọn hợp đồng!')
        return
    }

    const details = [
        {
            item_name: 'Tiền thuê nhà',
            price: Number(invoiceForm.rent),
            quantity: 1,
            subtotal: Number(invoiceForm.rent),
            service_id: null
        },
        {
            item_name: 'Tiền điện',
            price: Number(invoiceForm.elecPrice),
            quantity: elecDiff.value,
            subtotal: elecTotal.value,
            old_index: Number(invoiceForm.elecOld),
            new_index: Number(invoiceForm.elecNew),
            service_id: elecService.value?.id || null
        },
        {
            item_name: 'Tiền nước',
            price: Number(invoiceForm.waterPrice),
            quantity: waterDiff.value,
            subtotal: waterTotal.value,
            old_index: Number(invoiceForm.waterOld),
            new_index: Number(invoiceForm.waterNew),
            service_id: waterService.value?.id || null
        },
        {
            item_name: 'Tiền internet',
            price: Number(invoiceForm.internetPrice),
            quantity: Number(invoiceForm.internetQty),
            subtotal: internetTotal.value,
            service_id: internetService.value?.id || null
        },
        {
            item_name: 'Tiền rác',
            price: Number(invoiceForm.trashPrice),
            quantity: Number(invoiceForm.trashQty),
            subtotal: trashTotal.value,
            service_id: trashService.value?.id || null
        },
        {
            item_name: 'Tiền gửi xe',
            price: Number(invoiceForm.parkingPrice),
            quantity: Number(invoiceForm.parkingQty),
            subtotal: parkingTotal.value,
            service_id: parkingService.value?.id || null
        }
    ]

    if (Number(invoiceForm.management) > 0) {
        details.push({
            item_name: 'Phí dịch vụ khác',
            price: Number(invoiceForm.management),
            quantity: 1,
            subtotal: Number(invoiceForm.management),
            service_id: null
        })
    }

    submitForm.contract_id = selectedContractId.value
    submitForm.billing_month = month.value
    submitForm.due_date = invoiceForm.dueDate
    submitForm.details = details

    if (currentView.value === 'create') {
        submitForm.post(route('landlord.invoices.store'), {
            onSuccess: () => {
                showSuccess('Thành công', 'Lưu hóa đơn thành công!')
                currentView.value = 'list'
            }
        })
    } else {
        submitForm.put(route('landlord.invoices.update', selectedInvoiceId.value), {
            onSuccess: () => {
                showSuccess('Thành công', 'Cập nhật hóa đơn thành công!')
                currentView.value = 'list'
            }
        })
    }
}

const updateInvoiceStatus = (inv, status) => {
    const statusForm = useForm({ status: status })
    statusForm.patch(route('landlord.invoices.status', inv.id), {
        onSuccess: () => {
            showSuccess('Thành công', 'Cập nhật trạng thái thành công!')
        }
    })
}

const deleteInvoice = async (id) => {
    const confirmed = await showConfirm(
        'Xóa hóa đơn',
        'Bạn có chắc chắn muốn xóa hóa đơn này?',
        'Xóa hóa đơn',
        'Hủy'
    );
    if (confirmed) {
        const deleteForm = useForm({})
        deleteForm.delete(route('landlord.invoices.delete', id), {
            onSuccess: () => {
                showSuccess('Đã xóa', 'Xóa hóa đơn thành công!')
                if (selectedInvoice.value && selectedInvoice.value.id === id) {
                    closeViewModal()
                }
            }
        })
    }
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

const copyInvoiceToClipboard = (inv) => {
    if (!inv) return
    let text = `--- HÓA ĐƠN TIỀN NHÀ ---\n`;
    text += `Phòng: ${inv.contract?.room?.room_number || ''}\n`;
    text += `Khách thuê: ${inv.contract?.tenant?.name || ''}\n`;
    text += `Kỳ thanh toán: Tháng ${inv.billing_month || ''}\n`;
    text += `Hạn đóng: ${inv.due_date || ''}\n`;
    text += `-------------------------\n`;
    inv.details.forEach(d => {
        let calc = '';
        if (d.old_index !== null) {
            calc = ` (${d.new_index} - ${d.old_index} = ${d.quantity})`;
        } else if (d.quantity > 1) {
            calc = ` (${d.quantity}x)`;
        }
        text += `${d.item_name}${calc}: ${formatMoney(d.subtotal)}\n`;
    });
    text += `-------------------------\n`;
    text += `TỔNG CỘNG: ${formatMoney(inv.total_amount)}\n`;
    
    navigator.clipboard.writeText(text).then(() => {
        showToast('Đã sao chép nội dung hóa đơn để gửi Zalo/SMS!');
    }).catch(err => {
        showError('Không thể sao chép', err.toString());
    });
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
                <button @click="activeTab = 'open'" :class="['pb-2 border-b-2 transition-colors -mb-[13px]', activeTab === 'open' ? 'border-emerald-600 text-emerald-600' : 'border-transparent hover:text-slate-600']">Đang mở <span class="ml-1 px-1.5 py-0.5 bg-emerald-50 text-emerald-600 rounded-full text-[10px]">{{ invoices.filter(i=>i.status!=='paid').length }}</span></button>
                <button @click="activeTab = 'completed'" :class="['pb-2 border-b-2 transition-colors -mb-[13px]', activeTab === 'completed' ? 'border-emerald-600 text-emerald-600' : 'border-transparent hover:text-slate-600']">Hoàn thành <span class="ml-1 px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded-full text-[10px]">{{ invoices.filter(i=>i.status==='paid').length }}</span></button>
            </div>

            <!-- Filters Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-white border border-slate-100 rounded-2xl p-4 shadow-sm">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-slate-400">Kỳ thanh toán</label>
                    <input type="month" v-model="month" class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/50 cursor-pointer" />
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

            <!-- Invoices Desktop Table List -->
            <div class="hidden lg:block bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1000px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4 w-12 text-center">
                                    <input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                </th>
                                <th class="py-3.5 px-4">Kỳ thanh toán</th>
                                <th class="py-3.5 px-4">Mã hóa đơn</th>
                                <th class="py-3.5 px-4">Phòng</th>
                                <th class="py-3.5 px-4 text-right">Tổng cộng</th>
                                <th class="py-3.5 px-4 text-center">Tình trạng</th>
                                <th class="py-3.5 px-4">Khách hàng</th>
                                <th class="py-3.5 px-6 text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-600">
                            <tr v-for="inv in filteredInvoices" :key="inv.id" class="hover:bg-slate-50/40">
                                <td class="py-4 px-4 text-center">
                                    <input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-bold text-slate-800">Tháng {{ inv.billing_month }}</div>
                                    <div class="text-[10px] text-slate-400 font-semibold">Hạn: {{ inv.due_date }}</div>
                                </td>
                                <td class="py-4 px-4 text-slate-500 font-medium">#{{ inv.invoice_code }}</td>
                                <td class="py-4 px-4 text-slate-800 font-bold">Phòng {{ inv.contract?.room?.room_number }}</td>
                                <td class="py-4 px-4 text-right text-rose-500 font-bold">{{ formatMoney(inv.total_amount) }}</td>
                                <td class="py-4 px-4 text-center">
                                    <div class="flex justify-center">
                                        <span v-if="inv.status === 'paid'" class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-md text-[10px] font-bold">
                                            Hoàn Thành
                                        </span>
                                        <span v-else class="px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-md text-[10px] font-bold">
                                            Đang Mở (Chưa thu)
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-[10px]">
                                            {{ inv.contract?.tenant?.name?.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="text-slate-700 font-semibold">{{ inv.contract?.tenant?.name }}</div>
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
                                        <button v-if="inv.status !== 'paid'" @click="changeStatus(inv, 'paid')" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-100 rounded-lg text-[10px] font-bold flex items-center gap-1">
                                            <i class="bi bi-check-lg"></i> Thu tiền
                                        </button>
                                        <button v-else @click="changeStatus(inv, 'unpaid')" class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-600 border border-amber-100 rounded-lg text-[10px] font-bold flex items-center gap-1">
                                             Chưa thu
                                        </button>
                                        <button @click="deleteInvoice(inv.id)" class="w-7 h-7 bg-slate-50 hover:bg-rose-50 hover:text-rose-600 text-slate-500 rounded-lg flex items-center justify-center" title="Xóa hóa đơn">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredInvoices.length === 0">
                                <td colspan="8" class="text-center py-6 text-slate-400 font-bold">Không tìm thấy hóa đơn nào</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoices Mobile Card List -->
            <div class="block lg:hidden space-y-4">
                <div v-for="inv in filteredInvoices" :key="inv.id" class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hóa đơn: #{{ inv.invoice_code }}</div>
                            <div class="text-sm font-black text-slate-800 mt-1">Phòng {{ inv.contract?.room?.room_number }}</div>
                            <div class="text-xs text-slate-500 font-semibold mt-0.5">Khách: <span class="text-slate-700 font-bold">{{ inv.contract?.tenant?.name }}</span></div>
                        </div>
                        <div>
                            <span v-if="inv.status === 'paid'" class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-md text-[10px] font-bold">
                                Hoàn Thành
                            </span>
                            <span v-else class="px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-md text-[10px] font-bold">
                                Chưa thu
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center pt-3 border-t border-slate-50 text-xs">
                        <div>
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Kỳ đóng tiền</div>
                            <div class="font-bold text-slate-700 mt-0.5">Tháng {{ inv.billing_month }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Tổng thu</div>
                            <div class="font-black text-rose-500 mt-0.5">{{ formatMoney(inv.total_amount) }}</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-1.5 pt-3 border-t border-slate-50">
                        <button @click="openViewModal(inv)" class="w-8 h-8 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-600 text-slate-500 rounded-xl flex items-center justify-center" title="Xem chi tiết">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button @click="goEdit(inv)" class="w-8 h-8 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-600 text-slate-500 rounded-xl flex items-center justify-center" title="Chỉnh sửa">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button v-if="inv.status !== 'paid'" @click="changeStatus(inv, 'paid')" class="px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border border-emerald-100 rounded-xl text-xs font-extrabold flex items-center gap-1">
                            <i class="bi bi-check-lg"></i> Thu tiền
                        </button>
                        <button @click="deleteInvoice(inv.id)" class="w-8 h-8 bg-slate-50 hover:bg-rose-50 hover:text-rose-600 text-slate-500 rounded-xl flex items-center justify-center" title="Xóa">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div v-if="filteredInvoices.length === 0" class="text-center py-8 text-slate-400 font-bold text-xs">
                    Không tìm thấy hóa đơn nào
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT VIEW -->
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
                    <div class="flex justify-between items-center border-b border-slate-50 pb-4 gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg font-bold shrink-0">
                                <i class="bi bi-house"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-slate-800 text-sm truncate">
                                    {{ currentView === 'create' ? 'Tạo hóa đơn tháng' : 'Sửa hóa đơn' }}
                                </h3>
                                <p class="text-[10px] text-slate-400 font-semibold mt-0.5 truncate">Vui lòng cập nhật các chỉ số sử dụng</p>
                            </div>
                        </div>
                        
                        <div class="text-right shrink-0">
                            <span class="text-[9px] sm:text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Tổng cộng</span>
                            <span class="text-lg sm:text-2xl font-black text-rose-500 whitespace-nowrap">{{ formatMoney(formTotal) }}</span>
                        </div>
                    </div>

                    <!-- Services Indices List -->
                    <div class="space-y-6">
                        <!-- Room & Billing cycle selectors -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 bg-slate-50/50 border border-slate-100 rounded-2xl">
                            <!-- Chọn hợp đồng / phòng -->
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Chọn Hợp đồng - Phòng trọ</label>
                                <select v-if="currentView === 'create'" v-model="selectedContractId" class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white cursor-pointer">
                                    <option v-for="c in activeContracts" :key="c.id" :value="c.id">
                                        Phòng {{ c.room?.room_number }} ({{ c.tenant?.name }})
                                    </option>
                                </select>
                                <div v-else class="w-full px-3 py-2 border border-slate-100 rounded-xl text-xs font-semibold bg-slate-100/60 text-slate-500">
                                    Phòng {{ activeContracts.find(c => c.id === selectedContractId)?.room?.room_number }} ({{ activeContracts.find(c => c.id === selectedContractId)?.tenant?.name }})
                                </div>
                            </div>
                            
                            <!-- Tháng thanh toán -->
                            <div class="space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kỳ hóa đơn (tháng)</label>
                                <input type="month" v-model="month" class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white text-slate-700 cursor-pointer" />
                            </div>

                            <!-- Hạn đóng -->
                            <div class="space-y-1 sm:col-span-3">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hạn đóng tiền</label>
                                <input type="date" v-model="invoiceForm.dueDate" class="w-full px-3 py-2 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-semibold outline-none bg-white text-slate-700 cursor-pointer" />
                            </div>
                        </div>

                        <!-- Rent line -->
                        <div class="flex items-center justify-between p-4 bg-slate-50/50 border border-slate-100 rounded-2xl">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="bi bi-house-door-fill"></i></div>
                                <span class="text-xs font-bold text-slate-700">Tiền phòng</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-slate-500 font-bold">
                                <span>Thành tiền:</span>
                                <input type="text" v-model="displayRentPrice" class="w-32 px-2 py-1 text-right border border-slate-200 focus:border-emerald-500 rounded-lg outline-none font-bold text-slate-800 bg-white" />
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
                                    <input type="text" v-model="displayElecPrice" class="w-full px-3 py-1.5 border border-slate-200 focus:border-amber-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
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
                                    <input type="text" v-model="displayWaterPrice" class="w-full px-3 py-1.5 border border-slate-200 focus:border-blue-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
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
                                    <input type="text" v-model="displayInternetPrice" class="w-full px-3 py-1.5 border border-slate-200 focus:border-indigo-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
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
                                    <input type="text" v-model="displayTrashPrice" class="w-full px-3 py-1.5 border border-slate-200 focus:border-rose-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
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
                                    <input type="text" v-model="displayParkingPrice" class="w-full px-3 py-1.5 border border-slate-200 focus:border-teal-500 rounded-xl text-xs font-semibold outline-none bg-slate-50/40" />
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
                                <input type="text" v-model="displayManagementPrice" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-bold outline-none bg-slate-50/40" placeholder="0" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer actions inside form -->
                    <div class="flex items-center justify-between border-t border-slate-100 pt-5">
                        <div></div>
                        <div class="flex items-center gap-2">
                            <button @click="currentView = 'list'" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors">Hủy bỏ</button>
                            <button @click="saveInvoice" class="px-5 py-2.5 bg-[#0e3b3e] hover:bg-[#09282a] text-white font-bold text-xs rounded-xl shadow-md transition-colors" :disabled="submitForm.processing">
                                <i class="bi bi-save"></i> {{ submitForm.processing ? 'Đang lưu...' : (currentView === 'create' ? 'Tạo hóa đơn' : 'Lưu thay đổi') }}
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

        <!-- DETAIL MODAL -->
        <Teleport to="body">
            <div v-if="showViewModal && selectedInvoice" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-0 sm:p-4" @click.self="closeViewModal">
                <div class="bg-white rounded-t-[32px] sm:rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] sm:max-h-[90vh]">
                    <!-- Head -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-xs sm:text-sm font-bold text-slate-800">Chi tiết hóa đơn #{{ selectedInvoice.invoice_code }}</h3>
                        <button @click="closeViewModal" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-5 sm:p-8 space-y-6 overflow-y-auto flex-1 bg-white scrollbar-thin">
                        <div class="text-center space-y-2">
                            <h2 class="text-lg sm:text-xl font-extrabold text-slate-800">Hóa đơn tiền nhà</h2>
                            
                            <!-- Payment Status Badge -->
                            <div class="flex justify-center mt-1">
                                <span v-if="selectedInvoice.status === 'paid'" class="px-3 py-1 bg-blue-50 text-blue-600 border border-blue-100 rounded-full text-[11px] font-extrabold flex items-center gap-1 uppercase tracking-wide">
                                    <i class="bi bi-check-circle-fill"></i> Đã thanh toán
                                </span>
                                <span v-else class="px-3 py-1 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full text-[11px] font-extrabold flex items-center gap-1 uppercase tracking-wide animate-pulse">
                                    <i class="bi bi-clock-fill"></i> Chưa thanh toán
                                </span>
                            </div>
                            
                            <div class="max-w-md mx-auto grid grid-cols-2 gap-y-2.5 text-xs font-semibold text-slate-600 text-left pt-5 border-b border-dashed border-slate-200 pb-5">
                                <div class="text-slate-400">Kỳ thanh toán:</div>
                                <div class="text-slate-800 text-right">Tháng {{ selectedInvoice.billing_month }}</div>
                                
                                <div class="text-slate-400">Mã hóa đơn:</div>
                                <div class="text-slate-800 text-right font-mono font-bold">#{{ selectedInvoice.invoice_code }}</div>
                                
                                <div class="text-slate-400">Phòng:</div>
                                <div class="text-slate-800 text-right font-black text-emerald-600">Phòng {{ selectedInvoice.contract?.room?.room_number }}</div>
                                
                                <div class="text-slate-400">Khách hàng:</div>
                                <div class="text-slate-800 text-right font-bold text-slate-700">{{ selectedInvoice.contract?.tenant?.name }}</div>

                                <div v-if="selectedInvoice.due_date" class="text-slate-400">Hạn đóng tiền:</div>
                                <div v-if="selectedInvoice.due_date" class="text-right text-rose-500 font-bold">{{ selectedInvoice.due_date }}</div>
                            </div>
                        </div>

                        <!-- Bill table (Desktop) -->
                        <div class="hidden sm:block border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
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
                                    <tr v-for="(detail, index) in selectedInvoice.details" :key="detail.id">
                                        <td class="py-3 px-4 text-center text-slate-400">{{ index + 1 }}</td>
                                        <td class="py-3 px-4 font-bold text-slate-800">
                                            {{ detail.item_name }}
                                            <span v-if="detail.old_index !== null" class="text-slate-400 font-normal"> ({{ detail.new_index }} - {{ detail.old_index }})</span>
                                        </td>
                                        <td class="py-3 px-4 text-center">{{ detail.quantity }}</td>
                                        <td class="py-3 px-4 text-right">{{ formatMoney(detail.price) }}</td>
                                        <td class="py-3 px-4 text-right font-bold text-slate-800">{{ formatMoney(detail.subtotal) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Receipt Rows list (Mobile-First Receipt View) -->
                        <div class="block sm:hidden space-y-4">
                            <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-1.5">Chi tiết chi phí</div>
                            <div v-for="detail in selectedInvoice.details" :key="detail.id" class="flex justify-between items-start py-1.5 text-xs font-semibold">
                                <div class="space-y-0.5">
                                    <div class="text-slate-800 font-bold">{{ detail.item_name }}</div>
                                    <!-- Detailed calculations under item name -->
                                    <div class="text-[10px] text-slate-400 font-medium">
                                        <span v-if="detail.old_index !== null">
                                            Chỉ số: {{ detail.new_index }} - {{ detail.old_index }} = {{ detail.quantity }} &times; {{ formatMoney(detail.price) }}
                                        </span>
                                        <span v-else-if="detail.quantity > 1">
                                            Số lượng: {{ detail.quantity }} &times; {{ formatMoney(detail.price) }}
                                        </span>
                                        <span v-else>
                                            Đơn giá: {{ formatMoney(detail.price) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-slate-800 font-bold text-right">{{ formatMoney(detail.subtotal) }}</div>
                            </div>
                        </div>

                        <!-- Tear-off dashed divider line -->
                        <div class="border-t-2 border-dashed border-slate-200 pt-4 mt-6"></div>

                        <!-- Grand Total -->
                        <div class="flex justify-between items-center py-3 bg-slate-50 rounded-2xl px-5">
                            <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Tổng cộng</span>
                            <span class="text-lg font-black text-rose-500">{{ formatMoney(selectedInvoice.total_amount) }}</span>
                        </div>
                    </div>

                    <!-- Foot -->
                    <div class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-2.5 bg-slate-50/50">
                        <!-- Action buttons for Mobile and Desktop -->
                        <button @click="copyInvoiceText(selectedInvoice)" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-colors">
                            <i class="bi bi-send-fill text-emerald-600"></i> Gửi Zalo / SMS
                        </button>
                        
                        <button v-if="selectedInvoice.status !== 'paid'" @click="changeStatus(selectedInvoice, 'paid'); closeViewModal()" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-colors shadow-sm shadow-emerald-600/10">
                            <i class="bi bi-check-lg"></i> Xác nhận đã thu
                        </button>
                        <button v-else @click="changeStatus(selectedInvoice, 'unpaid'); closeViewModal()" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-colors">
                             Chưa thu tiền
                        </button>
                        
                        <button class="px-4 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors text-center" @click="closeViewModal">Đóng</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>
