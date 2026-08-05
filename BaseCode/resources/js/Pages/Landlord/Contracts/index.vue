<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed, watch, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import CustomSwal, { showSuccess, showWarning, showConfirm, showError } from '@/Utils/swal'
import axios from 'axios'
import { performClientOcr } from '@/Utils/contractOcr.js'

const props = defineProps({
    dbContracts: Array,
    appointments: Array,
    boardingHouses: Array,
    authLandlord: Object
})

const contracts = computed(() => {
    return (props.dbContracts || []).map(c => ({
        id: c.id,
        room: c.room ? c.room.room_number : '',
        tenant: c.tenant ? c.tenant.name : '',
        phone: c.tenant ? c.tenant.phone : '',
        tenant_cccd: c.tenant ? c.tenant.cccd_number : '',
        start: c.start_date,
        end: c.end_date,
        rent: c.monthly_rent,
        deposit: c.deposit_amount,
        depositPaid: true,
        status: c.status,
        ocr_status: c.ocr_status,
        ocr_rejection_reason: c.ocr_rejection_reason,
        terms_accepted: c.terms_accepted,
        original_contract: c,
    }))
})

const showModal          = ref(false)
const showAddModal       = ref(false)
const showUploadModal    = ref(false)
const showExtendModal    = ref(false)
const showLiquidationModal = ref(false)
const showPendingRequestsModal = ref(false)
const selectedContract   = ref(null)

const openContractForAppointment = (apt) => {
    showPendingRequestsModal.value = false;
    openAddContract();
    addForm.value.appointment_id = apt.id;
}

const statusMap = {
    pending:         { label: 'Chờ ký/duyệt', code: 'Chờ duyệt', cls: 'bg-amber-50 text-amber-600 border-amber-150', dot: 'bg-amber-500' },
    signed:          { label: 'Đã ký kết',   code: 'Đã ký', cls: 'bg-blue-50 text-blue-600 border-blue-150', dot: 'bg-blue-500' },
    awaiting_upload: { label: 'Chờ Upload', code: 'Chờ Upload', cls: 'bg-amber-50 text-amber-600 border-amber-150', dot: 'bg-amber-500' },
    active:          { label: 'Đang Hiệu Lực', code: 'Hiệu lực', cls: 'bg-emerald-50 text-emerald-600 border-emerald-150', dot: 'bg-emerald-500' },
    expiring:        { label: 'Sắp Hết Hạn',   code: 'Sắp hết hạn', cls: 'bg-orange-50 text-orange-600 border-orange-150', dot: 'bg-orange-500' },
    expired:         { label: 'Đã Hết Hạn',    code: 'Đã Hết Hạn', cls: 'bg-rose-50 text-rose-600 border-rose-150', dot: 'bg-rose-500' },
    terminated:      { label: 'Đã Thanh Lý',  code: 'Đã Thanh Lý', cls: 'bg-slate-50 text-slate-500 border-slate-150', dot: 'bg-slate-500' },
    termination_requested: { label: 'Yêu Cầu Chấm Dứt', code: 'Yêu cầu chấm dứt', cls: 'bg-orange-50 text-orange-600 border-orange-200', dot: 'bg-orange-500' },
    cancelled:       { label: 'Đã Hủy',        code: 'Đã Hủy', cls: 'bg-slate-50 text-slate-500 border-slate-150', dot: 'bg-slate-500' },
    draft:           { label: 'Bản Nháp',        code: 'Bản Nháp', cls: 'bg-slate-50 text-slate-600 border-slate-150', dot: 'bg-slate-500' },
}

const defaultStatus = { label: 'Khác', cls: 'bg-slate-50 text-slate-500 border-slate-150', dot: 'bg-slate-400' };
const getStatusConfig = (status) => statusMap[status] || defaultStatus;

const expiringCount = computed(() => contracts.value.filter(c => c.status === 'expiring').length)
const openContract  = (c) => { selectedContract.value = c; showModal.value = true }
const closeModal    = () => { showModal.value = false; selectedContract.value = null }

const formatMoney   = (n) => new Intl.NumberFormat('vi-VN').format(n || 0) + 'đ'
const formatDate    = (d) => d ? new Date(d).toLocaleDateString('vi-VN') : '---'

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
            showSuccess('Thành công', 'Ảnh hợp đồng đã vượt qua kiểm duyệt OCR và kích hoạt thành công!');
        },
        onError: (errors) => {
            showError('Lỗi kiểm duyệt', errors.signed_image || 'Ảnh hợp đồng không hợp lệ hoặc chưa điền đầy đủ các thông tin viết tay.');
        }
    })
}

// Multi-step create contract state
const activeStep = ref(1) // 1: Room & Price, 2: Tenant Info, 3: Terms & Upload

const imagePreviews = ref([])
const isScanningOcr = ref(false)
const ocrProgressText = ref('')

const handleImageSelect = (e) => {
    const files = Array.from(e.target.files)
    addForm.value.signed_image = files
    imagePreviews.value = files.map(file => URL.createObjectURL(file))
    if (files.length > 0) {
        scanOcrFile(files[0])
    }
}

const scanOcrFile = async (file) => {
    if (!file) {
        showWarning('Thiếu tệp ảnh', 'Vui lòng chọn ảnh hợp đồng trước khi quét OCR!');
        return;
    }
    isScanningOcr.value = true;
    ocrProgressText.value = 'Đang phân tích hình ảnh hợp đồng (chữ in & chữ viết tay)...';

    // Reset tất cả các trường dữ liệu về rỗng
    addForm.value.landlord_name = '';
    addForm.value.landlord_cccd = '';
    addForm.value.landlord_phone = '';
    addForm.value.landlord_address = '';

    addForm.value.tenant_name = '';
    addForm.value.tenant_cccd = '';
    addForm.value.tenant_phone = '';
    addForm.value.tenant_dob = '';
    addForm.value.tenant_address = '';

    try {
        let res = null;

        // 1. Thử gọi Backend API Server-side (Tự động đọc chữ viết tay & chữ in qua Gemini Vision AI nếu có GEMINI_API_KEY)
        try {
            const formData = new FormData();
            formData.append('ocr_file', file);
            const apiRes = await axios.post(route('landlord.contracts.extract_ocr'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            if (apiRes.data && apiRes.data.success && apiRes.data.has_data) {
                res = apiRes.data;
            }
        } catch (serverErr) {
            console.log('Backend OCR bypass/fallback to Client Tesseract:', serverErr);
        }

        // 2. Nếu Backend Server API chưa trả về dữ liệu -> Chạy Client-side Tesseract.js trên trình duyệt
        if (!res || !res.has_data) {
            ocrProgressText.value = 'Đang khởi động Tesseract.js trên trình duyệt...';
            res = await performClientOcr(file, (percent, msg) => {
                ocrProgressText.value = `${msg} (${percent}%)`;
            });
        }

        if (res) {
            if (res.is_blank) {
                addForm.value.landlord_name = '';
                addForm.value.landlord_cccd = '';
                addForm.value.landlord_phone = '';
                addForm.value.landlord_address = '';

                addForm.value.tenant_name = '';
                addForm.value.tenant_cccd = '';
                addForm.value.tenant_phone = '';
                addForm.value.tenant_dob = '';
                addForm.value.tenant_address = '';

                showWarning('Mẫu hợp đồng chưa điền', res.message || 'Phát hiện ảnh hợp đồng là bản mẫu in chưa điền thông tin/chữ ký. Tất cả các trường thông tin đã được giữ trống để bạn tự điền ở Bước 3.');
            } else {
                addForm.value.landlord_name = res.landlord_name || '';
                addForm.value.landlord_cccd = res.landlord_cccd || '';
                addForm.value.landlord_phone = res.landlord_phone || '';
                addForm.value.landlord_address = res.landlord_address || '';

                addForm.value.tenant_name = res.tenant_name || '';
                addForm.value.tenant_cccd = res.tenant_cccd || '';
                addForm.value.tenant_phone = res.tenant_phone || '';
                addForm.value.tenant_dob = res.tenant_dob || '';
                addForm.value.tenant_address = res.tenant_address || '';

                if (res.start_date) addForm.value.start_date = res.start_date;
                if (res.end_date) addForm.value.end_date = res.end_date;
                if (res.monthly_rent) addForm.value.rent = res.monthly_rent;
                if (res.deposit_amount) addForm.value.deposit = res.deposit_amount;

                if (res.has_data) {
                    showSuccess('Quét OCR hoàn tất', res.message || 'Hệ thống đã bóc tách thành công dữ liệu từ ảnh hợp đồng!');
                } else {
                    showSuccess('Nhận diện hợp đồng', 'Đã chuyển sang Bước 3 để bạn kiểm tra và điền thông tin hợp đồng.');
                }
            }
            activeStep.value = 3;
        }
    } catch (err) {
        showError('Lưu ý OCR', 'Không thể tự động bóc tách dữ liệu từ tệp này. Các trường đã được để trống để bạn tự điền ở Bước 3.');
        activeStep.value = 3;
    } finally {
        isScanningOcr.value = false;
        ocrProgressText.value = '';
    }
}

const getInitialAddForm = (appointmentId = '') => {
    return {
        appointment_id: appointmentId,
        room_id: '',
        room: '',
        rent: 3000000,
        deposit: 3000000,
        // BÊN A (Chủ trọ) - ĐỂ TRỐNG TOÀN BỘ, CHỈ LẤY TỪ OCR HỢP ĐỒNG HOẶC NHẬP THỦ CÔNG
        landlord_name: '',
        landlord_cccd: '',
        landlord_phone: '',
        landlord_address: '',
        // BÊN B (Khách thuê) - ĐỂ TRỐNG TOÀN BỘ, CHỈ LẤY TỪ OCR HỢP ĐỒNG HOẶC NHẬP THỦ CÔNG
        tenant_id: '',
        tenant_name: '',
        tenant_phone: '',
        tenant_email: '',
        tenant_cccd: '',
        tenant_dob: '',
        tenant_address: '',
        start_date: new Date().toISOString().split('T')[0],
        end_date: '',
        number_of_tenants: 1,
        billing_cycle: 1,
        depositPaid: true,
        terms_accepted: true,
        signed_image: []
    }
}

const addForm = ref(getInitialAddForm())

watch(() => addForm.value.appointment_id, (newVal) => {
    if (newVal) {
        const apt = props.appointments.find(a => String(a.id) === String(newVal))
        if (apt) {
            addForm.value.room_id = apt.room_id || ''
            addForm.value.room = apt.room ? apt.room.room_number : ''
            addForm.value.tenant_id = apt.user_id || ''
            addForm.value.rent = apt.room ? Math.round(Number(apt.room.price)) : 3000000
            addForm.value.deposit = apt.room ? Math.round(Number(apt.room.price)) : 3000000
            // KHÔNG tự động gán tenant_name, tenant_phone, tenant_cccd từ tài khoản người dùng
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

const openAddContract = (appointmentId = '') => {
    activeStep.value = 1
    imagePreviews.value = []

    addForm.value = getInitialAddForm(appointmentId)

    if (appointmentId) {
        const apt = props.appointments.find(a => String(a.id) === String(appointmentId))
        if (apt) {
            addForm.value.room_id = apt.room_id || ''
            addForm.value.room = apt.room ? apt.room.room_number : ''
            addForm.value.tenant_id = apt.user_id || ''
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

const selectedRoomData = computed(() => {
    if (!addForm.value.appointment_id) return null;
    const apt = props.appointments?.find(a => String(a.id) === String(addForm.value.appointment_id));
    return apt?.room || null;
})

const selectedRoomCapacity = computed(() => {
    return selectedRoomData.value?.capacity ?? 2;
})

const selectedRoomCurrentPeople = computed(() => {
    return selectedRoomData.value?.current_people ?? 0;
})

const maxAvailableTenants = computed(() => {
    const cap = selectedRoomCapacity.value;
    const curr = selectedRoomCurrentPeople.value;
    return Math.max(1, cap - curr);
})

const tenantCountErrorMsg = computed(() => {
    const num = Number(addForm.value.number_of_tenants || 0);
    if (!num || num < 1) {
        return 'Số lượng người ở phải lớn hơn 0.';
    }
    if (selectedRoomData.value) {
        const max = maxAvailableTenants.value;
        if (num > max) {
            return `⚠️ Số người ở (${num} người) vượt quá sức chứa còn lại (Phòng tối đa ${selectedRoomCapacity.value} người, hiện có ${selectedRoomCurrentPeople.value} người, chỉ thêm được tối đa ${max} người).`;
        }
    }
    return null;
})

const isStep1Valid = computed(() => {
    return !!addForm.value.appointment_id && String(addForm.value.appointment_id).trim() !== '' && !tenantCountErrorMsg.value;
});

const goToNextStep = () => {
    if (activeStep.value === 1) {
        if (!addForm.value.appointment_id) {
            showWarning('Bắt buộc chọn người đăng ký', 'Vui lòng chọn Người đăng ký lịch hẹn trước khi tiếp tục!');
            return;
        }
        if (tenantCountErrorMsg.value) {
            showError('Vượt quá sức chứa phòng', tenantCountErrorMsg.value);
            return;
        }
        activeStep.value = 2;
    } else if (activeStep.value === 2) {
        activeStep.value = 3;
    }
};

const goToStep = (step) => {
    if (step > 1) {
        if (!addForm.value.appointment_id) {
            showWarning('Bắt buộc chọn người đăng ký', 'Vui lòng chọn Lịch hẹn/Người đăng ký trước.');
            return;
        }
        if (tenantCountErrorMsg.value) {
            showError('Vượt quá sức chứa phòng', tenantCountErrorMsg.value);
            return;
        }
    }
    activeStep.value = step;
};

const checkTenantActiveContract = (tenantId, tenantPhone) => {
    if (!tenantId && !tenantPhone) return null;
    const activeStatuses = ['active', 'signed', 'pending', 'awaiting_upload', 'termination_requested', 'expiring'];
    return (props.dbContracts || []).find(c => {
        if (!activeStatuses.includes(c.status)) return false;
        if (tenantId && c.tenant_id === tenantId) return true;
        if (c.tenant && c.tenant.id === tenantId) return true;
        if (tenantPhone && c.tenant && c.tenant.phone === tenantPhone) return true;
        return false;
    });
};

const validateStep = (step) => {
    if (step === 1) {
        if (!addForm.value.appointment_id) {
            showWarning('Chưa chọn người đăng ký', 'Bắt buộc phải chọn Lịch hẹn khách xem phòng trước khi tiếp tục!');
            return false;
        }
        if (tenantCountErrorMsg.value) {
            showError('Vượt quá sức chứa phòng', tenantCountErrorMsg.value);
            return false;
        }
        if (!addForm.value.rent || Number(addForm.value.rent) <= 0) {
            showWarning('Giá thuê không hợp lệ', 'Vui lòng nhập giá thuê hàng tháng hợp lệ!');
            return false;
        }

        const activeContract = checkTenantActiveContract(addForm.value.tenant_id, addForm.value.tenant_phone);
        if (activeContract) {
            showError(
                'Không thể tạo hợp đồng!',
                `Khách thuê "${addForm.value.tenant_name || 'này'}" hiện đã có 1 hợp đồng thuê trọ đang có hiệu lực trong hệ thống. Quy định hệ thống: Mỗi người dùng chỉ được phép sở hữu tối đa 1 hợp đồng tại cùng một thời điểm.`
            );
            return false;
        }
        return true;
    }

    if (step === 2) {
        if (!addForm.value.appointment_id) {
            showWarning('Chưa chọn người đăng ký', 'Bạn bắt buộc phải chọn Lịch hẹn khách xem phòng ở Bước 1!');
            return false;
        }
        if (tenantCountErrorMsg.value) {
            showError('Vượt quá sức chứa phòng', tenantCountErrorMsg.value);
            return false;
        }
        return true;
    }

    if (step === 3) {
        if (!addForm.value.appointment_id) {
            showWarning('Chưa chọn người đăng ký', 'Bạn bắt buộc phải chọn Lịch hẹn khách xem phòng ở Bước 1!');
            return false;
        }
        if (tenantCountErrorMsg.value) {
            showError('Vượt quá sức chứa phòng', tenantCountErrorMsg.value);
            return false;
        }
        if (!addForm.value.tenant_name || !addForm.value.tenant_name.trim()) {
            showWarning('Thiếu tên khách thuê', 'Vui lòng nhập/chỉnh sửa Họ tên khách thuê!');
            return false;
        }
        if (!addForm.value.tenant_cccd || String(addForm.value.tenant_cccd).trim().length < 9) {
            showWarning('Thiếu số CCCD', 'Vui lòng nhập đúng số Căn cước công dân (CCCD) 12 số của khách thuê!');
            return false;
        }
        if (!addForm.value.terms_accepted) {
            showWarning('Chưa đồng ý điều khoản', 'Vui lòng tích chọn cam kết quy định pháp luật về hợp đồng!');
            return false;
        }
        return true;
    }

    return true;
};

const submitAddContract = () => {
    if (!validateStep(1) || !validateStep(3)) {
        return;
    }

    const payload = new FormData()
    payload.append('appointment_id', addForm.value.appointment_id)
    if (addForm.value.tenant_cccd) payload.append('tenant_cccd', addForm.value.tenant_cccd)
    if (addForm.value.start_date) payload.append('start_date', addForm.value.start_date)
    if (addForm.value.end_date) payload.append('end_date', addForm.value.end_date)
    payload.append('monthly_rent', addForm.value.rent)
    payload.append('deposit', addForm.value.deposit)
    if (addForm.value.billing_cycle) payload.append('billing_cycle', addForm.value.billing_cycle)
    payload.append('terms_accepted', '1')

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

// Extend Contract State
const extendForm = ref({ new_end_date: '', new_monthly_rent: '', tenant_cccd: '', notes: '' });
const openExtendModal = () => {
    if (!selectedContract.value) return;
    extendForm.value = {
        new_end_date: selectedContract.value.original_contract?.end_date?.split('T')[0] || '',
        new_monthly_rent: selectedContract.value.rent || '',
        tenant_cccd: selectedContract.value.tenant_cccd || '',
        notes: ''
    };
    showExtendModal.value = true;
};

const submitExtendContract = () => {
    if (!extendForm.value.new_end_date) {
        showWarning('Thiếu thông tin', 'Vui lòng chọn ngày hết hạn mới.');
        return;
    }
    if (!extendForm.value.tenant_cccd) {
        showWarning('Thiếu thông tin pháp lý', 'Bắt buộc nhập số CCCD/CMND của Khách thuê trước khi gia hạn hợp đồng.');
        return;
    }
    router.post(`/landlord/contracts/${selectedContract.value.id}/extend`, extendForm.value, {
        onSuccess: () => {
            showExtendModal.value = false;
            showModal.value = false;
            showSuccess('Gia hạn thành công', 'Đã lưu thời hạn hợp đồng mới và thông tin pháp lý!');
        }
    });
};

// Liquidation State (Requires 'expired' status strictly!)
const liquidationForm = ref({
    deposit_handling: 'refund_full',
    deposit_refund_amount: 0,
    notes: ''
});

const openLiquidationModal = (c) => {
    const target = c || selectedContract.value;
    if (!target) return;

    if (target.status !== 'expired') {
        showWarning('Chưa thể thanh lý!', 'Hợp đồng BẮT BUỘC phải chuyển sang trạng thái Hết Hạn (expired) mới được phép thực hiện Thanh lý. Vui lòng chuyển trạng thái Hợp đồng sang Hết Hạn trước.');
        return;
    }

    selectedContract.value = target;
    liquidationForm.value = {
        deposit_handling: 'refund_full',
        deposit_refund_amount: target.deposit || 0,
        notes: ''
    };
    showLiquidationModal.value = true;
};

const submitLiquidationContract = () => {
    router.post(`/landlord/contracts/${selectedContract.value.id}/liquidate`, liquidationForm.value, {
        onSuccess: () => {
            showLiquidationModal.value = false;
            showModal.value = false;
            showSuccess('Thành công', 'Đã thanh lý hợp đồng và giải phóng phòng trọ!');
        }
    });
};

// Manual Scan Action
const isScanning = ref(false);
const triggerScan = () => {
    isScanning.value = true;
    router.post('/landlord/contracts/scan', {}, {
        onFinish: () => { isScanning.value = false; },
        onSuccess: () => {
            showSuccess('Thành công', 'Đã quét và tự động cập nhật trạng thái hợp đồng mới nhất!');
        }
    });
};

// Mark as Expired Action (with Early Termination Check)
const submitMarkExpired = async () => {
    if (!selectedContract.value) return;

    const todayStr = new Date().toISOString().split('T')[0];
    const endDateStr = selectedContract.value.end ? selectedContract.value.end.split('T')[0] : '';
    const isEarly = endDateStr && endDateStr > todayStr;

    if (isEarly) {
        const result = await CustomSwal.fire({
            icon: 'warning',
            title: 'Chấm Dứt Hợp Đồng Trước Thời Hạn',
            text: 'Hợp đồng này chưa đến ngày hết hạn quy định (' + formatDate(selectedContract.value.end) + '). Vui lòng nhập lý do chấm dứt trước thời hạn:',
            input: 'textarea',
            inputPlaceholder: 'Nhập lý do chấm dứt sớm (ví dụ: Khách trả phòng sớm, vi phạm điều khoản...)...',
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'Vui lòng nhập lý do chấm dứt trước thời hạn!';
                }
            },
            showCancelButton: true,
            confirmButtonText: 'Xác nhận chấm dứt sớm',
            cancelButtonText: 'Hủy bỏ',
            reverseButtons: true
        });

        if (result.isConfirmed && result.value) {
            router.post(`/landlord/contracts/${selectedContract.value.id}/expire`, { reason: result.value }, {
                onSuccess: () => {
                    showModal.value = false;
                    showSuccess('Đã cập nhật', 'Đã chấm dứt hợp đồng sớm và chuyển sang trạng thái Hết hạn (Chờ thanh lý).');
                }
            });
        }
    } else {
        const confirmed = await showConfirm(
            'Chuyển sang Trạng Thái Hết Hạn',
            'Bạn có chắc muốn chuyển hợp đồng này sang trạng thái Hết Hạn (Expired) để tiến hành Thanh lý hoặc Gia hạn?',
            'Chuyển Hết Hạn',
            'Đóng'
        );
        if (confirmed) {
            router.post(`/landlord/contracts/${selectedContract.value.id}/expire`, {}, {
                onSuccess: () => {
                    showModal.value = false;
                    showSuccess('Đã cập nhật', 'Hợp đồng đã chuyển sang trạng thái Hết Hạn (expired).');
                }
            });
        }
    }
};

// Cancel Draft Action
const submitCancelDraft = async (c) => {
    const target = c || selectedContract.value;
    if (!target) return;
    const confirmed = await showConfirm(
        'Hủy Hợp Đồng Nháp',
        'Bạn có chắc chắn muốn hủy hợp đồng nháp này? Phòng trọ sẽ được giải phóng trở lại trạng thái Còn trống.',
        'Hủy Hợp Đồng',
        'Bỏ qua'
    );
    if (confirmed) {
        router.post(`/landlord/contracts/${target.id}/cancel-draft`, {}, {
            onSuccess: () => {
                showModal.value = false;
                showSuccess('Thành công', 'Đã hủy hợp đồng nháp và giải phóng phòng.');
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
                    <p class="text-xs text-slate-400">Danh sách hợp đồng thuê phòng, xác minh OCR và thanh lý hợp đồng khi hết hạn</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button @click="showPendingRequestsModal = true" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-amber-500/10 flex items-center gap-1.5 cursor-pointer">
                        <i class="bi bi-clock-history"></i>
                        <span>Hợp đồng chờ (User ấn ưng)</span>
                        <span v-if="props.appointments?.length > 0" class="ml-1 px-1.5 py-0.5 bg-white text-amber-600 rounded-full text-[10px] font-black shadow-xs">
                            {{ props.appointments.length }}
                        </span>
                    </button>
                    <button @click="triggerScan" :disabled="isScanning" class="px-4 py-2.5 bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-sky-500/10 flex items-center gap-1.5 disabled:opacity-50">
                        <i class="bi bi-arrow-repeat" :class="{ 'animate-spin': isScanning }"></i>
                        {{ isScanning ? 'Đang quét...' : 'Quét Trạng Thái Hợp Đồng' }}
                    </button>
                    <button @click="openAddContract" class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-emerald-500/10 flex items-center gap-1.5">
                        <i class="bi bi-file-earmark-plus"></i> Tạo hợp đồng mới
                    </button>
                </div>
            </div>

            <!-- Expiry Warning Alert -->
            <div v-if="expiringCount > 0" class="p-4 bg-amber-50/70 border border-amber-250 rounded-2xl flex items-center gap-3 text-xs text-amber-800 font-semibold shadow-sm">
                <i class="bi bi-clock-history text-lg text-amber-500"></i>
                <p>
                    Hiện đang có <strong class="text-amber-950">{{ expiringCount }}</strong> hợp đồng chuẩn bị hết hạn trong vòng 30 ngày tới. Vui lòng chuyển trạng thái Hết Hạn để Thanh lý hoặc thực hiện Gia hạn.
                </p>
            </div>

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
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">3. Đã hết hạn (Chờ thanh lý)</p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">{{ contracts.filter(c=>c.status==='expired').length }}</h3>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                </div>

                <!-- 4 -->
                <div class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">4. Đã thanh lý</p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">{{ contracts.filter(c=>c.status==='terminated').length }}</h3>
                    </div>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-base sm:text-lg">
                        <i class="bi bi-check2-all"></i>
                    </div>
                </div>

                <!-- 5 -->
                <div class="bg-white border border-slate-100 rounded-2xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400">5. Đã hủy</p>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-800">{{ contracts.filter(c=>c.status==='cancelled').length }}</h3>
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
                                c.status === 'expired' ? 'bg-rose-50/20 font-bold' : ''
                            ]" @click="openContract(c)">
                                <td class="py-4 px-6 font-bold text-slate-800">#{{ c.id }}</td>
                                <td class="py-4 px-4 font-bold text-emerald-600">{{ c.room }}</td>
                                <td class="py-4 px-4">
                                    <div class="flex flex-col">
                                        <span>{{ c.tenant }}</span>
                                        <span class="text-[10px] text-slate-400 font-semibold">{{ c.phone }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-500">{{ formatDate(c.start) }}</td>
                                <td class="py-4 px-4 text-slate-500" :class="{ 'text-rose-600 font-bold': c.status === 'expired' }">{{ formatDate(c.end) }}</td>
                                <td class="py-4 px-4">
                                    <span :class="[
                                        'px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider',
                                        c.depositPaid ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100'
                                    ]">
                                        {{ c.depositPaid ? 'Đã cọc' : 'Chưa cọc' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex flex-col gap-1">
                                        <span :title="getStatusConfig(c.status).label" :class="['px-3 py-1 rounded-lg text-xs font-black border flex items-center gap-1.5 w-fit shadow-xs', getStatusConfig(c.status).cls]">
                                            <span class="w-2 h-2 rounded-full" :class="getStatusConfig(c.status).dot"></span>
                                            {{ getStatusConfig(c.status).code }}
                                        </span>
                                        <span v-if="c.ocr_status === 'failed'" class="text-[10px] text-rose-600 font-bold">⚠️ OCR từ chối ảnh trống</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right" @click.stop>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openContract(c)" class="w-7 h-7 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-lg flex items-center justify-center transition-colors" title="Xem chi tiết"><i class="bi bi-eye"></i></button>
                                        <a v-if="c.original_contract?.contract_file_path" :href="`/storage/${c.original_contract.contract_file_path}`" target="_blank" class="w-7 h-7 bg-slate-50 hover:bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center transition-colors" title="Tải/Xem PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                        <button v-if="c.status === 'awaiting_upload'" @click="openUploadModal(c)" class="w-7 h-7 bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center transition-colors" title="Upload ảnh ký tay"><i class="bi bi-upload"></i></button>
                                        <button v-if="c.status === 'awaiting_upload'" @click="submitCancelDraft(c)" class="w-7 h-7 bg-slate-50 hover:bg-rose-100 text-rose-600 rounded-lg flex items-center justify-center transition-colors" title="Hủy nháp"><i class="bi bi-x-circle"></i></button>
                                        <button v-if="c.status === 'expired'" @click="openLiquidationModal(c)" class="px-2.5 py-1 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-[10px] font-bold shadow-xs transition-colors flex items-center gap-1">
                                            <i class="bi bi-calculator"></i> Thanh lý
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile List -->
            <div class="block lg:hidden space-y-4">
                <div v-for="c in contracts" :key="c.id" :class="[
                    'bg-white border border-slate-150 rounded-3xl p-5 shadow-sm space-y-3',
                    c.status === 'expired' ? 'border-rose-200 bg-rose-50/10' : ''
                ]" @click="openContract(c)">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[9px] font-extrabold text-slate-400 uppercase tracking-wider">Hợp đồng #{{ c.id }}</span>
                            <div class="text-sm font-black text-slate-800 mt-0.5">Phòng {{ c.room }}</div>
                        </div>
                        <span :class="['px-3 py-1 rounded-lg text-xs font-black border flex items-center gap-1.5 w-fit shadow-xs', getStatusConfig(c.status).cls]">
                            <span class="w-2 h-2 rounded-full" :class="getStatusConfig(c.status).dot"></span>
                            {{ getStatusConfig(c.status).code }}
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs pt-2 border-t border-slate-50 font-semibold text-slate-600">
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">Người thuê:</span>
                            <span class="text-slate-700 font-bold">{{ c.tenant }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">Giá thuê:</span>
                            <span class="text-slate-700 font-bold">{{ formatMoney(c.rent) }}/tháng</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-bold uppercase text-[9px]">Thời hạn:</span>
                            <span class="text-slate-500">{{ formatDate(c.start) }} - {{ formatDate(c.end) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-50" @click.stop>
                        <button @click="openContract(c)" class="w-8 h-8 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-xl flex items-center justify-center transition-colors">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button v-if="c.status === 'awaiting_upload'" @click="openUploadModal(c)" class="px-3 py-1.5 bg-amber-500 text-white rounded-xl text-xs font-bold flex items-center gap-1">
                            <i class="bi bi-upload"></i> Upload
                        </button>
                        <button v-if="c.status === 'expired'" @click="openLiquidationModal(c)" class="px-3 py-1.5 bg-rose-500 text-white rounded-xl text-xs font-bold flex items-center gap-1">
                            <i class="bi bi-calculator"></i> Thanh lý
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
                        <h3 class="text-sm font-bold text-slate-800">Chi tiết Hợp đồng #{{ selectedContract.id }}</h3>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- OCR Alert error if failed -->
                        <div v-if="selectedContract.ocr_status === 'failed'" class="p-3.5 bg-rose-50 border border-rose-200 rounded-2xl space-y-1">
                            <div class="flex items-center gap-2 text-rose-700 font-bold text-xs">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>Cảnh báo OCR Kiểm Duyệt Ảnh:</span>
                            </div>
                            <p class="text-xs text-rose-600 font-medium">{{ selectedContract.ocr_rejection_reason }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Phòng</span>
                                <p class="font-bold text-emerald-600">{{ selectedContract.room }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Người đại diện thuê</span>
                                <p class="font-bold text-slate-800">{{ selectedContract.tenant }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Số CCCD</span>
                                <p class="font-bold text-slate-700 font-mono">{{ selectedContract.tenant_cccd || 'Chưa cập nhật' }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Trạng thái</span>
                                <span :class="['px-2.5 py-0.5 rounded text-[10px] font-black border inline-block', getStatusConfig(selectedContract.status).cls]">
                                    {{ getStatusConfig(selectedContract.status).code }}
                                </span>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hiệu lực từ</span>
                                <p class="font-bold text-slate-800">{{ formatDate(selectedContract.start) }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hết hạn vào</span>
                                <p class="font-bold text-slate-800">{{ formatDate(selectedContract.end) }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tiền thuê hàng tháng</span>
                                <p class="font-bold text-slate-800">{{ formatMoney(selectedContract.rent) }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Số tiền cọc</span>
                                <p class="font-bold text-slate-800">{{ formatMoney(selectedContract.deposit) }}</p>
                            </div>
                        </div>

                        <!-- Rule Notice -->
                        <div class="p-3 bg-amber-50/60 border border-amber-200 rounded-2xl text-[11px] text-amber-800 font-medium space-y-1">
                            <div class="font-bold flex items-center gap-1.5">
                                <i class="bi bi-shield-check text-amber-600"></i> Quy tắc Thanh lý Hợp đồng:
                            </div>
                            <p>Để đảm bảo đúng quy trình pháp lý, hợp đồng <strong>phải chuyển sang trạng thái Hết Hạn (expired)</strong> mới kích hoạt chức năng Thanh lý.</p>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 flex flex-wrap items-center justify-end gap-2 bg-slate-50/50">
                        <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="closeModal">Đóng</button>
                        
                        <!-- Cancel draft -->
                        <button v-if="selectedContract.status === 'awaiting_upload'" @click="submitCancelDraft" class="px-4 py-2 bg-slate-200 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl transition-colors">
                            Hủy hợp đồng nháp
                        </button>

                        <!-- Mark Expired (Active / Expiring -> Expired) -->
                        <button v-if="selectedContract.status === 'active' || selectedContract.status === 'expiring'" @click="submitMarkExpired" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                            {{ selectedContract.end && new Date(selectedContract.end) > new Date() ? 'Chấm dứt HĐ trước thời hạn' : 'Chuyển trạng thái Hết Hạn' }}
                        </button>

                        <!-- Extend contract -->
                        <button v-if="selectedContract.status === 'active' || selectedContract.status === 'expired'" @click="openExtendModal" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                            Gia hạn hợp đồng
                        </button>

                        <!-- Liquidate contract STRICT CHECK -->
                        <button v-if="selectedContract.status === 'expired'" @click="openLiquidationModal" class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow-md transition-colors flex items-center gap-1">
                            <i class="bi bi-calculator"></i> Thanh lý Hợp Đồng
                        </button>
                    </div>
                </div>
            </div>

            <!-- Create Contract Modal -->
            <div v-if="showAddModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-2 sm:p-4" @click.self="showAddModal = false">
                <div :class="[
                    'bg-white rounded-t-[32px] sm:rounded-3xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[88vh] sm:max-h-[92vh] transition-all duration-300 mx-auto',
                    activeStep === 3 ? 'max-w-4xl' : 'max-w-2xl'
                ]">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <div class="space-y-0.5">
                            <h3 class="text-base font-bold text-slate-800">Tạo hợp đồng thuê mới</h3>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bước {{ activeStep }} / 3</span>
                        </div>
                        <button @click="showAddModal=false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 transition-colors">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="px-6 py-3 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center text-xs font-bold text-slate-400">
                        <button @click="goToStep(1)" class="flex items-center gap-1.5 transition-colors hover:text-emerald-600" :class="activeStep >= 1 ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                            <span>1. Khách & Tiền cọc</span>
                        </button>
                        <i class="bi bi-chevron-right text-slate-300"></i>
                        <button @click="goToStep(2)" class="flex items-center gap-1.5 transition-colors hover:text-emerald-600" :class="activeStep >= 2 ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                            <span>2. Quét Ảnh (OCR)</span>
                        </button>
                        <i class="bi bi-chevron-right text-slate-300"></i>
                        <button @click="goToStep(3)" class="flex items-center gap-1.5 transition-colors hover:text-emerald-600" :class="activeStep >= 3 ? 'text-emerald-600 font-bold' : 'text-slate-400'">
                            <span>3. Kiểm Tra & Chỉnh Sửa</span>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- Step 1 -->
                        <div v-if="activeStep === 1" class="space-y-4">
                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Chọn lịch hẹn khách ký HĐ <span class="text-rose-500">*</span></label>
                                    <select v-model="addForm.appointment_id" :class="!addForm.appointment_id ? 'border-2 border-rose-500 bg-rose-50/40 text-rose-900 font-semibold' : 'border-slate-200 focus:border-emerald-500 font-semibold'" class="w-full px-3.5 py-2.5 border rounded-xl text-xs outline-none transition-all">
                                        <option value="" disabled>-- Bắt buộc chọn người đăng ký lịch hẹn --</option>
                                        <option v-for="apt in props.appointments" :key="apt.id" :value="apt.id">
                                            Phòng {{ apt.room?.room_number }} - {{ apt.user?.name }} ({{ apt.user?.phone }})
                                        </option>
                                    </select>
                                    <p v-if="!addForm.appointment_id" class="text-[11.5px] text-rose-600 font-bold flex items-center gap-1.5 mt-1.5 p-2 bg-rose-50 border border-rose-200 rounded-xl shadow-xs">
                                        <i class="bi bi-exclamation-triangle-fill text-rose-500 text-base"></i>
                                        <span>Bắt buộc phải chọn Người đăng ký lịch hẹn trước khi tiếp tục!</span>
                                    </p>
                                </div>
                                <div class="space-y-1" v-if="addForm.room">
                                    <label class="text-xs font-bold text-slate-500">Phòng đã chọn</label>
                                    <input v-model="addForm.room" readonly class="w-full bg-slate-50 px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-emerald-600 outline-none"/>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Giá thuê (đ/tháng)</label>
                                    <input v-model="displayRent" type="text" placeholder="0" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-bold text-slate-700 outline-none transition-all"/>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Tiền đã đặt cọc (đ)</label>
                                    <input v-model="displayDeposit" type="text" placeholder="0" class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-bold text-slate-700 outline-none transition-all"/>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500">Số lượng người ở <span class="text-rose-500">*</span></label>
                                    <input v-model.number="addForm.number_of_tenants" type="number" min="1" max="20" placeholder="1" :class="tenantCountErrorMsg ? 'border-2 border-rose-500 bg-rose-50/40 text-rose-900 font-bold' : 'border-slate-200 focus:border-emerald-500 font-bold'" class="w-full px-3.5 py-2.5 border rounded-xl text-xs outline-none transition-all"/>
                                </div>
                            </div>
                            <p v-if="tenantCountErrorMsg" class="text-[11.5px] text-rose-600 font-bold flex items-center gap-1.5 mt-2 p-2.5 bg-rose-50 border border-rose-200 rounded-xl shadow-xs">
                                <i class="bi bi-exclamation-triangle-fill text-rose-500 text-base flex-shrink-0"></i>
                                <span>{{ tenantCountErrorMsg }}</span>
                            </p>
                        </div>

                        <!-- Step 2: Quét ảnh OCR -->
                        <div v-if="activeStep === 2" class="space-y-4">
                            <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-xs text-blue-800 font-semibold flex items-center gap-2">
                                <i class="bi bi-camera-fill text-lg text-blue-600"></i>
                                <span>Tải ảnh hợp đồng giấy để hệ thống tự động bóc tách nét chữ (OCR)</span>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500">Tải ảnh chụp / scan hợp đồng giấy <span class="text-rose-500">*</span></label>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    multiple
                                    @change="handleImageSelect"
                                    class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all cursor-pointer bg-slate-50"
                                />

                                <div v-if="imagePreviews.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-2 pt-2">
                                    <div v-for="(src, i) in imagePreviews" :key="i" class="relative group rounded-xl overflow-hidden border border-slate-200 aspect-[3/4]">
                                        <img :src="src" class="w-full h-full object-cover"/>
                                    </div>
                                </div>
                            </div>

                            <div v-if="addForm.signed_image && addForm.signed_image.length > 0" class="pt-2">
                                <button 
                                    @click="scanOcrFile(addForm.signed_image[0])" 
                                    :disabled="isScanningOcr"
                                    class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer"
                                >
                                    <i v-if="isScanningOcr" class="bi bi-arrow-repeat animate-spin text-base"></i>
                                    <i v-else class="bi bi-scan-magic text-base"></i>
                                    <span>{{ isScanningOcr ? (ocrProgressText || 'Đang phân tích nét chữ hợp đồng...') : 'Quét OCR & Lấy Thông Tin Tự Động (Sang Bước 3)' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Đối soát & Nhập thủ công các ô lỗi -->
                        <div v-if="activeStep === 3" class="space-y-4">
                            <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-900 font-medium space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-amber-950 text-sm">
                                    <i class="bi bi-pencil-square text-amber-600 text-base"></i> Đối soát & Chỉnh sửa dữ liệu bóc tách (OCR):
                                </div>
                                <p class="text-[12px] leading-relaxed">Vui lòng đối soát dữ liệu bên dưới với hợp đồng giấy. Ô nào <strong>viền đỏ</strong> nghĩa là chữ trên hợp đồng mờ/chưa quét được, bạn có thể tự do chỉnh sửa và nhập bổ sung thủ công.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- KHU VỰC BÊN A (BÊN CHO THUÊ NHÀ - CHỦ TRỌ) -->
                                <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                                    <div class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 pb-2.5">
                                        <i class="bi bi-house-door-fill text-emerald-600 text-base"></i> BÊN A: BÊN CHO THUÊ NHÀ (CHỦ TRỌ)
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">Đại diện (Họ tên Bên A)</label>
                                        <input v-model="addForm.landlord_name" :class="!addForm.landlord_name ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200'" class="w-full px-3.5 py-2.5 border rounded-xl text-xs font-semibold text-slate-800 outline-none" placeholder="Họ tên chủ trọ"/>
                                        <p v-if="!addForm.landlord_name" class="text-[10px] text-rose-500 font-semibold">⚠️ Không quét được tên Bên A</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-[11px] font-bold text-slate-500">Số CMND/CCCD Bên A</label>
                                            <input v-model="addForm.landlord_cccd" :class="!addForm.landlord_cccd ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200'" class="w-full px-3 py-2 border rounded-xl text-xs font-semibold text-slate-800 outline-none" placeholder="CMND/CCCD chủ trọ"/>
                                            <p v-if="!addForm.landlord_cccd" class="text-[10px] text-rose-500 font-semibold">⚠️ Chưa nhận CCCD</p>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[11px] font-bold text-slate-500">Điện thoại Bên A</label>
                                            <input v-model="addForm.landlord_phone" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 outline-none" placeholder="SĐT chủ trọ"/>
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">Địa chỉ nhà cho thuê (Bên A)</label>
                                        <input v-model="addForm.landlord_address" :class="!addForm.landlord_address ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200'" class="w-full px-3.5 py-2 border rounded-xl text-xs font-semibold text-slate-800 outline-none" placeholder="Địa chỉ chi tiết nhà trọ"/>
                                    </div>
                                </div>

                                <!-- KHU VỰC BÊN B (BÊN THUÊ NHÀ - KHÁCH THUÊ) -->
                                <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                                    <div class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 pb-2.5">
                                        <i class="bi bi-person-fill text-blue-600 text-base"></i> BÊN B: BÊN THUÊ NHÀ (KHÁCH THUÊ)
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-[11px] font-bold text-slate-500">Đại diện (Họ tên Bên B) <span class="text-rose-500">*</span></label>
                                            <input v-model="addForm.tenant_name" :class="!addForm.tenant_name ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200'" class="w-full px-3.5 py-2 border rounded-xl text-xs font-semibold text-slate-800 outline-none" placeholder="Họ tên khách thuê"/>
                                            <p v-if="!addForm.tenant_name" class="text-[10px] text-rose-500 font-semibold">⚠️ Chưa nhận Tên Bên B</p>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[11px] font-bold text-slate-500">Số CCCD / CMND Bên B <span class="text-rose-500">*</span></label>
                                            <input v-model="addForm.tenant_cccd" maxlength="12" :class="(!addForm.tenant_cccd || String(addForm.tenant_cccd).length < 9) ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200'" class="w-full px-3 py-2 border rounded-xl text-xs font-semibold text-slate-800 outline-none" placeholder="12 số CCCD"/>
                                            <p v-if="!addForm.tenant_cccd || String(addForm.tenant_cccd).length < 9" class="text-[10px] text-rose-500 font-semibold">⚠️ Chưa có CCCD</p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-[11px] font-bold text-slate-500">SĐT Bên B</label>
                                            <input v-model="addForm.tenant_phone" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 outline-none" placeholder="Số điện thoại khách"/>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[11px] font-bold text-slate-500">Ngày sinh Bên B</label>
                                            <input v-model="addForm.tenant_dob" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 outline-none" placeholder="DD/MM/YYYY"/>
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">Hộ khẩu thường trú (HKTT) Bên B</label>
                                        <input v-model="addForm.tenant_address" :class="!addForm.tenant_address ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200'" class="w-full px-3.5 py-2 border rounded-xl text-xs font-semibold text-slate-800 outline-none" placeholder="Địa chỉ thường trú khách thuê"/>
                                    </div>
                                </div>
                            </div>

                            <!-- KHU VỰC ĐIỀU KHOẢN & THỜI HẠN THUÊ -->
                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                                <div class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-200 pb-2.5">
                                    <i class="bi bi-calendar-range-fill text-amber-600 text-base"></i> ĐIỀU KHOẢN HỢP ĐỒNG & THỜI HẠN THUÊ
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">Ngày bắt đầu hợp đồng <span class="text-rose-500">*</span></label>
                                        <input v-model="addForm.start_date" type="date" :class="!addForm.start_date ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200'" class="w-full px-3 py-2 border rounded-xl text-xs font-semibold text-slate-700 outline-none"/>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">Ngày kết thúc dự kiến <span class="text-rose-500">*</span></label>
                                        <input v-model="addForm.end_date" type="date" :class="!addForm.end_date ? 'border-rose-400 bg-rose-50/20' : 'border-slate-200'" class="w-full px-3 py-2 border rounded-xl text-xs font-semibold text-slate-700 outline-none"/>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">Giá thuê (đ/tháng)</label>
                                        <input v-model="displayRent" type="text" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 outline-none"/>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">Tiền đặt cọc (đ)</label>
                                        <input v-model="displayDeposit" type="text" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 outline-none"/>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[11px] font-bold text-slate-500">Số lượng người ở <span class="text-rose-500">*</span></label>
                                        <input v-model.number="addForm.number_of_tenants" type="number" min="1" max="20" placeholder="1" :class="tenantCountErrorMsg ? 'border-2 border-rose-500 bg-rose-50/40 text-rose-900 font-bold' : 'border-slate-200 focus:border-emerald-500 font-bold'" class="w-full px-3 py-2 border rounded-xl text-xs outline-none"/>
                                    </div>
                                </div>

                                <p v-if="tenantCountErrorMsg" class="text-[11.5px] text-rose-600 font-bold flex items-center gap-1.5 mt-2 p-2.5 bg-rose-50 border border-rose-200 rounded-xl shadow-xs">
                                    <i class="bi bi-exclamation-triangle-fill text-rose-500 text-base flex-shrink-0"></i>
                                    <span>{{ tenantCountErrorMsg }}</span>
                                </p>

                                <div class="pt-1">
                                    <label class="flex items-start gap-2 cursor-pointer p-3 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700">
                                        <input type="checkbox" v-model="addForm.terms_accepted" class="mt-0.5 rounded text-emerald-500 focus:ring-emerald-400"/>
                                        <span>Cam kết các thông tin bóc tách BÊN A & BÊN B là đúng sự thật và tuân thủ các quy định pháp luật về hợp đồng thuê nhà.</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between gap-2.5 bg-slate-50/50">
                        <button v-if="activeStep > 1" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="activeStep--">Quay lại</button>
                        <div v-else></div>

                        <div class="flex items-center gap-2">
                            <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="showAddModal = false">Hủy</button>
                            <button v-if="activeStep < 3" 
                                :disabled="activeStep === 1 && !isStep1Valid"
                                :class="[
                                    'px-5 py-2.5 font-bold text-xs rounded-xl transition-all',
                                    (activeStep === 1 && !isStep1Valid) 
                                        ? 'bg-slate-300 text-slate-500 cursor-not-allowed pointer-events-none opacity-60 shadow-none' 
                                        : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-md cursor-pointer'
                                ]" 
                                @click="goToNextStep">
                                {{ activeStep === 2 ? 'Sang Bước 3 (Kiểm tra)' : 'Tiếp tục' }}
                            </button>
                            <button v-else 
                                :disabled="!isStep1Valid"
                                :class="[
                                    'px-5 py-2.5 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5',
                                    !isStep1Valid 
                                        ? 'bg-slate-300 text-slate-500 cursor-not-allowed pointer-events-none opacity-60 shadow-none' 
                                        : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-md'
                                ]" 
                                @click="submitAddContract">
                                <i class="bi bi-check-circle-fill text-sm"></i>
                                <span>Xác Nhận & Tạo Hợp Đồng</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Signed Image Modal -->
            <div v-if="showUploadModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                        <h3 class="text-sm font-bold text-slate-800">Upload Hợp Đồng Ký Tay (Quét OCR)</h3>
                        <button @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    
                    <form @submit.prevent="submitUpload">
                        <div class="p-6 space-y-4">
                            <p class="text-xs text-slate-500 leading-relaxed">
                                Vui lòng tải lên ảnh chụp bản hợp đồng gốc đã điền tay đầy đủ các thông tin thiết yếu (Bên B, CCCD, Thời hạn, Giá thuê, Chữ ký). Hợp đồng mẫu in trống sẽ bị hệ thống tự động từ chối.
                            </p>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Ảnh hợp đồng (JPEG, PNG) <span class="text-rose-500">*</span></label>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    multiple
                                    @input="uploadForm.signed_image = Array.from($event.target.files)"
                                    class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                                    required
                                />
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
                            <button type="button" @click="showUploadModal = false" class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl">Đóng</button>
                            <button type="submit" :disabled="uploadForm.processing" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-md disabled:opacity-50">
                                {{ uploadForm.processing ? 'Đang kiểm duyệt OCR...' : 'Xác minh OCR & Kích hoạt' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Extend Contract Modal -->
            <div v-if="showExtendModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl p-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-500 text-xl font-bold">
                            <i class="bi bi-calendar-plus"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Gia hạn hợp đồng</h3>
                            <p class="text-xs text-slate-400">Yêu cầu xác minh số CCCD của Khách thuê khi gia hạn</p>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2 text-xs">
                        <div class="space-y-1">
                            <label class="font-bold text-slate-600">Số CCCD Khách Thuê <span class="text-rose-500">*</span></label>
                            <input type="text" v-model="extendForm.tenant_cccd" maxlength="12" placeholder="Nhập 12 số CCCD" class="w-full px-3.5 py-2 border border-slate-200 rounded-xl outline-none focus:border-emerald-500"/>
                        </div>

                        <div class="space-y-1">
                            <label class="font-bold text-slate-600">Ngày hết hạn mới <span class="text-rose-500">*</span></label>
                            <input type="date" v-model="extendForm.new_end_date" class="w-full px-3.5 py-2 border border-slate-200 rounded-xl outline-none focus:border-emerald-500"/>
                        </div>

                        <div class="space-y-1">
                            <label class="font-bold text-slate-600">Giá thuê mới (nếu có thỏa thuận điều chỉnh)</label>
                            <input type="number" v-model="extendForm.new_monthly_rent" placeholder="Giữ nguyên nếu không đổi" class="w-full px-3.5 py-2 border border-slate-200 rounded-xl outline-none focus:border-emerald-500"/>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button @click="showExtendModal = false" class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl">Hủy</button>
                        <button @click="submitExtendContract" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md">Xác nhận gia hạn</button>
                    </div>
                </div>
            </div>

            <!-- Liquidation Modal (STRICTLY FOR EXPIRED STATUS) -->
            <div v-if="showLiquidationModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl p-6 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-rose-50 rounded-xl flex items-center justify-center text-rose-500 text-xl font-bold">
                            <i class="bi bi-calculator"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Thanh lý & Quyết toán Hợp đồng Hết hạn</h3>
                            <p class="text-xs text-slate-400">Trạng thái hiện tại: Đã hết hạn (Expired)</p>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2 text-xs">
                        <div class="space-y-1">
                            <label class="font-bold text-slate-600">Phương án xử lý Tiền đặt cọc ({{ formatMoney(selectedContract?.deposit) }})</label>
                            <select v-model="liquidationForm.deposit_handling" class="w-full px-3.5 py-2 border border-slate-200 rounded-xl outline-none focus:border-emerald-500">
                                <option value="refund_full">Hoàn lại 100% tiền cọc</option>
                                <option value="refund_partial">Hoàn lại một phần tiền cọc</option>
                                <option value="keep_deposit">Khấu trừ toàn bộ / Mất cọc (Do vi phạm/hỏng hóc)</option>
                            </select>
                        </div>

                        <div v-if="liquidationForm.deposit_handling === 'refund_partial'" class="space-y-1">
                            <label class="font-bold text-slate-600">Số tiền cọc hoàn trả thực tế (đ)</label>
                            <input type="number" v-model="liquidationForm.deposit_refund_amount" class="w-full px-3.5 py-2 border border-slate-200 rounded-xl outline-none focus:border-emerald-500"/>
                        </div>

                        <div class="space-y-1">
                            <label class="font-bold text-slate-600">Ghi chú quyết toán & lý do thanh lý</label>
                            <textarea v-model="liquidationForm.notes" rows="3" placeholder="Nhập ghi chú thanh lý..." class="w-full px-3.5 py-2 border border-slate-200 rounded-xl outline-none focus:border-emerald-500"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button @click="showLiquidationModal = false" class="px-4 py-2 border border-slate-200 text-slate-600 font-bold text-xs rounded-xl">Đóng</button>
                        <button @click="submitLiquidationContract" class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl shadow-md">Xác nhận Thanh lý</button>
                    </div>
                </div>
            </div>

            <!-- Modal Danh sách User ấn ưng / Hợp đồng đang chờ (Ảnh 2) -->
            <div v-if="showPendingRequestsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl overflow-hidden flex flex-col max-h-[85vh]">
                    <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-gradient-to-r from-amber-500 to-orange-500 text-white">
                        <div class="flex items-center gap-2">
                            <i class="bi bi-heart-fill text-xl"></i>
                            <div>
                                <h3 class="text-sm font-bold">Danh sách Hợp đồng đang chờ (User đã ấn ưng)</h3>
                                <p class="text-[11px] text-amber-100">Các khách hàng đã nhấn quan tâm / đăng ký thuê nhưng chưa tạo hợp đồng</p>
                            </div>
                        </div>
                        <button @click="showPendingRequestsModal = false" class="text-amber-100 hover:text-white transition-colors cursor-pointer">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <div class="p-5 overflow-y-auto space-y-3 flex-1">
                        <div v-if="!props.appointments || props.appointments.length === 0" class="text-center py-8 text-slate-400 space-y-2">
                            <i class="bi bi-inbox text-4xl block text-slate-300"></i>
                            <p class="text-xs font-semibold">Hiện chưa có khách hàng nào nhấn ưng hoặc chờ duyệt hợp đồng.</p>
                        </div>
                        <div v-else v-for="apt in props.appointments" :key="apt.id" class="p-4 bg-slate-50 hover:bg-amber-50/40 border border-slate-200 hover:border-amber-300 rounded-2xl flex items-center justify-between transition-all">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-lg">Phòng {{ apt.room?.room_number }}</span>
                                    <span class="text-xs font-bold text-slate-800">{{ apt.user?.name || 'Khách thuê' }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-3">
                                    <span><i class="bi bi-telephone"></i> {{ apt.user?.phone || 'Chưa có SĐT' }}</span>
                                    <span v-if="apt.room?.boarding_house"><i class="bi bi-geo-alt"></i> {{ apt.room.boarding_house.name }}</span>
                                </div>
                            </div>

                            <button @click="openContractForAppointment(apt)" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                                <i class="bi bi-file-earmark-plus"></i> Tạo hợp đồng ngay
                            </button>
                        </div>
                    </div>

                    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                        <button @click="showPendingRequestsModal = false" class="px-4 py-2 border border-slate-200 hover:bg-slate-100 text-slate-600 font-bold text-xs rounded-xl">Đóng</button>
                    </div>
                </div>
            </div>

        </Teleport>
    </LandlordLayout>
</template>
