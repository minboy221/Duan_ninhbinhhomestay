<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    post: Object,
});

// Sử dụng useForm quản lý lý do từ chối gửi lên server
const rejectForm = useForm({
    reject_reason: props.post.reject_reason || "",
});

//Phần hiển thị trạng thái của phòng mà chủ trọ đăng tin
const getStatusLabel = (status) => {
    const labels = {
        available: "Còn phòng",
        rented: "Đã thuê",
        maintenance: "Bảo trì",
        deposited: "Đã đặt cọc",
        expiring_soon: "Sắp hết hạn hợp đồng",
        pending_renewal: "Chờ gia hạn",
        suspended: "Tạm ngưng",
        under_construction: "Đang xây dựng",
    };
    return labels[status] || "Không xác định";
};

const getStatusClass = (status) => {
    const classes = {
        available: "bg-green-50 text-green-700 border-green-200",
        rented: "bg-gray-50 text-gray-500 border-gray-200",
        maintenance: "bg-yellow-50 text-yellow-700 border-yellow-200",
        deposited: "bg-blue-50 text-blue-700 border-blue-200",
        expiring_soon: "bg-orange-50 text-orange-700 border-orange-200",
        pending_renewal: "bg-purple-50 text-purple-700 border-purple-200",
        suspended: "bg-red-50 text-red-700 border-red-200",
        under_construction: "bg-teal-50 text-teal-700 border-teal-200",
    };
    return classes[status] || "bg-gray-50 text-gray-500 border-gray-200";
};

// Hàm gọi lệnh Phê duyệt bài viết
function approvePost() {
    if (
        confirm(`Bạn có chắc chắn muốn phê duyệt xuất bản bài đăng này không?`)
    ) {
        router.post(route("admin.listings.approve", props.post.id));
    }
}

// Hàm gọi lệnh Từ chối bài viết kèm lý do
function rejectPost() {
    rejectForm.post(route("admin.listings.reject", props.post.id));
}

const formatDate = (dateStr) =>
    dateStr ? new Date(dateStr).toLocaleDateString("vi-VN") : "—";
const formatMoney = (n) =>
    n ? new Intl.NumberFormat("vi-VN").format(n) + "đ" : "—";
</script>

<template>

    <Head :title="'Kiểm duyệt bài viết #' + post.id" />
    <AdminLayout>
        <template #header-title>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.listings.index')"
                    class="px-3 py-2 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 text-gray-700 text-xs font-bold transition-colors flex items-center gap-1 shadow-sm">
                    📂 Quay lại danh sách
                </Link>
                <div>
                    <h1 class="page-title">Chi Tiết Kiểm Duyệt Bài Viết</h1>
                    <p class="page-sub">
                        Mã tin đăng hệ thống:
                        <span class="font-bold text-gray-700">#{{ post.id }}</span>
                    </p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl mx-auto p-4">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3 flex items-center gap-1.5">
                        <i class="bi bi-images text-sm"></i> Album ảnh thực tế
                        căn phòng:
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" v-if="post.image && post.image.length > 0">
                        <img v-for="(img, idx) in post.image" :key="idx" :src="img"
                            class="w-full aspect-[4/3] object-cover rounded-xl border hover:opacity-90 transition-opacity shadow-sm"
                            alt="Ảnh chụp thực tế phòng trọ Ninh Bình" />
                    </div>
                    <div v-else
                        class="py-12 bg-gray-50 text-center rounded-xl text-gray-400 text-xs italic border border-dashed">
                        Chủ trọ chưa cập nhật hoặc không đính kèm hình ảnh cho
                        bài viết này.
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm space-y-5">
                    <div>
                        <span
                            class="px-2.5 py-1 bg-violet-50 text-violet-600 font-bold text-[10px] uppercase rounded-md border border-violet-100">
                            {{ post.room?.room_type || "Phòng trọ" }}
                        </span>
                        <h2 class="text-xl font-black text-gray-900 mt-2.5 leading-snug">
                            {{ post.title }}
                        </h2>
                    </div>
                    <div class="text-xs text-gray-700 bg-blue-50/40 border border-blue-100 p-3 rounded-xl">
                        <span class="font-bold block text-blue-900 mb-0.5">📍 Địa chỉ thực tế bài đăng đăng ký:</span>
                        {{
                            post.address ||
                            post.room?.boarding_house?.address_detail ||
                            "Chủ trọ chưa thiết lập định vị địa chỉ."
                        }}
                    </div>
                    <div class="mt-4">
                        <span class="font-bold block text-gray-900 text-xs mb-2">🗺️ Bản đồ vị trí khu trọ:</span>
                        <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm" style="height: 250px">
                            <!-- Trường hợp 1: Có tọa độ GPS chính xác (Kinh độ & Vĩ độ) từ tin đăng -->
                            <iframe v-if="post.latitude && post.longitude"
                                :src="'https://maps.google.com/maps?q=' + post.latitude + ',' + post.longitude + '&hl=vi&z=16&output=embed'"
                                width="100%" height="100%" style="border: 0" loading="lazy">
                            </iframe>
                            <!-- Trường hợp 2: Có địa chỉ text từ tin đăng -->
                            <iframe v-else-if="post.address"
                                :src="'https://maps.google.com/maps?q=' + encodeURIComponent(post.address) + '&hl=vi&z=16&output=embed'"
                                width="100%" height="100%" style="border: 0" loading="lazy">
                            </iframe>
                            <!-- Trường hợp 3: Fallback qua boarding house GPS -->
                            <iframe v-else-if="
                                post.room?.boarding_house &&
                                post.room.boarding_house.latitude &&
                                post.room.boarding_house.longitude
                            " :src="'https://maps.google.com/maps?q=' +
                                    post.room.boarding_house.latitude +
                                    ',' +
                                    post.room.boarding_house.longitude +
                                    '&hl=vi&z=16&output=embed'
                                    " width="100%" height="100%" style="border: 0" loading="lazy">
                            </iframe>
                            <!-- Trường hợp 4: Fallback qua boarding house Address -->
                            <iframe v-else-if="
                                post.room?.boarding_house &&
                                post.room.boarding_house.address_detail
                            " :src="'https://maps.google.com/maps?q=' +
                                    encodeURIComponent(
                                        post.room.boarding_house.address_detail,
                                    ) +
                                    '&hl=vi&z=16&output=embed'
                                    " width="100%" height="100%" style="border: 0" loading="lazy">
                            </iframe>
                            <!-- Trường hợp 5: Trống thông tin -->
                            <div v-else
                                class="p-6 text-center text-gray-400 text-xs bg-gray-50 flex items-center justify-center h-full">
                                Chưa có tọa độ hoặc địa chỉ cụ thể để hiển thị
                                bản đồ.
                            </div>
                        </div>
                    </div>

                    <!-- THÊM DƯỚI ĐÂY -->
                    <div v-if="
                        post.room?.services && post.room.services.length > 0
                    " class="p-4 bg-gray-50 border border-gray-200 rounded-xl space-y-2">
                        <span class="font-bold block text-gray-900 text-xs">⚡ Các dịch vụ & Tiện ích đi kèm:</span>
                        <div class="grid grid-cols-2 gap-3">
                            <div v-for="service in post.room.services" :key="service.id"
                                class="flex items-center gap-2 text-xs text-gray-600 bg-white p-2 rounded-lg border border-gray-100">
                                <i :class="[
                                    'bi',
                                    service.icon || 'bi-check-circle-fill',
                                ]" :style="'color:' + (service.color || '#3b82f6')
                                        "></i>
                                <span>{{ service.name }}:
                                    <strong class="text-red-500">{{
                                        formatMoney(service.price)
                                        }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-4 text-xs py-4 border-t border-b text-gray-600 bg-gray-50/50 px-4 rounded-xl">
                        <div>
                            💰 Giá thuê phòng:
                            <span class="font-extrabold text-red-600 text-sm">{{
                                formatMoney(post.room?.price)
                                }}</span>/tháng
                        </div>
                        <div>
                            📐 Diện tích sử dụng:
                            <span class="font-bold text-gray-900">{{ post.room?.area || "—" }} m²</span>
                        </div>
                        <div>
                            🔢 Số phòng quản lý:
                            <span class="font-bold text-gray-900">Phòng {{ post.room?.room_number }}</span>
                        </div>
                        <div>
                            📅 Thời gian gửi bài:
                            <span class="font-bold text-gray-900">{{
                                formatDate(post.created_at)
                                }}</span>
                        </div>
                        <div>
                            🏠 Tên khu trọ:
                            <span class="font-bold text-gray-900">{{
                                post.room?.boarding_house?.name || "—"
                                }}</span>
                        </div>
                        <div>
                            👥 Sức chứa tối đa:
                            <span class="font-bold text-gray-900">{{ post.room?.capacity || "2" }} người</span>
                        </div>
                        <div>
                            🏢 Tầng quản lý:
                            <span class="font-bold text-gray-900">{{
                                post.room?.floor?.name || "—"
                                }}</span>
                        </div>
                        <div>
                            ⚡ Trạng thái phòng:
                            <span :class="[
                                'px-2 py-0.5 rounded-md border text-[10px] font-bold',
                                getStatusClass(post.room?.status),
                            ]">
                                {{ getStatusLabel(post.room?.status) }}
                            </span>
                        </div>
                    </div>

                    <div class="text-xs text-gray-700 bg-blue-50/40 border border-blue-100 p-3 rounded-xl">
                        <span class="font-bold block text-blue-900 mb-0.5">📍 Địa chỉ thực tế bài đăng đăng ký:</span>
                        {{
                            post.room?.boarding_house?.address_detail ||
                            "Chủ trọ chưa thiết lập định vị địa chỉ."
                        }}
                    </div>

                    <div>
                        <h4
                            class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1">
                            <i class="bi bi-file-text"></i> Nội dung mô tả chi
                            tiết từ chủ trọ:
                        </h4>
                        <div v-html="post.description"
                            class="p-4 bg-white border border-gray-100 rounded-xl text-xs text-gray-700 leading-relaxed max-h-96 overflow-y-auto detail-content-view shadow-inner">
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm text-xs">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3 flex items-center gap-1">
                        <i class="bi bi-person-badge"></i> Thông tin người đăng
                        tin:
                    </h3>
                    <div class="space-y-2.5 pt-1">
                        <div class="text-sm font-black text-gray-900">
                            {{ post.landlord?.name }}
                        </div>
                        <div class="text-gray-500">
                            📧 Email:
                            <span class="text-gray-800 font-medium">{{
                                post.landlord?.email
                                }}</span>
                        </div>
                        <div class="text-gray-500">
                            📞 Điện thoại:
                            <span class="text-gray-800 font-bold">{{
                                post.landlord?.phone || "Chưa cập nhật"
                                }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm" v-if="post.status === 'pending'">
                    <h3 class="text-xs font-black text-gray-900 mb-4 text-center uppercase tracking-wider">
                        Hộp tác vụ duyệt tin
                    </h3>

                    <button @click="approvePost"
                        class="w-full py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold text-xs shadow-md transition-colors mb-4 flex items-center justify-center gap-1">
                        <i class="bi bi-check-circle-fill"></i> PHÊ DUYỆT & XUẤT
                        BẢN TIN
                    </button>

                    <div class="border-t border-gray-100 pt-4">
                        <label class="block text-xs font-bold text-red-600 mb-1.5 flex items-center gap-1">
                            <i class="bi bi-patch-exclamation-fill"></i> Lý do
                            từ chối xuất bản bài (Bắt buộc):
                        </label>
                        <textarea v-model="rejectForm.reject_reason" rows="4"
                            placeholder="Ví dụ: Ảnh chụp phòng bị mờ, thông tin giá cả chưa khớp thực tế, tiêu đề cần viết hoa lịch sự..."
                            class="w-full text-xs rounded-xl border-gray-300 focus:ring-red-500 focus:border-red-500 placeholder:text-gray-300 shadow-sm"></textarea>

                        <p v-if="rejectForm.errors.reject_reason"
                            class="text-red-500 text-[11px] mt-1 font-semibold flex items-center gap-1">
                            <span>⚠️</span>
                            {{ rejectForm.errors.reject_reason }}
                        </p>

                        <button @click="rejectPost" :disabled="rejectForm.processing"
                            class="w-full mt-2.5 py-2.5 bg-red-50 border border-red-200 text-red-600 hover:bg-red-100 font-bold text-xs rounded-xl transition-colors disabled:opacity-50 flex items-center justify-center gap-1">
                            <i class="bi bi-x-circle-fill"></i> TỪ CHỐI DUYỆT
                            TIN ĐĂNG
                        </button>
                    </div>
                </div>

                <div class="p-5 rounded-2xl text-center font-bold text-xs border shadow-sm space-y-2" :class="post.status === 'approved'
                        ? 'bg-green-50 border-green-200 text-green-700'
                        : 'bg-red-50 border-red-200 text-red-700'
                    " v-else>
                    <div class="text-sm">
                        {{
                            post.status === "approved"
                                ? "🎉 Bài đăng đã được duyệt"
                                : "❌ Bài đăng đã bị hủy"
                        }}
                    </div>
                    <p class="text-xs font-normal text-gray-500">
                        Xử lý vào lúc: {{ formatDate(post.updated_at) }}
                    </p>

                    <div class="text-left bg-white p-3 border border-red-100 rounded-xl mt-3"
                        v-if="post.status === 'rejected' && post.reject_reason">
                        <span class="block text-[10px] font-bold text-red-700 uppercase tracking-wider mb-0.5">Lý do đã
                            từ
                            chối:</span>
                        <p class="text-[11px] text-red-600 font-normal italic">
                            "{{ post.reject_reason }}"
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style>
/* CSS bổ trợ giúp thẻ v-html không bị mất dấu chấm tròn của danh sách tiện ích */
.detail-content-view ul {
    list-style-type: disc;
    padding-left: 1.3rem;
    margin-bottom: 0.4rem;
}

.detail-content-view ol {
    list-style-type: decimal;
    padding-left: 1.3rem;
    margin-bottom: 0.4rem;
}
</style>
