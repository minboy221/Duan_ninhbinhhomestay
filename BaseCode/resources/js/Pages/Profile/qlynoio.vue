<script setup>
import { ref } from 'vue';
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    contract: Object
});

const showPdfModal = ref(false);

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
</script>

<template>
    <Head title="Trang Quản Lý Nơi Ở | Ninh Bình HomeStay" />
    <UserLayout>
        <div class="bao_item">
            <div class="infor_noidung">
                <div v-if="!contract" class="alert-no-contract" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; font-weight: 600;">
                    <i class="bi bi-exclamation-triangle-fill"></i> Bạn hiện chưa có hợp đồng thuê trọ nào có hiệu lực.
                </div>
                
                <div class="title_noio">
                    <h2>THÔNG TIN NƠI Ở</h2>
                    <div class="status" :style="{ background: contract?.status === 'signed' ? '#22c55e' : '#ef4444' }">
                        <p>{{ contract ? (contract.status === 'signed' ? 'Hợp đồng hiệu lực' : 'Chưa ký') : 'Không hoạt động' }}</p>
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
                </form>
                
                <div class="hopdong">
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
        
        <div id="pdfModal" class="modal" :style="{ display: showPdfModal ? 'flex' : 'none' }">
            <span class="close" @click="showPdfModal = false">&times;</span>
            <div class="modal-content" style="max-height: 90vh; overflow-y: auto; background: white; padding: 20px; border-radius: 12px; position: relative; width: 80%; max-width: 800px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div v-if="!contract?.contract_file_path" class="p-6 text-center text-slate-500 font-semibold" style="color: #64748b; font-size: 1.1rem;">
                    Chưa có tệp hợp đồng được tải lên.
                </div>
                <div v-else-if="isImage(contract.contract_file_path)" class="text-center p-4" style="width: 100%;">
                    <img :src="getContractUrl()" alt="Hợp đồng" style="max-width: 100%; height: auto; border-radius: 8px;" />
                </div>
                <iframe v-else :src="getContractUrl()" style="width: 100%; height: 70vh; border: none; border-radius: 8px;"></iframe>
            </div>
        </div>
    </UserLayout>
</template>

<style scoped>
@import "../../css/qlynoio.css";
@import '../../css/responsive/responsiveqlytro.css';
@import '../../css/responsive/responsive.css';
@import '../../css/responsive/responsivetranguser.css';
</style>
