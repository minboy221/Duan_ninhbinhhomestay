<script setup>
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
    user: Object,
    verification: Object,
    boardingHouse: Object,
});

// Hàm lấy URL ảnh private
const getPrivateImageUrl = (path, type) => {
    if (!path) return "https://placehold.co/400x300?text=No+Image";
    const filename = path.replace(/\\/g, "/").split("/").pop();
    return route("admin.files.private", { type: type, filename: filename });
};

// Phần lưu video
const isVideo = (path) => {
    if (!path) return false;
    const ext = path.split(".").pop().toLowerCase();
    return ["mp4", "mov", "avi"].includes(ext);
};

// --- QUẢN LÝ MODAL ---
const showRejectModal = ref(false);
const showApproveModal = ref(false);
const showImageModal = ref(false);
const currentImageUrl = ref("");
const rejectReason = ref("");
const isProcessing = ref(false);

const showSuccessPopup = ref(false);
const successMessage = ref("");

const openImage = (url) => {
    currentImageUrl.value = url;
    showImageModal.value = true;
};

// Xử lý gửi request
const submitAction = (action) => {
    if (action === "reject" && !rejectReason.value.trim()) {
        alert("Vui lòng nhập lý do từ chối!");
        return;
    }

    isProcessing.value = true;

    router.post(
        route("admin.verifications.update-status", props.user.id),
        {
            action: action,
            reason: action === "reject" ? rejectReason.value : "",
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showRejectModal.value = false;
                showApproveModal.value = false;
                isProcessing.value = false;

                // Hiển thị popup đẹp
                successMessage.value =
                    action === "approve"
                        ? "Đã phê duyệt chủ trọ thành công!"
                        : "Đã từ chối hồ sơ thành công!";
                showSuccessPopup.value = true;

                // Tự động tắt popup sau 2.5s
                setTimeout(() => {
                    showSuccessPopup.value = false;
                }, 2500);
            },
            onError: () => {
                isProcessing.value = false;
            },
        },
    );
};

// Hàm định dạng ngày giờ gửi yêu cầu xác minh
const formatDate = (dateString) => {
    if (!dateString) return "Chưa cập nhật";
    const date = new Date(dateString);
    return date.toLocaleString("vi-VN", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
};
</script>

<template>

    <Head title="Admin - Chi tiết Hồ sơ Phê duyệt" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    Chi tiết Hồ sơ Phê duyệt
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Đối chiếu thông tin đăng ký hệ thống Ninh Bình StayWork của
                    <span class="font-bold text-gray-700">{{ user?.name }}</span>.
                </p>
            </div>
        </template>

        <div
            class="mt-6 flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-200 pb-5 mb-6 gap-4">
            <div class="flex items-center gap-3">
                <div
                    class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-lg font-bold shadow-sm border border-blue-200">
                    {{ user?.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ user?.name }}
                    </h2>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-x-4 gap-y-1 mt-1">
                        <p class="text-sm text-gray-500 flex items-center gap-1">
                            <i class="bi bi-envelope-fill text-gray-400"></i>
                            {{ user?.email }}
                        </p>
                        <!-- Thanh phân cách nhỏ trên màn hình máy tính -->
                        <span class="hidden sm:inline text-gray-300">|</span>
                        <p class="text-sm text-gray-500 flex items-center gap-1">
                            <i class="bi bi-clock-history text-gray-400"></i>
                            Thời gian đăng ký:
                            <span class="font-bold text-gray-700">{{
                                formatDate(verification?.created_at)
                                }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex space-x-3 w-full md:w-auto justify-end">
                <button @click="showRejectModal = true"
                    class="flex items-center gap-2 bg-white border border-red-200 text-red-600 hover:bg-red-50 px-5 py-2.5 rounded-lg font-semibold shadow-sm transition duration-200">
                    <i class="bi bi-x-circle-fill"></i> Từ chối Hồ sơ
                </button>
                <button @click="showApproveModal = true"
                    class="flex items-center gap-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-6 py-2.5 rounded-lg font-semibold shadow-md transition duration-200 hover:shadow-lg">
                    <i class="bi bi-check-circle-fill"></i> Duyệt Cấp Quyền
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- CỘT 1: KYC -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                            <i class="bi bi-person-vcard-fill text-lg"></i>
                        </div>
                        1. Xác minh Danh tính (KYC)
                    </h3>
                </div>

                <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-xl text-sm border border-gray-100">
                    <div>
                        <span class="text-gray-500 block text-[11px] font-bold uppercase tracking-wider mb-1">Số điện
                            thoại</span>
                        <span class="font-bold text-gray-900 text-base flex items-center gap-1.5">
                            <i class="bi bi-telephone-fill text-blue-500"></i>
                            {{ user?.phone || "Chưa có" }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-[11px] font-bold uppercase tracking-wider mb-1">Số CCCD /
                            Định
                            danh</span>
                        <span class="font-bold text-gray-900 text-base tracking-wider">{{
                            verification?.id_card_number || "Chưa cập nhật"
                        }}</span>
                    </div>
                </div>

                <div class="p-4 rounded-xl flex items-center justify-between shadow-sm" :class="verification?.kyc_status === 'approved'
                        ? 'bg-green-50 border border-green-200'
                        : 'bg-red-50 border border-red-200'
                    ">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl" :class="verification?.kyc_status === 'approved'
                                ? 'bg-green-100 text-green-600'
                                : 'bg-red-100 text-red-600'
                            ">
                            <i class="bi" :class="verification?.kyc_status === 'approved'
                                    ? 'bi-shield-fill-check'
                                    : 'bi-shield-fill-exclamation'
                                "></i>
                        </div>
                        <div>
                            <div class="font-bold text-sm" :class="verification?.kyc_status === 'approved'
                                    ? 'text-green-800'
                                    : 'text-red-800'
                                ">
                                Kết quả đối sánh khuôn mặt (Face-API)
                            </div>
                            <p class="text-xs mt-0.5" :class="verification?.kyc_status === 'approved'
                                    ? 'text-green-600'
                                    : 'text-red-600'
                                ">
                                {{
                                    verification?.kyc_status === "approved"
                                        ? "Hệ thống tự động đã xác nhận trùng khớp"
                                        : "Hệ thống cảnh báo rủi ro, cần kiểm tra kỹ"
                                }}
                            </p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-sm" :class="verification?.kyc_status === 'approved'
                            ? 'bg-green-500 text-white'
                            : 'bg-red-500 text-white'
                        ">
                        {{
                            verification?.kyc_status === "approved"
                                ? "Khớp 100%"
                                : "Không khớp"
                        }}
                    </span>
                </div>

                <div class="space-y-4 pt-2">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center group">
                            <span class="text-[11px] font-bold text-gray-500 block mb-2 uppercase tracking-wider">CCCD
                                MẶT
                                TRƯỚC</span>
                            <div class="relative overflow-hidden rounded-xl border border-gray-200 shadow-sm cursor-pointer"
                                @click="
                                    openImage(
                                        getPrivateImageUrl(
                                            verification?.id_card_front,
                                            'id_cards',
                                        ),
                                    )
                                    ">
                                <img :src="getPrivateImageUrl(
                                    verification?.id_card_front,
                                    'id_cards',
                                )
                                    " class="w-full h-40 object-cover group-hover:scale-105 transition duration-300" />
                                <div
                                    class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition duration-300 flex items-center justify-center">
                                    <i
                                        class="bi bi-zoom-in text-white text-2xl opacity-0 group-hover:opacity-100 transition duration-300"></i>
                                </div>
                            </div>
                        </div>
                        <div class="text-center group">
                            <span class="text-[11px] font-bold text-gray-500 block mb-2 uppercase tracking-wider">CCCD
                                MẶT
                                SAU</span>
                            <div class="relative overflow-hidden rounded-xl border border-gray-200 shadow-sm cursor-pointer"
                                @click="
                                    openImage(
                                        getPrivateImageUrl(
                                            verification?.id_card_back,
                                            'id_cards',
                                        ),
                                    )
                                    ">
                                <img :src="getPrivateImageUrl(
                                    verification?.id_card_back,
                                    'id_cards',
                                )
                                    " class="w-full h-40 object-cover group-hover:scale-105 transition duration-300" />
                                <div
                                    class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition duration-300 flex items-center justify-center">
                                    <i
                                        class="bi bi-zoom-in text-white text-2xl opacity-0 group-hover:opacity-100 transition duration-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center border-t border-gray-100 pt-6 mt-4 relative">
                        <span
                            class="absolute -top-3 left-1/2 -translate-x-1/2 bg-white px-2 text-[11px] font-bold text-gray-400 uppercase tracking-wider">ẢNH
                            CHỤP THỰC TẾ</span>
                        <div class="relative inline-block group cursor-pointer" @click="
                            openImage(
                                getPrivateImageUrl(
                                    verification?.face_auth_image,
                                    'faces',
                                ),
                            )
                            ">
                            <img :src="getPrivateImageUrl(
                                verification?.face_auth_image,
                                'faces',
                            )
                                "
                                class="w-36 h-36 object-cover rounded-full border-4 border-indigo-100 shadow-md group-hover:scale-105 transition duration-300" />
                            <div
                                class="absolute inset-0 rounded-full bg-black/0 group-hover:bg-black/20 transition duration-300 flex items-center justify-center">
                                <i
                                    class="bi bi-zoom-in text-white text-2xl opacity-0 group-hover:opacity-100 transition duration-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CỘT 2: THÔNG TIN TRỌ -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center">
                            <i class="bi bi-house-door-fill text-lg"></i>
                        </div>
                        2. Cơ sở Kinh doanh Trọ
                    </h3>
                </div>

                <div class="space-y-4 bg-gray-50 p-5 rounded-xl text-sm border border-gray-100">
                    <div>
                        <span class="text-gray-500 block text-[11px] font-bold uppercase tracking-wider mb-1">Tên cơ sở
                            trọ /
                            Homestay</span>
                        <span class="font-bold text-gray-900 text-base">{{
                            boardingHouse?.name || "Chưa cập nhật"
                            }}</span>
                    </div>
                    <div class="border-t border-gray-200 pt-3">
                        <span class="text-gray-500 block text-[11px] font-bold uppercase tracking-wider mb-1">Khu vực
                            hành
                            chính</span>
                        <span class="text-gray-800 font-medium">{{
                            boardingHouse?.district || "Chưa cập nhật"
                            }}</span>
                    </div>
                    <div class="border-t border-gray-200 pt-3">
                        <span class="text-gray-500 block text-[11px] font-bold uppercase tracking-wider mb-1">Địa chỉ
                            chi
                            tiết</span>
                        <span class="text-gray-800">{{
                            boardingHouse?.address_detail || "Chưa cập nhật"
                            }}</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="bi bi-file-earmark-text-fill text-gray-400"></i>
                        Giấy tờ pháp lý / Hợp đồng:
                    </h4>
                    <div class="grid grid-cols-3 gap-3" v-if="boardingHouse?.contract_images?.length">
                        <div v-for="(path, index
                            ) in boardingHouse.contract_images" :key="'contract-' + index"
                            class="relative group overflow-hidden rounded-lg border shadow-sm cursor-pointer aspect-[4/3]"
                            @click="
                                openImage(getPrivateImageUrl(path, 'contracts'))
                                ">
                            <img :src="getPrivateImageUrl(path, 'contracts')"
                                class="w-full h-full object-cover group-hover:scale-110 transition duration-300" />
                            <div
                                class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition duration-300 flex items-center justify-center">
                                <i class="bi bi-zoom-in text-white text-xl opacity-0 group-hover:opacity-100"></i>
                            </div>
                        </div>
                    </div>
                    <p v-else
                        class="text-sm text-gray-400 italic bg-gray-50 p-4 rounded-lg border border-dashed text-center">
                        Không có ảnh hợp đồng nào.
                    </p>
                </div>

                <div class="space-y-3 border-t border-gray-100 pt-5">
                    <h4 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="bi bi-images text-gray-400"></i>
                        Hình ảnh không gian thực tế:
                    </h4>

                    <!-- Media Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3" v-if="boardingHouse?.room_images?.length">
                        <div v-for="(path, index) in boardingHouse.room_images" :key="'room-' + index"
                            class="relative group overflow-hidden rounded-lg border shadow-sm bg-black cursor-pointer aspect-square">
                            <!-- Video -->
                            <video v-if="isVideo(path)" :src="getPrivateImageUrl(path, 'rooms')" controls
                                class="w-full h-full object-cover">
                                Trình duyệt không hỗ trợ video.
                            </video>

                            <!-- Image -->
                            <div v-else class="w-full h-full" @click="
                                openImage(getPrivateImageUrl(path, 'rooms'))
                                ">
                                <img :src="getPrivateImageUrl(path, 'rooms')"
                                    class="w-full h-full object-cover group-hover:scale-110 transition duration-300" />

                                <div
                                    class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition duration-300 flex items-center justify-center">
                                    <i class="bi bi-zoom-in text-white text-xl opacity-0 group-hover:opacity-100"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty -->
                    <p v-else
                        class="text-sm text-gray-400 italic bg-gray-50 p-4 rounded-lg border border-dashed text-center">
                        Không có ảnh/video không gian trọ nào.
                    </p>

                    <!-- GPS Map -->
                    <div class="space-y-2 border-t pt-4 mt-4" v-if="
                        boardingHouse?.latitude && boardingHouse?.longitude
                    ">
                        <h4 class="text-sm font-bold text-green-700 flex items-center gap-2">
                            <i class="bi bi-geo-alt-fill"></i>
                            Tọa độ chụp thực tế
                        </h4>

                        <p class="text-xs text-blue-600 font-bold bg-blue-50 p-2 rounded">
                            Tọa độ ảnh chụp thực tế:
                            {{ boardingHouse.latitude }} ,
                            {{ boardingHouse.longitude }}
                        </p>

                        <div class="rounded-xl overflow-hidden border border-green-200 shadow-sm mt-2">
                            <iframe width="100%" height="250" style="border: 0" loading="lazy" :src="'https://maps.google.com/maps?q=' +
                                boardingHouse.latitude +
                                ',' +
                                boardingHouse.longitude +
                                '&hl=vi&z=16&output=embed'
                                ">
                            </iframe>
                        </div>

                        <p class="text-xs text-gray-500 italic">
                            * Tọa độ được trích xuất từ dữ liệu GPS trong ảnh
                            nhằm hỗ trợ đối chiếu tính xác thực của hình ảnh
                            đăng tải.
                        </p>
                    </div>

                    <!-- Warning -->
                    <div v-else-if="boardingHouse?.room_images?.length"
                        class="mt-4 p-3 bg-yellow-50 text-yellow-700 text-sm rounded-lg border border-yellow-200 flex items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill"></i>

                        <span>
                            Không trích xuất được tọa độ thực tế. Ảnh có thể đã
                            bị xóa dữ liệu GPS khi gửi qua Zalo/Facebook hoặc
                            chỉnh sửa.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= MODAL XEM ẢNH TÓ ================= -->
        <div v-if="showImageModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            @click.self="showImageModal = false">
            <div class="relative max-w-4xl max-h-[90vh] w-full flex items-center justify-center">
                <button @click="showImageModal = false"
                    class="absolute -top-10 right-0 text-white hover:text-gray-300 text-3xl font-bold">
                    &times;
                </button>
                <img :src="currentImageUrl" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl" />
            </div>
        </div>

        <!-- ================= MODAL TỪ CHỐI ================= -->
        <div v-if="showRejectModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="p-6">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-exclamation-triangle-fill text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-center text-gray-900 mb-2">
                        Từ chối hồ sơ
                    </h3>
                    <p class="text-sm text-center text-gray-500 mb-6">
                        Bạn đang chuẩn bị từ chối hồ sơ của
                        <span class="font-bold">{{ user?.name }}</span>. Vui lòng cung cấp lý do để gửi cho chủ trọ.
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lý do từ chối
                            <span class="text-red-500">*</span></label>
                        <textarea v-model="rejectReason" rows="3"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm p-3"
                            placeholder="Ví dụ: Ảnh CCCD bị mờ, không khớp khuôn mặt..."></textarea>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 border-t">
                    <button @click="showRejectModal = false" :disabled="isProcessing"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Hủy bỏ
                    </button>
                    <button @click="submitAction('reject')" :disabled="isProcessing"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 flex items-center gap-2">
                        <i v-if="isProcessing" class="bi bi-arrow-repeat animate-spin"></i>
                        Xác nhận Từ chối
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= MODAL PHÊ DUYỆT ================= -->
        <div v-if="showApproveModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="p-6">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-check-lg text-green-600 text-2xl font-bold"></i>
                    </div>
                    <h3 class="text-lg font-bold text-center text-gray-900 mb-2">
                        Duyệt & Cấp Quyền Chủ Trọ
                    </h3>
                    <p class="text-sm text-center text-gray-500 mb-2">
                        Bạn có chắc chắn muốn phê duyệt hồ sơ này?
                    </p>
                    <div class="bg-blue-50 p-3 rounded-lg text-sm text-blue-800 text-center mt-4">
                        Tài khoản <strong>{{ user?.name }}</strong> sẽ được cấp
                        quyền truy cập vào <strong>Landlord Dashboard</strong>.
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3 border-t">
                    <button @click="showApproveModal = false" :disabled="isProcessing"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Hủy bỏ
                    </button>
                    <button @click="submitAction('approve')" :disabled="isProcessing"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg hover:bg-green-700 flex items-center gap-2">
                        <i v-if="isProcessing" class="bi bi-arrow-repeat animate-spin"></i>
                        Xác nhận Duyệt
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= POPUP THÔNG BÁO THÀNH CÔNG THAY THẾ ALERT ================= -->
        <Teleport to="body">
            <div v-if="showSuccessPopup"
                class="fixed inset-0 bg-black/40 z-[99999] flex items-center justify-center backdrop-blur-[2px] transition-all duration-300">
                <div
                    class="bg-white rounded-2xl p-8 max-w-[320px] w-full mx-4 text-center shadow-2xl transform scale-100 transition-transform animate-[pop_0.3s_ease-out]">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full mx-auto flex items-center justify-center shadow-lg shadow-green-500/30 mb-5 relative">
                        <i class="bi bi-check2 text-white text-5xl absolute"></i>
                        <div class="absolute inset-0 rounded-full border-4 border-green-200 animate-ping opacity-20">
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2 font-headline">
                        Thành công!
                    </h3>
                    <p class="text-gray-600 font-medium text-sm">
                        {{ successMessage }}
                    </p>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
