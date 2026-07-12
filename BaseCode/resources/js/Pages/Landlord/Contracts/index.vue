<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'

const props = defineProps({
    dbContracts: Array,
    appointments: Array
})

const contracts = computed(() => {
    return (props.dbContracts || []).map(c => ({
        id: c.id,
        room: c.room ? c.room.room_number : '',
        tenant: c.tenant ? c.tenant.name : '',
        phone: c.tenant ? c.tenant.phone : '',
        start: c.start_date,
        end: c.end_date,
        rent: c.monthly_rent,
        deposit: c.deposit_amount,
        depositPaid: true,
        status: c.status,
        original_contract: c,
    }))
})

const showModal      = ref(false)
const showAddModal   = ref(false)
const showUploadModal = ref(false)
const selectedContract = ref(null)
const showDeleteConfirm = ref(false)
const deleteTarget   = ref(null)

const daysLeft  = (endDate) => Math.ceil((new Date(endDate) - new Date()) / (1000 * 60 * 60 * 24))
const statusMap = {
    active:   { label: 'Đang Hiệu Lực', cls: 'bg-emerald-50 text-emerald-600 border-emerald-150', dot: 'bg-emerald-500' },
    draft:    { label: 'Bản Nháp',      cls: 'bg-slate-50 text-slate-600 border-slate-150', dot: 'bg-slate-500' },
    awaiting_upload: { label: 'Chờ Upload Ảnh', cls: 'bg-amber-50 text-amber-600 border-amber-150', dot: 'bg-amber-500' },
    expired:  { label: 'Đã Hết Hạn',    cls: 'bg-rose-50 text-rose-600 border-rose-150', dot: 'bg-rose-500' },
    cancelled:{ label: 'Đã Hủy',        cls: 'bg-rose-50 text-rose-500 border-rose-150', dot: 'bg-rose-500' },
    expiring: { label: 'Sắp Hết Hạn',   cls: 'bg-amber-50 text-amber-600 border-amber-150', dot: 'bg-amber-500' },
}

const expiringCount = computed(() => contracts.value.filter(c => c.status === 'expiring').length)
const openContract  = (c) => { selectedContract.value = c; showModal.value = true }
const closeModal    = () => { showModal.value = false; selectedContract.value = null }
const askDelete     = (c) => { deleteTarget.value = c; showDeleteConfirm.value = true }
const confirmDelete = () => { contracts.value = contracts.value.filter(c => c.id !== deleteTarget.value.id); showDeleteConfirm.value = false }
const formatMoney   = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'
const formatDate    = (d) => new Date(d).toLocaleDateString('vi-VN')

const uploadForm = useForm({
    signed_image: null,
})

const openUploadModal = (c) => {
    selectedContract.value = c;
    showUploadModal.value = true;
}

const submitUpload = () => {
    uploadForm.post(`/landlord/contracts/${selectedContract.value.id}/upload-signed`, {
        forceFormData: true,
        onSuccess: () => {
            showUploadModal.value = false;
            uploadForm.reset();
        }
    })
}

// Multi-step create contract state
const activeStep = ref(1) // 1: Room & Price, 2: Tenant Info, 3: Terms & Services
const addForm = ref({
    appointment_id: '',
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

watch(() => addForm.value.appointment_id, (newVal) => {
    if (newVal) {
        const apt = props.appointments.find(a => a.id === newVal)
        if (apt) {
            addForm.value.room = apt.room ? apt.room.room_number : ''
            addForm.value.tenant_name = apt.user ? apt.user.name : ''
            addForm.value.tenant_phone = apt.user ? apt.user.phone : ''
            addForm.value.rent = apt.room ? apt.room.price : 3000000
        }
    }
})

const openAddContract = () => {
    activeStep.value = 1
    addForm.value = {
        appointment_id: '',
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
    if(!addForm.value.appointment_id || !addForm.value.start_date || !addForm.value.end_date) {
        alert('Vui lòng hoàn thành toàn bộ thông tin hợp đồng.')
        return
    }
    
    // Convert current data to form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/landlord/contracts/store-draft';
    form.target = '_blank'; // Open PDF in new tab
    
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content || '';
    
    const inputs = {
        _token: csrfToken,
        appointment_id: addForm.value.appointment_id,
        start_date: addForm.value.start_date,
        end_date: addForm.value.end_date,
        monthly_rent: addForm.value.rent
    };
    
    for (const key in inputs) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = inputs[key];
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    showAddModal.value = false
    
    // Reload data after a short delay since it opens in a new tab
    setTimeout(() => {
        router.reload({ only: ['dbContracts'] });
    }, 1500);
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
                                        <a v-if="c.original_contract?.contract_file_path" :href="`/storage/${c.original_contract.contract_file_path}`" target="_blank" class="w-7 h-7 bg-slate-50 hover:bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center transition-colors"><i class="bi bi-file-earmark-pdf"></i></a>
                                        <button v-if="c.status === 'awaiting_upload'" @click="openUploadModal(c)" class="w-7 h-7 bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center transition-colors"><i class="bi bi-upload"></i></button>
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
                                <label class="text-xs font-bold text-slate-500">Chọn lịch hẹn khách ký HĐ <span class="text-rose-500">*</span></label>
                                <select v-model="addForm.appointment_id" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all">
                                    <option value="" disabled>-- Chọn khách đã hẹn --</option>
                                    <option v-for="apt in props.appointments" :key="apt.id" :value="apt.id">
                                        Phòng {{ apt.room?.room_number }} - {{ apt.user?.name }} ({{ apt.user?.phone }})
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Số phòng thuê <span class="text-rose-500">*</span></label>
                                <input v-model="addForm.room" readonly class="w-full bg-slate-50 px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Tiền thuê (đ/tháng)</label>
                                    <input v-model.number="addForm.rent" type="number" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Tiền cọc (đ)</label>
                                    <input v-model.number="addForm.deposit" type="number" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
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
                                <input v-model="addForm.tenant_name" readonly class="w-full bg-slate-50 px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Số điện thoại <span class="text-rose-500">*</span></label>
                                <input v-model="addForm.tenant_phone" readonly class="w-full bg-slate-50 px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"/>
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
                                Tạo & Xuất PDF
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

            <!-- Upload Signed Image Modal -->
            <div v-if="showUploadModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">Upload Hợp Đồng Đã Ký</h3>
                        <button @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    
                    <form @submit.prevent="submitUpload">
                        <div class="p-6 space-y-4">
                            <p class="text-xs text-slate-500">Vui lòng tải lên ảnh chụp bản hợp đồng gốc đã có đầy đủ chữ ký của cả hai bên để kích hoạt hợp đồng.</p>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Ảnh hợp đồng (Định dạng JPEG, PNG) <span class="text-rose-500">*</span></label>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    capture="environment"
                                    @input="uploadForm.signed_image = $event.target.files[0]"
                                    class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                                    required
                                />
                                <div v-if="uploadForm.errors.signed_image" class="text-[10px] text-rose-500 font-bold mt-1">{{ uploadForm.errors.signed_image }}</div>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
                            <button type="button" @click="showUploadModal = false" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors">Đóng</button>
                            <button 
                                type="submit" 
                                :disabled="uploadForm.processing"
                                class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-md transition-colors disabled:opacity-50"
                            >
                                {{ uploadForm.processing ? 'Đang tải lên...' : 'Xác nhận & Kích hoạt' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>
