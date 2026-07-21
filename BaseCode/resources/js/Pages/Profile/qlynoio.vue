<script setup>
import { ref, computed } from 'vue';
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    contract: Object
});

const showPdfModal = ref(false);
const currentImageIndex = ref(0);

const signedImages = computed(() => {
    if (!props.contract?.signed_contract_image) return [];
    try {
        const parsed = JSON.parse(props.contract.signed_contract_image);
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
});

const nextImage = () => {
    if (currentImageIndex.value < signedImages.value.length - 1) {
        currentImageIndex.value++;
    }
};

const prevImage = () => {
    if (currentImageIndex.value > 0) {
        currentImageIndex.value--;
    }
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
                    <div class="status" :style="{ background: contract?.status === 'active' ? '#22c55e' : '#ef4444' }">
                        <p>{{ contract ? (contract.status === 'active' ? 'Hợp đồng hiệu lực' : 'Chưa ký') : 'Không hoạt động' }}</p>
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
            <div class="modal-content" style="max-height: 90vh; overflow-y: auto; background: white; padding: 20px; border-radius: 12px; position: relative; width: 80%; max-width: 800px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <span @click="showPdfModal = false" class="absolute top-2 right-4 text-slate-400 hover:text-slate-600 cursor-pointer" style="position: absolute; top: 10px; right: 15px; font-size: 32px; line-height: 1; z-index: 50;">&times;</span>
                <!-- Hiển thị bản gốc nếu có -->
                <div v-if="signedImages.length > 0" class="w-full flex flex-col items-center" style="width: 100%;">
                    <h3 style="margin-bottom: 15px; color: #10b981; font-weight: bold;">BẢN GỐC CÓ CHỮ KÝ</h3>
                    <div style="position: relative; width: 100%; display: flex; justify-content: center; align-items: center;">
                        <button v-if="signedImages.length > 1" @click="prevImage" :disabled="currentImageIndex === 0" style="position: absolute; left: 0; padding: 10px 15px; background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;" :style="{ opacity: currentImageIndex === 0 ? 0.3 : 1 }">&lt;</button>
                        
                        <img :src="'/storage/' + signedImages[currentImageIndex]" alt="Hợp đồng gốc" style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);" />
                        
                        <button v-if="signedImages.length > 1" @click="nextImage" :disabled="currentImageIndex === signedImages.length - 1" style="position: absolute; right: 0; padding: 10px 15px; background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;" :style="{ opacity: currentImageIndex === signedImages.length - 1 ? 0.3 : 1 }">&gt;</button>
                    </div>
                    <div v-if="signedImages.length > 1" style="margin-top: 10px; font-size: 14px; color: #64748b; font-weight: 600;">
                        Trang {{ currentImageIndex + 1 }} / {{ signedImages.length }}
                    </div>
                    
                    <div style="margin-top: 20px; width: 100%; border-top: 1px dashed #cbd5e1; padding-top: 20px; text-align: center;">
                        <p style="font-size: 12px; color: #94a3b8; margin-bottom: 10px;">Bản PDF đối chiếu (Không có chữ ký)</p>
                        <a :href="getContractUrl()" target="_blank" style="padding: 8px 16px; background: #f8fafc; color: #3b82f6; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; border: 1px solid #e2e8f0; display: inline-block;">Mở bản nháp PDF</a>
                    </div>
                </div>

                <!-- Hiển thị PDF nếu chưa có bản gốc -->
                <div v-else-if="!contract?.contract_file_path" class="p-6 text-center text-slate-500 font-semibold mt-4" style="color: #64748b; font-size: 1.1rem;">
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
