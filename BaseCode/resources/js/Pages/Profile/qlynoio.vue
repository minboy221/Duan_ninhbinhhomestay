<script setup>
import { ref, computed } from 'vue';
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { showSuccess, showError } from '@/Utils/swal';

const props = defineProps({
    user: Object,
    contract: Object
});

const showPdfModal = ref(false);
const showTerminateModal = ref(false);

const terminateForm = useForm({
    reason: ''
});

const submitTerminateRequest = () => {
    if (!props.contract?.id) return;
    if (!terminateForm.reason.trim()) {
        showError('Vui lòng điền lý do', 'Bạn cần điền lý do muốn chấm dứt hợp đồng.');
        return;
    }

    terminateForm.post(route('contracts.request-termination', props.contract.id), {
        onSuccess: () => {
            showTerminateModal.value = false;
            terminateForm.reset();
            showSuccess('Đã gửi yêu cầu', 'Yêu cầu chấm dứt hợp đồng của bạn đã được gửi tới Chủ trọ thành công.');
        },
        onError: (err) => {
            showError('Thao tác thất bại', err.reason || 'Có lỗi xảy ra khi gửi yêu cầu.');
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('vi-VN');
};

const getContractUrl = () => {
    if (!props.contract?.contract_file_path) return null;
    return '/storage/' + props.contract.contract_file_path;
};

const isImage = (path) => {
    if (!path) return false;
    return path.match(/\.(jpeg|jpg|gif|png)$/i) != null;
};

const getStatusLabel = computed(() => {
    if (!props.contract) return 'Không hoạt động';
    switch (props.contract.status) {
        case 'active': return 'Hợp đồng hiệu lực';
        case 'termination_requested': return 'Yêu cầu chấm dứt (Chờ duyệt)';
        case 'terminated': return 'Đã thanh lý';
        case 'expired': return 'Đã hết hạn';
        default: return 'Chưa ký';
    }
});

const getStatusBg = computed(() => {
    if (!props.contract) return '#ef4444';
    switch (props.contract.status) {
        case 'active': return '#22c55e';
        case 'termination_requested': return '#f97316';
        case 'terminated': return '#64748b';
        case 'expired': return '#ef4444';
        default: return '#ef4444';
    }
});

const terminateButtonText = computed(() => {
    if (!props.contract?.end_date) return 'Chấm dứt hợp đồng';
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const endDate = new Date(props.contract.end_date);
    endDate.setHours(0, 0, 0, 0);
    
    const diffTime = endDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays > 3) {
        return 'Chấm dứt HĐ trước thời hạn';
    } else {
        return 'Chấm dứt hợp đồng';
    }
});
</script>

<template>
    <Head title="Trang Quản Lý Nơi Ở | Ninh Bình HomeStay" />
    <UserLayout>
        <div class="bao_item">
            <div class="infor_noidung">
                <div v-if="!contract" class="alert-no-contract" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; font-weight: 600;">
                    <i class="bi bi-exclamation-triangle-fill"></i> Bạn hiện chưa có hợp đồng thuê trọ nào có hiệu lực.
                </div>

                <div v-else-if="contract?.status === 'termination_requested'" class="alert-no-contract" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; background: #fff7ed; border: 1px solid #ffedd5; color: #ea580c; font-weight: 600;">
                    <i class="bi bi-clock-history"></i> Bạn đã gửi yêu cầu chấm dứt hợp đồng này. Chủ trọ đang xem xét và tiến hành thủ tục thanh lý.
                </div>
                
                <div class="title_noio" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>THÔNG TIN NƠI Ở</h2>
                    <div class="status" :style="{ background: getStatusBg }">
                        <p>{{ getStatusLabel }}</p>
                    </div>
                </div>
                
                <form action="" @submit.prevent>
                    <div class="row">
                        <div class="form-group">
                            <label>Tên phòng/Số phòng</label>
                            <input type="text" :value="contract?.room?.room_number || 'Chưa có phòng'" disabled>
                        </div>

                        <div class="form-group">
                            <label> Họ tên chủ trọ:</label>
                            <input type="text" :value="contract?.room?.boarding_house?.user?.name || contract?.room?.boardingHouse?.user?.name || 'Chưa xác định'" disabled>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>SĐT chủ trọ:</label>
                        <input type="text" :value="contract?.room?.boarding_house?.user?.phone || contract?.room?.boardingHouse?.user?.phone || 'Chưa xác định'" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Địa chỉ :</label>
                        <input type="text" :value="contract?.room?.boarding_house?.address_detail || contract?.room?.boardingHouse?.address_detail || contract?.room?.address || 'Chưa xác định'" disabled>
                    </div>
                    
                    <div class="row">
                        <div class="form-group">
                            <label>Ngày bắt đầu hợp đồng:</label>
                            <input type="text" :value="formatDate(contract?.start_date)" disabled>
                        </div>

                        <div class="form-group">
                            <label>Ngày kêt thúc dự kiến:</label>
                            <input type="text" :value="formatDate(contract?.end_date)" disabled>
                        </div>
                    </div>

                    <!-- Nút Chấm dứt hợp đồng cho Client được chuyển lên khung THÔNG TIN NƠI Ở -->
                    <div v-if="contract && ['active', 'expiring'].includes(contract.status)" style="display: flex; justify-content: flex-end; margin-top: 15px;">
                        <button @click="showTerminateModal = true" class="btn-terminate" style="background-color: #ef4444; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 14px; transition: all 0.2s;">
                            <i class="bi bi-x-circle-fill"></i> {{ terminateButtonText }}
                        </button>
                    </div>
                </form>
                
                <div class="hopdong" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>HỢP ĐỒNG THUÊ TRỌ</h2>
                    <button id="openPdf" class="btn-hopdong" @click="showPdfModal = true" :disabled="!contract">
                        Xem trực tiếp hợp đồng tại đây!
                    </button>
                </div>
                
                <div class="history_thanhtoan">
                    <h2>LỊCH SỬ HOÁ ĐƠN</h2>
                    <Link :href="route('lichsuthanhtoan')" class="btn-hopdong">Xem trực tiếp lịch sử thanh toán</Link>
                </div>
            </div>
        </div>
        
        <!-- Modal Xem PDF / Ảnh Hợp Đồng -->
        <div id="pdfModal" class="modal" :style="{ display: showPdfModal ? 'flex' : 'none' }">
            <div class="modal-content" style="max-height: 90vh; overflow-y: auto; background: white; padding: 20px; border-radius: 12px; position: relative; width: 80%; max-width: 800px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <span @click="showPdfModal = false" class="absolute top-2 right-4 text-slate-400 hover:text-slate-600 cursor-pointer" style="position: absolute; top: 10px; right: 15px; font-size: 32px; line-height: 1; z-index: 50;">&times;</span>
                
                <h3 style="margin-bottom: 15px; color: #10b981; font-weight: bold;">HỢP ĐỒNG THUÊ TRỌ</h3>
                <div v-if="!contract?.contract_file_path" class="p-6 text-center text-slate-500 font-semibold mt-4" style="color: #64748b; font-size: 1.1rem;">
                    Chưa có tệp hợp đồng được tải lên.
                </div>
                <div v-else-if="isImage(contract.contract_file_path)" class="text-center p-4" style="width: 100%;">
                    <img :src="getContractUrl()" alt="Hợp đồng" style="max-width: 100%; height: auto; border-radius: 8px;" />
                </div>
                <iframe v-else :src="getContractUrl()" style="width: 100%; height: 70vh; border: none; border-radius: 8px;"></iframe>
            </div>
        </div>

        <!-- Modal Yêu cầu Chấm dứt Hợp đồng cho Client -->
        <div v-if="showTerminateModal" class="modal" style="display: flex; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center;">
            <div class="modal-content" style="background: white; padding: 24px; border-radius: 16px; width: 90%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="font-size: 18px; font-weight: bold; color: #dc2626; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-exclamation-octagon-fill"></i> Yêu Cầu Chấm Dứt Hợp Đồng
                    </h3>
                    <button @click="showTerminateModal = false" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer;">&times;</button>
                </div>
                
                <p style="font-size: 13.5px; color: #475569; margin-bottom: 16px; line-height: 1.5;">
                    Bạn đang chuẩn bị gửi yêu cầu chấm dứt/thanh lý hợp đồng sớm cho chủ trọ. Vui lòng nhập rõ lý do bên dưới để chủ trọ tiếp nhận và xử lý.
                </p>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Lý do chấm dứt hợp đồng (*):</label>
                    <textarea v-model="terminateForm.reason" rows="4" placeholder="Ví dụ: Chuyển nơi công tác / Trả phòng do hết nhu cầu thuê..." style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; outline: none; box-sizing: border-box;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button @click="showTerminateModal = false" style="padding: 9px 16px; background: #f1f5f9; color: #475569; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Hủy bỏ</button>
                    <button @click="submitTerminateRequest" :disabled="terminateForm.processing" style="padding: 9px 18px; background: #dc2626; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-send-fill"></i> Gửi Yêu Cầu
                    </button>
                </div>
            </div>
        </div>
    </UserLayout>
</template>

<style scoped>
@import "../../css/qlynoio.css";
@import '../../css/responsive/responsiveqlytro.css';
@import '../../css/responsive/responsive.css';
@import '../../css/responsive/responsivetranguser.css';

.btn-terminate:hover {
    background-color: #dc2626 !important;
    transform: translateY(-1px);
}
</style>
