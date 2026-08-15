<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed } from 'vue'

const contracts = ref([
    { id: 'HD001', room: 'Phòng 101', tenant: 'Nguyễn Văn A', phone: '0912 345 678', start: '2025-06-01', end: '2026-06-01', rent: 2800000, deposit: 2800000, depositPaid: true,  status: 'active' },
    { id: 'HD002', room: 'Phòng 102', tenant: 'Trần Thị B',   phone: '0987 654 321', start: '2026-01-15', end: '2026-05-15', rent: 2800000, deposit: 2800000, depositPaid: true,  status: 'expiring' },
    { id: 'HD003', room: 'Phòng 201', tenant: 'Lê Minh C',    phone: '0901 111 222', start: '2025-09-01', end: '2026-09-01', rent: 3200000, deposit: 3200000, depositPaid: false, status: 'active' },
    { id: 'HD004', room: 'Phòng 205', tenant: 'Phạm Thị D',   phone: '0933 444 555', start: '2026-04-20', end: '2026-06-20', rent: 3200000, deposit: 3200000, depositPaid: true,  status: 'expiring' },
    { id: 'HD005', room: 'Phòng 301', tenant: 'Hoàng Văn E',  phone: '0966 777 888', start: '2025-03-01', end: '2025-12-31', rent: 3500000, deposit: 3500000, depositPaid: true,  status: 'expired' },
])

const showModal      = ref(false)
const showAddModal   = ref(false)
const selectedContract = ref(null)
const showDeleteConfirm = ref(false)
const deleteTarget   = ref(null)

const daysLeft  = (endDate) => Math.ceil((new Date(endDate) - new Date()) / (1000 * 60 * 60 * 24))
const statusMap = {
    active:   { label: 'Đang Hiệu Lực', cls: 'bg-emerald-50 text-emerald-600 border-emerald-150', dot: 'bg-emerald-500' },
    expiring: { label: 'Sắp Hết Hạn',   cls: 'bg-amber-50 text-amber-600 border-amber-150', dot: 'bg-amber-500' },
    expired:  { label: 'Đã Hết Hạn',    cls: 'bg-slate-50 text-slate-500 border-slate-150', dot: 'bg-slate-500' },
}

const expiringCount = computed(() => contracts.value.filter(c => c.status === 'expiring').length)
const openContract  = (c) => { selectedContract.value = c; showModal.value = true }
const closeModal    = () => { showModal.value = false; selectedContract.value = null }
const askDelete     = (c) => { deleteTarget.value = c; showDeleteConfirm.value = true }
const confirmDelete = () => { contracts.value = contracts.value.filter(c => c.id !== deleteTarget.value.id); showDeleteConfirm.value = false }
const formatMoney   = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'
const formatDate    = (d) => new Date(d).toLocaleDateString('vi-VN')

// Multi-step create contract state
const activeStep = ref(1) // 1: Room & Price, 2: Tenant Info, 3: Terms & Services
const addForm = ref({
    room: '',
    rent: 3000000,
    deposit: 3000000,
    tenant_name: '',
    tenant_phone: '',
    tenant_cccd: '',
    start_date: '',
    end_date: '',
    billing_cycle: 1, // month
    depositPaid: true
})

const openAddContract = () => {
    activeStep.value = 1
    addForm.value = {
        room: '',
        rent: 3000000,
        deposit: 3000000,
        tenant_name: '',
        tenant_phone: '',
        tenant_cccd: '',
        start_date: '',
        end_date: '',
        billing_cycle: 1,
        depositPaid: true
    }
    showAddModal.value = true
}

const submitAddContract = () => {
    if(!addForm.value.room || !addForm.value.tenant_name || !addForm.value.start_date || !addForm.value.end_date) {
        alert('Vui lòng hoàn thành toàn bộ thông tin hợp đồng.')
        return
    }

    const nextId = 'HD' + String(contracts.value.length + 1).padStart(3, '0')
    contracts.value.push({
        id: nextId,
        room: addForm.value.room,
        tenant: addForm.value.tenant_name,
        phone: addForm.value.tenant_phone,
        start: addForm.value.start_date,
        end: addForm.value.end_date,
        rent: addForm.value.rent,
        deposit: addForm.value.deposit,
        depositPaid: addForm.value.depositPaid,
        status: 'active'
    })
    showAddModal.value = false
}
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Hợp đồng</span>
            </div>

            <!-- Page Title -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">Quản lý Hợp đồng</h2>
                    <p class="text-xs text-slate-400">Danh sách hợp đồng thuê phòng và hồ sơ pháp lý đính kèm</p>
                </div>
                <button @click="openAddContract" class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-emerald-500/10 flex items-center gap-1.5">
                    <i class="bi bi-file-earmark-plus"></i> Tạo hợp đồng mới
                </button>
            </div>

            <!-- Expiry Warning Alert -->
            <div v-if="expiringCount > 0" class="p-4 bg-amber-50/70 border border-amber-250 rounded-2xl flex items-center gap-3 text-xs text-amber-800 font-semibold shadow-sm">
                <i class="bi bi-clock-history text-lg text-amber-500"></i>
                <p>
                    Hiện đang có <strong class="text-amber-950">{{ expiringCount }}</strong> hợp đồng chuẩn bị hết hạn trong vòng 30 ngày tới. Vui lòng liên hệ khách để thống nhất gia hạn.
                </p>
            </div>

            <!-- Stats Deck -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="bg-white border border-slate-100 rounded-2xl p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-slate-400">Hợp đồng đang chạy</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ contracts.filter(c=>c.status==='active').length }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="bi bi-file-check-fill"></i>
                    </div>
                </div>

                <div class="bg-white border border-slate-100 rounded-2xl p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-slate-400">Sắp hết hiệu lực</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ expiringCount }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                        <i class="bi bi-clock"></i>
                    </div>
                </div>

                <div class="bg-white border border-slate-100 rounded-2xl p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-xs font-bold text-slate-400">Đã hết hạn</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ contracts.filter(c=>c.status==='expired').length }}</h3>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-500 flex items-center justify-center text-lg">
                        <i class="bi bi-file-x-fill"></i>
                    </div>
                </div>
            </div>

            <!-- Contracts Table -->
            <div class="bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[900px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6">Mã HĐ</th>
                                <th class="py-3.5 px-4">Phòng</th>
                                <th class="py-3.5 px-4">Đại diện thuê</th>
                                <th class="py-3.5 px-4">Ngày hiệu lực</th>
                                <th class="py-3.5 px-4">Ngày kết thúc</th>
                                <th class="py-3.5 px-4">Đặt cọc</th>
                                <th class="py-3.5 px-4">Trạng thái</th>
                                <th class="py-3.5 px-6 text-right font-bold">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-600">
                            <tr v-for="c in contracts" :key="c.id" :class="[
                                'hover:bg-slate-50/40 cursor-pointer',
                                c.status === 'expiring' ? 'bg-amber-50/10' : '',
                                c.status === 'expired' ? 'bg-slate-50/30 opacity-75' : ''
                            ]" @click="openContract(c)">
                                <td class="py-4 px-6 font-bold text-slate-800">{{ c.id }}</td>
                                <td class="py-4 px-4 font-bold text-emerald-600">{{ c.room }}</td>
                                <td class="py-4 px-4">
                                    <div class="flex flex-col">
                                        <span>{{ c.tenant }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold">{{ c.phone }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-500">{{ formatDate(c.start) }}</td>
                                <td class="py-4 px-4 text-slate-500" :class="{ 'text-amber-600 font-bold': c.status === 'expiring' }">{{ formatDate(c.end) }}</td>
                                <td class="py-4 px-4">
                                    <span :class="[
                                        'px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider',
                                        c.depositPaid ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100'
                                    ]">
                                        {{ c.depositPaid ? 'Đã cọc' : 'Chưa cọc' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <span :class="['px-2.5 py-1 rounded-md text-[10px] font-bold border flex items-center gap-1.5 w-fit', statusMap[c.status].cls]">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="statusMap[c.status].dot"></span>
                                        {{ statusMap[c.status].label }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right" @click.stop>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openContract(c)" class="w-7 h-7 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center transition-colors"><i class="bi bi-eye"></i></button>
                                        <button class="w-7 h-7 bg-slate-50 hover:bg-rose-50 text-rose-600 rounded-lg flex items-center justify-center transition-colors"><i class="bi bi-file-earmark-pdf"></i></button>
                                        <button @click="askDelete(c)" class="w-7 h-7 bg-slate-50 hover:bg-rose-100 text-rose-500 rounded-lg flex items-center justify-center transition-colors"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <Teleport to="body">
            <!-- Details Modal -->
            <div v-if="showModal && selectedContract" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="closeModal">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">Chi tiết hợp đồng {{ selectedContract.id }}</h3>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Phòng</span>
                                <p class="text-xs font-bold text-emerald-600">{{ selectedContract.room }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Người đại diện thuê</span>
                                <p class="text-xs font-bold text-slate-800">{{ selectedContract.tenant }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hiệu lực từ</span>
                                <p class="text-xs font-bold text-slate-800">{{ formatDate(selectedContract.start) }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hết hạn vào</span>
                                <p class="text-xs font-bold text-slate-800">{{ formatDate(selectedContract.end) }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tiền thuê</span>
                                <p class="text-xs font-bold text-slate-800">{{ formatMoney(selectedContract.rent) }}/tháng</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Số tiền cọc</span>
                                <p class="text-xs font-bold text-slate-800">{{ formatMoney(selectedContract.deposit) }}</p>
                            </div>
                        </div>

                        <!-- Status Alert info -->
                        <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2 text-slate-600 font-semibold">
                                <i class="bi bi-info-circle text-emerald-500"></i>
                                <span>Thao tác chỉnh sửa sẽ lưu lại lịch sử Audit Log.</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
                        <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="closeModal">Đóng</button>
                        <button class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors">Gia hạn hợp đồng</button>
                    </div>
                </div>
            </div>

            <!-- Multi-step Add Contract Modal -->
            <div v-if="showAddModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="showAddModal = false">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Head -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <div class="space-y-0.5">
                            <h3 class="text-sm font-bold text-slate-800">Tạo hợp đồng thuê mới</h3>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Bước {{ activeStep }} / 3</span>
                        </div>
                        <button @click="showAddModal=false" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Step Indicators -->
                    <div class="px-6 py-3 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center text-[10px] font-bold text-slate-400">
                        <span :class="activeStep >= 1 ? 'text-emerald-500' : ''">1. Phòng & Giá</span>
                        <i class="bi bi-chevron-right"></i>
                        <span :class="activeStep >= 2 ? 'text-emerald-500' : ''">2. Khách thuê</span>
                        <i class="bi bi-chevron-right"></i>
                        <span :class="activeStep >= 3 ? 'text-emerald-500' : ''">3. Hợp đồng</span>
                    </div>

                    <!-- Form Body -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- Step 1: Room & Price -->
                        <div v-if="activeStep === 1" class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Số phòng thuê <span class="text-rose-500">*</span></label>
                                <input v-model="addForm.room" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all" placeholder="VD: Phòng 101"/>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Tiền thuê (đ/tháng)</label>
                                    <input v-model.number="addForm.rent" type="number" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
                                    <div class="text-[10px] text-emerald-600 font-bold mt-0.5" v-if="addForm.rent">
                                        Bằng số: {{ new Intl.NumberFormat('vi-VN').format(addForm.rent) }}đ
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Tiền cọc (đ)</label>
                                    <input v-model.number="addForm.deposit" type="number" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
                                    <div class="text-[10px] text-emerald-600 font-bold mt-0.5" v-if="addForm.deposit">
                                        Bằng số: {{ new Intl.NumberFormat('vi-VN').format(addForm.deposit) }}đ
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 pt-2">
                                <button @click="addForm.depositPaid = !addForm.depositPaid" :class="['w-11 h-6 rounded-full transition-all relative flex items-center p-0.5', addForm.depositPaid ? 'bg-emerald-500' : 'bg-slate-200']">
                                    <span :class="['w-5 h-5 rounded-full bg-white shadow-sm transition-all transform', addForm.depositPaid ? 'translate-x-5' : 'translate-x-0']"></span>
                                </button>
                                <span class="text-xs font-bold text-slate-600">Đã thanh toán tiền đặt cọc</span>
                            </div>
                        </div>

                        <!-- Step 2: Tenant Info -->
                        <div v-if="activeStep === 2" class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Họ và tên khách thuê <span class="text-rose-500">*</span></label>
                                <input v-model="addForm.tenant_name" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all" placeholder="VD: Nguyễn Văn A"/>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Số điện thoại <span class="text-rose-500">*</span></label>
                                <input v-model="addForm.tenant_phone" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all" placeholder="VD: 0912345678"/>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Căn cước công dân (CCCD)</label>
                                <input v-model="addForm.tenant_cccd" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all" placeholder="VD: 036091234567"/>
                            </div>
                        </div>

                        <!-- Step 3: Terms & Services -->
                        <div v-if="activeStep === 3" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Ngày hiệu lực <span class="text-rose-500">*</span></label>
                                    <input v-model="addForm.start_date" type="date" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Ngày hết hạn <span class="text-rose-500">*</span></label>
                                    <input v-model="addForm.end_date" type="date" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Chu kỳ đóng tiền (tháng/lần)</label>
                                <input v-model.number="addForm.billing_cycle" type="number" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
                            </div>
                        </div>
                    </div>

                    <!-- Foot -->
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <button 
                            v-if="activeStep > 1"
                            class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" 
                            @click="activeStep--"
                        >
                            Quay lại
                        </button>
                        <div v-else></div>

                        <div class="flex items-center gap-2">
                            <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="showAddModal = false">Hủy</button>
                            <button 
                                v-if="activeStep < 3"
                                class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors"
                                @click="activeStep++"
                            >
                                Tiếp tục
                            </button>
                            <button 
                                v-else
                                class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors"
                                @click="submitAddContract"
                            >
                                Ký hợp đồng
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirm delete modal -->
            <div v-if="showDeleteConfirm" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden">
                    <div class="p-6 text-center space-y-4">
                        <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center text-xl mx-auto">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-slate-800">Xác nhận xóa hợp đồng</h4>
                            <p class="text-xs text-slate-400">Bạn có chắc chắn muốn xóa hợp đồng phòng {{ deleteTarget?.room }}? Hành động này không thể hoàn tác.</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
                        <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="showDeleteConfirm = false">Hủy</button>
                        <button class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow-md shadow-rose-500/10 transition-colors" @click="confirmDelete">Xóa</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>
