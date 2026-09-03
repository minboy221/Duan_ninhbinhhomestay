<script setup>
import { Head, router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { showWarning } from "@/Utils/swal";

const props = defineProps({
    user: Object,
    verification: Object,
    boardingHouse: Object,
});

// Hàm chuẩn hóa danh sách ảnh (xử lý array, chuỗi json hoặc phần tử rỗng/false)
const normalizeImages = (images) => {
    if (!images) return [];
    if (typeof images === "string") {
        try {
            const parsed = JSON.parse(images);
            return normalizeImages(parsed);
        } catch (e) {
            return images.trim() && images !== "false" ? [images] : [];
        }
    }
    if (Array.isArray(images)) {
        return images
            .flatMap((item) => normalizeImages(item))
            .filter(
                (item) => item && typeof item === "string" && item !== "false",
            );
    }
    return [];
};

// Hàm lấy URL ảnh private
const getPrivateImageUrl = (path, type) => {
    if (!path || typeof path !== "string" || path === "false") {
        return "https://placehold.co/400x300?text=No+Image";
    }
    if (
        path.startsWith("http://") ||
        path.startsWith("https://") ||
        path.startsWith("blob:")
    ) {
        return path;
    }
    const filename = path.replace(/\\/g, "/").split("/").pop();
    return route("admin.files.private", { type: type, filename: filename });
};

// Phần lưu video
const isVideo = (path) => {
    if (!path || typeof path !== "string") return false;
    const ext = path.split(".").pop().toLowerCase();
    return ["mp4", "mov", "avi"].includes(ext);
};

// --- QUẢN LÝ BẢN ĐỒ VÀ TỌA ĐỘ GPS ---
const activeMapLayer = ref("satellite");
const copiedGps = ref(false);

const copyGpsCoordinates = () => {
    if (!props.boardingHouse?.latitude || !props.boardingHouse?.longitude)
        return;
    const text = `${props.boardingHouse.latitude}, ${props.boardingHouse.longitude}`;
    navigator.clipboard.writeText(text).then(() => {
        copiedGps.value = true;
        setTimeout(() => {
            copiedGps.value = false;
        }, 2000);
    });
};

const toDMS = (deg, isLat) => {
    if (deg === null || deg === undefined || isNaN(deg)) return "";
    const num = Number(deg);
    const absolute = Math.abs(num);
    const degrees = Math.floor(absolute);
    const minutesNotTruncated = (absolute - degrees) * 60;
    const minutes = Math.floor(minutesNotTruncated);
    const seconds = ((minutesNotTruncated - minutes) * 60).toFixed(1);
    const direction = isLat ? (num >= 0 ? "N" : "S") : num >= 0 ? "E" : "W";
    return `${degrees}°${minutes}'${seconds}"${direction}`;
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
        showWarning("Thiếu lý do", "Vui lòng nhập lý do từ chối!");
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

            <div class="flex items-center gap-3">
                <button @click="showRejectModal = true"
                    class="px-5 py-2.5 rounded-xl bg-red-50 text-red-600 font-bold hover:bg-red-100 transition-all flex items-center gap-1.5 shadow-sm">
                    <i class="bi bi-x-circle-fill text-lg"></i>
                    Từ chối hồ sơ
                </button>
                <button @click="showApproveModal = true"
                    class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition-all flex items-center gap-1.5 shadow-lg shadow-emerald-600/20">
                    <i class="bi bi-check-circle-fill text-lg"></i>
                    Phê duyệt hồ sơ
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
                    <div class="grid grid-cols-3 gap-3" v-if="
                        normalizeImages(boardingHouse?.contract_images)
                            .length
                    ">
                        <div v-for="(path, index) in normalizeImages(
                            boardingHouse.contract_images,
                        )" :key="'contract-' + index"
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
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3" v-if="
                        normalizeImages(boardingHouse?.room_images).length
                    ">
                        <div v-for="(path, index) in normalizeImages(
                            boardingHouse.room_images,
                        )" :key="'room-' + index"
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

                    <!-- GPS Map Section -->
                    <div class="space-y-3 border-t pt-4 mt-4" v-if="
                        Number(boardingHouse?.latitude) &&
                        Number(boardingHouse?.longitude)
                    ">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-bold text-emerald-700 flex items-center gap-2">
                                <i class="bi bi-geo-alt-fill text-emerald-600"></i>
                                Tọa độ định vị chính xác từ ảnh
                            </h4>
                            <span
                                class="text-[11px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full border border-emerald-300">
                                Độ chính xác cao
                            </span>
                        </div>

                        <!-- Coordinate Details Box -->
                        <div class="bg-emerald-50/80 p-3 rounded-xl border border-emerald-200 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <div class="space-y-0.5">
                                    <p class="font-bold text-emerald-950">
                                        Thập phân:
                                        <span class="font-mono text-emerald-700 font-bold">{{
                                            Number(
                                                boardingHouse.latitude,
                                            ).toFixed(7)
                                        }},
                                            {{
                                                Number(
                                                    boardingHouse.longitude,
                                                ).toFixed(7)
                                            }}</span>
                                    </p>
                                    <p class="text-emerald-800 text-[11px] font-medium">
                                        DMS:
                                        {{
                                            toDMS(boardingHouse.latitude, true)
                                        }}
                                        ,
                                        {{
                                            toDMS(
                                                boardingHouse.longitude,
                                                false,
                                            )
                                        }}
                                    </p>
                                </div>
                                <button type="button" @click="copyGpsCoordinates"
                                    class="px-2.5 py-1.5 bg-white hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-semibold shadow-xs transition flex items-center gap-1">
                                    <i class="bi" :class="copiedGps
                                            ? 'bi-check-lg text-emerald-600'
                                            : 'bi-clipboard'
                                        "></i>
                                    {{ copiedGps ? "Đã sao chép" : "Sao chép" }}
                                </button>
                            </div>

                            <!-- Map Layer Switcher Tabs -->
                            <div class="flex items-center gap-1.5 pt-1 border-t border-emerald-200/60 text-xs">
                                <span class="text-gray-500 text-[11px] mr-1">Chế độ xem:</span>
                                <button type="button" @click="activeMapLayer = 'satellite'" :class="activeMapLayer === 'satellite'
                                        ? 'bg-emerald-700 text-white font-bold'
                                        : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                                    " class="px-2 py-1 rounded-md text-[11px] transition shadow-xs">
                                    Vệ tinh HD (Google)
                                </button>
                                <button type="button" @click="activeMapLayer = 'google'" :class="activeMapLayer === 'google'
                                        ? 'bg-emerald-700 text-white font-bold'
                                        : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
                                    " class="px-2 py-1 rounded-md text-[11px] transition shadow-xs">
                                    Bản đồ Google
                                </button>
                            </div>
                        </div>

                        <!-- Map Frame -->
                        <div class="rounded-xl overflow-hidden border border-emerald-200 shadow-sm relative h-64 bg-gray-100">
                            <!-- Google Maps Satellite HD -->
                            <iframe v-if="activeMapLayer === 'satellite'" width="100%" height="100%"
                                style="border: 0" loading="lazy" :src="'https://maps.google.com/maps?q=' +
                                    boardingHouse.latitude +
                                    ',' +
                                    boardingHouse.longitude +
                                    '&t=k&z=19&output=embed'
                                    ">
                            </iframe>

                            <!-- Google Maps Standard -->
                            <iframe v-else width="100%" height="100%" style="border: 0" loading="lazy" :src="'https://maps.google.com/maps?q=' +
                                boardingHouse.latitude +
                                ',' +
                                boardingHouse.longitude +
                                '&z=18&output=embed'
                                ">
                            </iframe>
                        </div>

                        <!-- Action External Links -->
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <a :href="'https://www.google.com/maps?q=' +
                                boardingHouse.latitude +
                                ',' +
                                boardingHouse.longitude
                                " target="_blank"
                                class="text-xs bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-2xs transition">
                                <i class="bi bi-box-arrow-up-right text-emerald-600"></i>
                                Mở Google Maps
                            </a>
                            <a :href="'https://www.google.com/maps/dir/?api=1&destination=' +
                                boardingHouse.latitude +
                                ',' +
                                boardingHouse.longitude
                                " target="_blank"
                                class="text-xs bg-white hover:bg-blue-50 text-blue-800 border border-blue-200 font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-2xs transition">
                                <i class="bi bi-compass text-blue-600"></i> Chỉ
                                đường tới đây
                            </a>
                            <a :href="'https://www.google.com/maps/@' +
                                boardingHouse.latitude +
                                ',' +
                                boardingHouse.longitude +
                                ',19z/data=!3m1!1e3'
                                " target="_blank"
                                class="text-xs bg-white hover:bg-purple-50 text-purple-800 border border-purple-200 font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-2xs transition">
                                <i class="bi bi-globe-americas text-purple-600"></i>
                                Xem vệ tinh 3D
                            </a>
                        </div>
                    </div>

                    <!-- Warning & Address-based Fallback Map -->
                    <div v-else class="space-y-3 border-t pt-4 mt-4">
                        <div
                            class="p-3 bg-amber-50 text-amber-800 text-xs rounded-lg border border-amber-200 flex items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-amber-600 text-base mt-0.5"></i>
                            <div>
                                <p class="font-bold">
                                    Không trích xuất được tọa độ GPS từ ảnh
                                    chụp.
                                </p>
                                <p class="text-amber-700 mt-0.5">
                                    Ảnh tải lên có thể đã bị xóa dữ liệu
                                    EXIF/GPS khi gửi qua Zalo/Facebook, chụp màn
                                    hình hoặc chỉnh sửa.
                                </p>
                            </div>
                        </div>

                        <!-- Fallback Map from Address -->
                        <div v-if="
                            boardingHouse?.address_detail ||
                            boardingHouse?.district
                        " class="space-y-2">
                            <h4 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                <i class="bi bi-map-fill text-blue-600"></i>
                                Vị trí tham khảo theo địa chỉ khai báo:
                            </h4>
                            <p class="text-xs text-gray-600 font-medium">
                                {{
                                    [
                                        boardingHouse?.address_detail,
                                        boardingHouse?.district,
                                        "Ninh Bình",
                                    ]
                                        .filter(Boolean)
                                        .join(", ")
                                }}
                            </p>
                            <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm">
                                <iframe width="100%" height="220" style="border: 0" loading="lazy" :src="'https://maps.google.com/maps?q=' +
                                    encodeURIComponent(
                                        [
                                            boardingHouse?.address_detail,
                                            boardingHouse?.district,
                                            'Ninh Bình',
                                        ]
                                            .filter(Boolean)
                                            .join(', '),
                                    ) +
                                    '&hl=vi&z=15&output=embed'
                                    ">
                                </iframe>
                            </div>
                        </div>
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
