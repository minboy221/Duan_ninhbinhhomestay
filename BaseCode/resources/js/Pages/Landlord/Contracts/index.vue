<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed, watch, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { showSuccess, showWarning, showConfirm } from '@/Utils/swal'
import axios from 'axios'

const props = defineProps({
    dbContracts: Array,
    appointments: Array,
    boardingHouses: Array
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

const statusMap = {
    pending:         { label: 'Chờ ký/duyệt', cls: 'bg-amber-50 text-amber-600 border-amber-150', dot: 'bg-amber-500' },
    signed:          { label: 'Đã ký kết',   cls: 'bg-blue-50 text-blue-600 border-blue-150', dot: 'bg-blue-500' },
    awaiting_upload: { label: '1. Chờ Upload', cls: 'bg-amber-50 text-amber-600 border-amber-150', dot: 'bg-amber-500' },
    active:          { label: '2. Đang Hiệu Lực', cls: 'bg-emerald-50 text-emerald-600 border-emerald-150', dot: 'bg-emerald-500' },
    expiring:        { label: '3. Sắp Hết Hạn',   cls: 'bg-orange-50 text-orange-600 border-orange-150', dot: 'bg-orange-500' },
    expired:         { label: '4. Đã Hết Hạn',    cls: 'bg-rose-50 text-rose-600 border-rose-150', dot: 'bg-rose-500' },
    terminated:      { label: '5. Đã Thanh Lý',  cls: 'bg-slate-50 text-slate-500 border-slate-150', dot: 'bg-slate-500' },
    cancelled:       { label: '5. Đã Hủy',        cls: 'bg-slate-50 text-slate-500 border-slate-150', dot: 'bg-slate-500' },
    draft:           { label: 'Bản Nháp',        cls: 'bg-slate-50 text-slate-600 border-slate-150', dot: 'bg-slate-500' },
}

const defaultStatus = { label: 'Khác', cls: 'bg-slate-50 text-slate-500 border-slate-150', dot: 'bg-slate-400' };
const getStatusConfig = (status) => statusMap[status] || defaultStatus;

const expiringCount = computed(() => contracts.value.filter(c => c.status === 'expiring').length)
const openContract  = (c) => { selectedContract.value = c; showModal.value = true }
const closeModal    = () => { showModal.value = false; selectedContract.value = null }
const askDelete     = (c) => { deleteTarget.value = c; showDeleteConfirm.value = true }

const confirmDelete = () => { contracts.value = contracts.value.filter(c => c.id !== deleteTarget.value.id); showDeleteConfirm.value = false }
const formatMoney   = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'
const formatDate    = (d) => new Date(d).toLocaleDateString('vi-VN')

const uploadForm = useForm({
    signed_image: [],
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
            showSuccess('Thành công', 'Đã tải lên và kích hoạt hợp đồng!');
        }
    })
}

// Multi-step create contract state
const activeStep = ref(1) // 1: Room & Price, 2: Tenant Info, 3: Terms, 4: Upload Photos

// Tra cứu người thuê
const tenantSearchQuery = ref('')
const tenantSearchResults = ref([])
const isSearchingTenant = ref(false)
const selectedTenantUser = ref(null)

const searchTenants = async () => {
    if (!tenantSearchQuery.value.trim()) {
        tenantSearchResults.value = []
        return
    }
    isSearchingTenant.value = true
    try {
        const response = await axios.get('/landlord/search-tenant', {
            params: { q: tenantSearchQuery.value }
        })
        tenantSearchResults.value = response.data || []
    } catch (e) {
        console.error(e)
    } finally {
        isSearchingTenant.value = false
    }
}

const selectTenantUser = (user) => {
    selectedTenantUser.value = user
    addForm.value.tenant_id = user.id
    addForm.value.tenant_name = user.name
    addForm.value.tenant_phone = user.phone
    addForm.value.tenant_email = user.email || ''
    addForm.value.tenant_cccd = user.cccd_number || ''
    tenantSearchQuery.value = `${user.name} (${user.phone})`
    tenantSearchResults.value = []
}

const clearTenantUser = () => {
    selectedTenantUser.value = null
    addForm.value.tenant_id = ''
    tenantSearchQuery.value = ''
}

const imagePreviews = ref([])
const handleImageSelect = (e) => {
    const files = Array.from(e.target.files)
    addForm.value.signed_image = files
    imagePreviews.value = files.map(file => URL.createObjectURL(file))
}

const addForm = ref({
    appointment_id: '',
    room_id: '',
    room: '',
    rent: 3000000,
    deposit: 3000000,
    tenant_id: '',
    tenant_name: '',
    tenant_phone: '',
    tenant_email: '',
    tenant_cccd: '',
    start_date: '',
    end_date: '',
    billing_cycle: 1,
    depositPaid: true,
    signed_image: []
})

watch(() => addForm.value.appointment_id, (newVal) => {
    if (newVal) {
        const apt = props.appointments.find(a => a.id === newVal)
        if (apt) {
            addForm.value.room_id = apt.room_id || ''
            addForm.value.room = apt.room ? apt.room.room_number : ''
            addForm.value.tenant_id = apt.user_id || ''
            addForm.value.tenant_name = apt.user ? apt.user.name : ''
            addForm.value.tenant_phone = apt.user ? apt.user.phone : ''
            addForm.value.tenant_email = apt.user ? apt.user.email || '' : ''
            addForm.value.tenant_cccd = apt.user ? apt.user.cccd_number || '' : ''
            addForm.value.rent = apt.room ? Math.round(Number(apt.room.price)) : 3000000
            addForm.value.deposit = apt.room ? Math.round(Number(apt.room.price)) : 3000000
        }
    }
})

watch(() => addForm.value.start_date, (newVal) => {
    if (newVal) {
        const startDate = new Date(newVal);
        startDate.setDate(startDate.getDate() + 30);
        const newMinEndDate = startDate.toISOString().split('T')[0];
        if (!addForm.value.end_date || addForm.value.end_date < newMinEndDate) {
            addForm.value.end_date = newMinEndDate;
        }
    }
})

const displayRent = computed({
    get() {
        if (addForm.value.rent === null || addForm.value.rent === undefined || addForm.value.rent === '') return ''
        return new Intl.NumberFormat('en-US').format(addForm.value.rent)
    },
    set(val) {
        const raw = String(val).replace(/\D/g, '')
        addForm.value.rent = raw ? parseInt(raw, 10) : 0
    }
})

const displayDeposit = computed({
    get() {
        if (addForm.value.deposit === null || addForm.value.deposit === undefined || addForm.value.deposit === '') return ''
        return new Intl.NumberFormat('en-US').format(addForm.value.deposit)
    },
    set(val) {
        const raw = String(val).replace(/\D/g, '')
        addForm.value.deposit = raw ? parseInt(raw, 10) : 0
    }
})

const minEndDate = computed(() => {
    if (!addForm.value.start_date) return '';
    const startDate = new Date(addForm.value.start_date);
    startDate.setDate(startDate.getDate() + 30);
    return startDate.toISOString().split('T')[0];
});

const minStartDate = computed(() => {
    if (addForm.value.appointment_id) {
        const apt = props.appointments.find(a => a.id === addForm.value.appointment_id)
        if (apt && apt.date) {
            return apt.date;
        }
    }
    const today = new Date();
    return today.toISOString().split('T')[0];
});

const openAddContract = (appointmentId = '') => {
    activeStep.value = 1
    tenantSearchQuery.value = ''
    tenantSearchResults.value = []
    selectedTenantUser.value = null
    imagePreviews.value = []

    addForm.value = {
        appointment_id: appointmentId,
        room_id: '',
        room: '',
        rent: 3000000,
        deposit: 3000000,
        tenant_id: '',
        tenant_name: '',
        tenant_phone: '',
        tenant_email: '',
        tenant_cccd: '',
        start_date: '',
        end_date: '',
        billing_cycle: 1,
        depositPaid: true,
        signed_image: []
    }

    if (appointmentId) {
        const apt = props.appointments.find(a => a.id === appointmentId)
        if (apt) {
            addForm.value.appointment_id = appointmentId
            addForm.value.room_id = apt.room_id || ''
            addForm.value.room = apt.room ? apt.room.room_number : ''
            addForm.value.tenant_id = apt.user_id || ''
            addForm.value.tenant_name = apt.user ? apt.user.name : ''
            addForm.value.tenant_phone = apt.user ? apt.user.phone : ''
            addForm.value.tenant_email = apt.user ? apt.user.email || '' : ''
            addForm.value.tenant_cccd = apt.user ? apt.user.cccd_number || '' : ''
            addForm.value.rent = apt.room ? Math.round(Number(apt.room.price)) : 3000000
            addForm.value.deposit = apt.room ? Math.round(Number(apt.room.price)) : 3000000
        }
    }
    showAddModal.value = true
}

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'create_contract') {
        const appointmentId = urlParams.get('appointment_id');
        if (appointmentId) {
            openAddContract(parseInt(appointmentId));
            setTimeout(() => {
                router.visit(window.location.pathname, {
                    replace: true,
                    preserveState: true,
                    preserveScroll: true,
                });
            }, 100);
        }
    }
})

const submitAddContract = () => {
    if (!addForm.value.appointment_id) {
        showWarning('Thiếu thông tin', 'Vui lòng chọn Lịch hẹn khách xem phòng!')
        return
    }

    const payload = new FormData()
    payload.append('appointment_id', addForm.value.appointment_id)
    if (addForm.value.tenant_cccd) payload.append('tenant_cccd', addForm.value.tenant_cccd)
    if (addForm.value.start_date) payload.append('start_date', addForm.value.start_date)
    if (addForm.value.end_date) payload.append('end_date', addForm.value.end_date)
    payload.append('monthly_rent', addForm.value.rent)
    payload.append('deposit', addForm.value.deposit)
    if (addForm.value.billing_cycle) payload.append('billing_cycle', addForm.value.billing_cycle)

    if (addForm.value.signed_image && addForm.value.signed_image.length > 0) {
        addForm.value.signed_image.forEach(file => {
            payload.append('signed_image[]', file)
        })
    }

    router.post('/landlord/contracts/store-draft', payload, {
        forceFormData: true,
        onSuccess: () => {
            showAddModal.value = false;
            showSuccess('Thành công', 'Tạo hợp đồng thuê trọ thành công!');
        }
    });
}

const showExtendModal = ref(false);
const extendForm = ref({ new_end_date: '' });
const openExtendModal = () => {
    if (!selectedContract.value) return;
    extendForm.value.new_end_date = selectedContract.value.original_contract?.end_date?.split('T')[0] || '';
    showExtendModal.value = true;
};
const submitExtendContract = () => {
    if (!extendForm.value.new_end_date) {
        showWarning('Thiếu thông tin', 'Vui lòng chọn ngày hết hạn mới.');
        return;
    }
    router.post(`/landlord/contracts/${selectedContract.value.id}/extend`, extendForm.value, {
        onSuccess: () => {
            showExtendModal.value = false;
            showModal.value = false;
        }
    });
};

const submitTerminateContract = async () => {
    if (!selectedContract.value) return;
    const confirmed = await showConfirm(
        'Thanh lý hợp đồng',
        'Bạn có chắc chắn muốn hủy/thanh lý hợp đồng này? Hành động này sẽ chuyển trạng thái phòng về Còn trống và khôi phục lại tin đăng phòng.',
        'Thanh lý',
        'Hủy'
    );
    if (confirmed) {
        router.post(`/landlord/contracts/${selectedContract.value.id}/terminate`, {}, {
            onSuccess: () => {
                showModal.value = false;
            }
        });
    }
};
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
            <!-- Stats Deck -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
                <!-- 1 -->
                <div class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">1. Chờ hoàn tất</p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">{{ contracts.filter(c=>c.status==='awaiting_upload').length }}</h3>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                    </div>
                </div>

                <!-- 2 -->
                <div class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">2. Đang hiệu lực</p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">{{ contracts.filter(c=>c.status==='active').length }}</h3>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-file-check-fill"></i>
                    </div>
                </div>

                <!-- 3 -->
                <div class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">3. Sắp hết hạn</p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">{{ expiringCount }}</h3>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>

                <!-- 4 -->
                <div class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">4. Đã hết hạn</p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">{{ contracts.filter(c=>c.status==='expired').length }}</h3>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-file-x-fill"></i>
                    </div>
                </div>
                
                <!-- 5 -->
                <div class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">5. Đã thanh lý/hủy</p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">{{ contracts.filter(c=>c.status==='terminated' || c.status==='cancelled').length }}</h3>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-slate-50 text-slate-500 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-slash-circle-fill"></i>
                    </div>
                </div>
            </div>

            <!-- Contracts Table (Desktop) -->
            <div class="hidden lg:block bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
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
                            <tr v-for="(c, index) in contracts" :key="c.id" :class="[
                                'hover:bg-slate-50/40 cursor-pointer',
                                c.status === 'expiring' ? 'bg-amber-50/10' : '',
                                c.status === 'expired' ? 'bg-slate-50/30 opacity-75' : ''
                            ]" @click="openContract(c)">
                                <td class="py-4 px-6 font-bold text-slate-800">{{ index + 1 }}</td>
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
                                    <span :class="['px-2.5 py-1 rounded-md text-[10px] font-bold border flex items-center gap-1.5 w-fit', getStatusConfig(c.status).cls]">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusConfig(c.status).dot"></span>
                                        {{ getStatusConfig(c.status).label }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right" @click.stop>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openContract(c)" class="w-7 h-7 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center transition-colors"><i class="bi bi-eye"></i></button>
                                        <a v-if="c.original_contract?.contract_file_path" :href="`/storage/${c.original_contract.contract_file_path}`" target="_blank" class="w-7 h-7 bg-slate-50 hover:bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center transition-colors"><i class="bi bi-file-earmark-pdf"></i></a>
                                        <button v-if="c.status === 'awaiting_upload'" @click="openUploadModal(c)" class="w-7 h-7 bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center transition-colors"><i class="bi bi-upload"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Contracts Mobile Card List -->
            <div class="block lg:hidden space-y-4">
                <div v-for="c in contracts" :key="c.id" :class="[
                    'bg-white border border-slate-150 rounded-3xl p-5 shadow-sm space-y-3',
                    c.status === 'expired' ? 'opacity-75' : ''
                ]" @click="openContract(c)">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Hợp đồng: {{ c.id }}</span>
                            <div class="text-sm font-black text-slate-800 mt-0.5">{{ c.room }}</div>
                        </div>
                        <span :class="['px-2 py-0.5 rounded-md text-[9px] font-bold border flex items-center gap-1 w-fit', getStatusConfig(c.status).cls]">
                            <span class="w-1.5 h-1.5 rounded-full" :class="getStatusConfig(c.status).dot"></span>
                            {{ getStatusConfig(c.status).label }}
                        </span>
                    </div>

                    <!-- Compact Key-Value Details -->
                    <div class="space-y-1.5 text-xs pt-2 border-t border-slate-50 font-semibold text-slate-600">
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">Người thuê:</span>
                            <span class="text-slate-700 font-bold">{{ c.tenant }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">Điện thoại:</span>
                            <span class="text-slate-500 font-mono">{{ c.phone }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">Giá thuê:</span>
                            <span class="text-slate-700 font-bold">{{ formatMoney(c.rent) }}/tháng</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">Thời hạn:</span>
                            <span class="text-slate-500">{{ formatDate(c.start) }} - {{ formatDate(c.end) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">Đặt cọc:</span>
                            <span :class="c.depositPaid ? 'text-emerald-600 font-bold' : 'text-rose-500 font-bold'">
                                {{ c.depositPaid ? 'Đã cọc' : 'Chưa cọc' }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-50" @click.stop>
                        <button @click="openContract(c)" class="w-8 h-8 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-xl flex items-center justify-center transition-colors">
                            <i class="bi bi-eye"></i>
                        </button>
                        <a v-if="c.original_contract?.contract_file_path" :href="`/storage/${c.original_contract.contract_file_path}`" target="_blank" class="w-8 h-8 bg-slate-50 hover:bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center transition-colors">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                        <button @click="askDelete(c)" class="w-8 h-8 bg-slate-50 hover:bg-rose-100 text-rose-500 rounded-xl flex items-center justify-center transition-colors">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <Teleport to="body">
            <!-- Details Modal -->
            <div v-if="showModal && selectedContract" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-0 sm:p-4" @click.self="closeModal">
                <div class="bg-white rounded-t-[32px] sm:rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[85vh] sm:max-h-[90vh]">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">Chi tiết hợp đồng {{ selectedContract.id }}</h3>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
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

                    <div class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-2 bg-slate-50/50">
                        <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors text-center" @click="closeModal">Đóng</button>
                        <button v-if="selectedContract?.status === 'active' || selectedContract?.status === 'awaiting_upload'" @click="submitTerminateContract" class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow-md shadow-rose-500/10 transition-colors text-center">Thanh lý hợp đồng</button>
                        <button v-if="selectedContract?.status === 'active'" @click="openExtendModal" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors text-center">Gia hạn hợp đồng</button>
                    </div>
                </div>
            </div>

            <!-- Multi-step Add Contract Modal -->
            <div v-if="showAddModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-0 sm:p-4" @click.self="showAddModal = false">
                <div class="bg-white rounded-t-[32px] sm:rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[85vh] sm:max-h-[90vh]">
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
                        <span :class="activeStep >= 1 ? 'text-emerald-500 font-bold' : ''">1. Thông tin phòng</span>
                        <i class="bi bi-chevron-right"></i>
                        <span :class="activeStep >= 2 ? 'text-emerald-500 font-bold' : ''">2. Thông tin khách</span>
                        <i class="bi bi-chevron-right"></i>
                        <span :class="activeStep >= 3 ? 'text-emerald-500 font-bold' : ''">3. Ảnh hợp đồng</span>
                    </div>

                    <!-- Form Body -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- Step 1: Room Selection & Dates -->
                        <div v-if="activeStep === 1" class="space-y-4">
                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Chọn lịch hẹn khách ký HĐ <span class="text-rose-500">*</span></label>
                                    <select v-model="addForm.appointment_id" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all">
                                        <option value="" disabled>-- Chọn khách đã hẹn --</option>
                                        <option v-for="apt in props.appointments" :key="apt.id" :value="apt.id">
                                            Phòng {{ apt.room?.room_number }} - {{ apt.user?.name }} ({{ apt.user?.phone }})
                                        </option>
                                    </select>
                                </div>
                                <div class="space-y-1" v-if="addForm.room">
                                    <label class="text-xs font-bold text-slate-500">Phòng đã chọn</label>
                                    <input v-model="addForm.room" readonly class="w-full bg-slate-50 px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-emerald-600 outline-none"/>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 pt-1">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Giá thuê (đ/tháng)</label>
                                    <input v-model="displayRent" type="text" placeholder="0" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-bold text-slate-700 outline-none transition-all"/>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Tiền đã đặt cọc (đ)</label>
                                    <input v-model="displayDeposit" type="text" placeholder="0" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-bold text-slate-700 outline-none transition-all"/>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Tenant Info -->
                        <div v-if="activeStep === 2" class="space-y-4">
                            <div class="space-y-3">
                                <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-xs text-emerald-700 font-semibold flex items-center gap-2">
                                    <i class="bi bi-person-check-fill text-lg"></i>
                                    <span>Thông tin khách thuê từ Lịch hẹn đã chọn</span>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Họ và tên khách thuê</label>
                                    <input v-model="addForm.tenant_name" readonly class="w-full bg-slate-50 px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-medium outline-none"/>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Số điện thoại</label>
                                    <input v-model="addForm.tenant_phone" readonly class="w-full bg-slate-50 px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-medium outline-none"/>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Số CCCD (Nói người thuê bổ sung nếu thiếu)</label>
                                    <input v-model="addForm.tenant_cccd" maxlength="12" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none" placeholder="VD: 036091234567"/>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Upload Image -->
                        <div v-if="activeStep === 3" class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500">Upload Ảnh Hợp Đồng Giấy Ký Tay</label>
                                <p class="text-[11px] text-slate-500 leading-relaxed">
                                    Chụp ảnh các trang hợp đồng giấy đã ký ngoài đời. Hệ thống sẽ tự động ghép các hình ảnh này thành <strong>1 file PDF chính thức</strong> để cả bạn và người thuê xem/tải về.
                                </p>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    multiple
                                    @change="handleImageSelect"
                                    class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all cursor-pointer bg-slate-50"
                                />

                                <!-- Image Previews Grid -->
                                <div v-if="imagePreviews.length > 0" class="grid grid-cols-3 gap-2 pt-2">
                                    <div v-for="(src, i) in imagePreviews" :key="i" class="relative group rounded-xl overflow-hidden border border-slate-200 aspect-[3/4]">
                                        <img :src="src" class="w-full h-full object-cover"/>
                                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">
                                            Trang {{ i + 1 }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Foot -->
                    <div class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-between gap-2.5 bg-slate-50/50">
                        <button 
                            v-if="activeStep > 1"
                            class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors text-center" 
                            @click="activeStep--"
                        >
                            Quay lại
                        </button>
                        <div v-else></div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors text-center" @click="showAddModal = false">Hủy</button>
                            <button 
                                v-if="activeStep < 3"
                                class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors text-center"
                                @click="activeStep++"
                            >
                                Tiếp tục
                            </button>
                            <button 
                                v-else
                                class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors text-center"
                                @click="submitAddContract"
                            >
                                {{ addForm.signed_image.length > 0 ? 'Tạo & Kích Hoạt' : 'Tạo Hợp Đồng' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirm delete modal -->
            <div v-if="showDeleteConfirm" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-0 sm:p-4">
                <div class="bg-white rounded-t-[32px] sm:rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[85vh] sm:max-h-[90vh]">
                    <div class="p-6 text-center space-y-4 flex-1 overflow-y-auto">
                        <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center text-xl mx-auto">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-slate-800">Xác nhận xóa hợp đồng</h4>
                            <p class="text-xs text-slate-400">Bạn có chắc chắn muốn xóa hợp đồng phòng {{ deleteTarget?.room }}? Hành động này không thể hoàn tác.</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center sm:justify-end gap-2 bg-slate-50/50">
                        <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors text-center" @click="showDeleteConfirm = false">Hủy</button>
                        <button class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow-md shadow-rose-500/10 transition-colors text-center" @click="confirmDelete">Xóa</button>
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
                                    multiple
                                    capture="environment"
                                    @input="uploadForm.signed_image = Array.from($event.target.files)"
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

            <!-- Extend Contract Modal -->
            <div v-if="showExtendModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
                <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl p-6 text-center space-y-6">
                    <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mx-auto text-emerald-500 text-3xl">
                        <i class="bi bi-calendar-plus"></i>
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-lg font-bold text-slate-800">Gia hạn hợp đồng</h3>
                        <p class="text-sm text-slate-500 font-medium">Vui lòng chọn ngày hết hạn mới cho hợp đồng này.</p>
                        <input type="date" v-model="extendForm.new_end_date" class="w-full mt-4 px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm font-medium outline-none transition-all" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="showExtendModal = false" class="py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-sm rounded-xl transition-colors">Hủy</button>
                        <button @click="submitExtendContract" class="py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm rounded-xl shadow-md shadow-emerald-500/20 transition-colors text-center">
                            Xác nhận
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>
    </LandlordLayout>
</template>
