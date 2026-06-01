<script setup>
import { router } from "@inertiajs/vue3";
import { ref } from "vue";

// Nhận dữ liệu từ AdminVerificationController truyền sang
const props = defineProps({
    user: Object,
    verification: Object,
    boardingHouse: Object,
});

console.log("Dữ liệu thực tế Vue nhận được từ Laravel:", props);

// Hàm quan trọng: Cắt chuỗi đường dẫn từ DB để lấy Tên File và gọi qua Route xem ảnh Private
const getPrivateImageUrl = (path, type) => {
    if (!path) return "https://placehold.co/400x300?text=No+Image";

    // ĐÃ CHỈNH SỬA: Chuyển toàn bộ dấu \ thành / trước khi cắt chuỗi lấy tên file
    const filename = path.replace(/\\/g, "/").split("/").pop();

    return route("admin.files.private", { type: type, filename: filename });
};

// Hàm xử lý nút Duyệt / Từ chối
const updateStatus = (action) => {
    let reason = "";

    if (action === "reject") {
        reason = prompt(
            "Vui lòng nhập lý do từ chối hồ sơ này (Thông báo này sẽ gửi tới chủ trọ):",
        );
        if (reason === null) return; // Bấm Cancel thì hủy thao tác
        if (reason.trim() === "") {
            alert("Bạn phải nhập lý do từ chối!");
            return;
        }
    } else {
        if (
            !confirm(
                "Bạn có chắc chắn muốn DUYỆT và CẤP QUYỀN Chủ trọ cho tài khoản này?",
            )
        )
            return;
    }

    // Gửi request lên Controller của Admin
    router.post(
        route("admin.verifications.update-status", props.user.id),
        {
            action: action,
            reason: reason,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                alert(
                    action === "approve"
                        ? "Đã phê duyệt chủ trọ thành công!"
                        : "Đã từ chối hồ sơ.",
                );
            },
        },
    );
};
</script>

<template>
    <div class="max-w-7xl mx-auto p-6 bg-gray-50 min-h-screen">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b pb-4 mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    Chi tiết Hồ sơ Phê duyệt
                </h2>
                <p class="text-sm text-gray-500">
                    Đối chiếu thông tin đăng ký hệ thống Ninh Bình StayWork
                </p>
            </div>

            <div class="flex space-x-3 w-full md:w-auto justify-end">
                <button @click="updateStatus('reject')"
                    class="bg-red-500 hover:bg-red-600 text-white px-5 py-2.5 rounded-lg font-semibold shadow transition duration-200">
                    Từ chối Hồ sơ
                </button>
                <button @click="updateStatus('approve')"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg font-semibold shadow transition duration-200">
                    Duyệt Cấp Quyền
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-6">
                <h3 class="text-lg font-bold text-blue-700 flex items-center border-b pb-2">
                    <span class="w-2 h-5 bg-blue-700 rounded mr-2 inline-block"></span>
                    1. Xác minh Danh tính (KYC)
                </h3>

                <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg text-sm">
                    <div>
                        <span class="text-gray-500 block text-xs uppercase">Họ và tên</span>
                        <span class="font-bold text-gray-800 text-base">{{
                            user?.name
                            }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs uppercase">Số điện thoại</span>
                        <span class="font-bold text-gray-800 text-base text-blue-600">{{ user?.phone || "Chưa có"
                            }}</span>
                    </div>
                    <div class="col-span-2 border-t pt-2 mt-1">
                        <span class="text-gray-500 block text-xs uppercase">Địa chỉ Email</span>
                        <span class="text-gray-700 font-medium">{{
                            user?.email
                            }}</span>
                    </div>
                    <div class="col-span-2 border-t pt-2 mt-1">
                        <span class="text-gray-500 block text-xs uppercase">Số CCCD / Định danh</span>
                        <span class="font-bold text-gray-800 text-base tracking-wider">{{
                            verification?.id_card_number || "Chưa cập nhật"
                        }}</span>
                    </div>
                </div>

                <div class="p-4 rounded-lg flex items-center justify-between" :class="verification?.kyc_status === 'approved'
                        ? 'bg-green-50 border border-green-200'
                        : 'bg-red-50 border border-red-200'
                    ">
                    <div>
                        <div class="font-bold" :class="verification?.kyc_status === 'approved'
                                ? 'text-green-800'
                                : 'text-red-800'
                            ">
                            Kết quả đối sánh khuôn mặt từ AI
                        </div>
                        <p class="text-xs text-gray-600 mt-0.5">
                            Hệ thống Face-API quét tự động ở Bước 3
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider" :class="verification?.kyc_status === 'approved'
                            ? 'bg-green-200 text-green-800'
                            : 'bg-red-200 text-red-800'
                        ">
                        {{
                            verification?.kyc_status === "approved"
                                ? "Khớp 100%"
                                : "Không khớp"
                        }}
                    </span>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <span class="text-xs font-semibold text-gray-500 block mb-1">CCCD MẶT TRƯỚC</span>
                            <a :href="getPrivateImageUrl(
                                verification?.id_card_front,
                                'id_cards',
                            )
                                " target="_blank" title="Bấm để xem ảnh lớn">
                                <img :src="getPrivateImageUrl(
                                    verification?.id_card_front,
                                    'id_cards',
                                )
                                    "
                                    class="w-full h-40 object-cover rounded-lg border shadow-sm hover:opacity-90 transition" />
                            </a>
                        </div>
                        <div class="text-center">
                            <span class="text-xs font-semibold text-gray-500 block mb-1">CCCD MẶT SAU</span>
                            <a :href="getPrivateImageUrl(
                                verification?.id_card_back,
                                'id_cards',
                            )
                                " target="_blank" title="Bấm để xem ảnh lớn">
                                <img :src="getPrivateImageUrl(
                                    verification?.id_card_back,
                                    'id_cards',
                                )
                                    "
                                    class="w-full h-40 object-cover rounded-lg border shadow-sm hover:opacity-90 transition" />
                            </a>
                        </div>
                    </div>
                    <div class="text-center border-t pt-4">
                        <span class="text-xs font-semibold text-gray-500 block mb-1">ẢNH CHỤP CAMERA THỰC TẾ (BƯỚC
                            3)</span>
                        <img :src="getPrivateImageUrl(
                            verification?.face_auth_image,
                            'faces',
                        )
                            "
                            class="w-40 h-40 object-cover rounded-full border-4 border-gray-200 shadow-md mx-auto hover:scale-105 transition" />
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-6">
                <h3 class="text-lg font-bold text-blue-700 flex items-center border-b pb-2">
                    <span class="w-2 h-5 bg-blue-700 rounded mr-2 inline-block"></span>
                    2. Hồ sơ Cơ sở Kinh doanh Trọ
                </h3>

                <div class="space-y-3 bg-gray-50 p-4 rounded-lg text-sm">
                    <div>
                        <span class="text-gray-500 block text-xs uppercase">Tên cơ sở trọ / Homestay</span>
                        <span class="font-bold text-gray-800 text-base">{{
                            boardingHouse?.name || "Chưa cập nhật"
                            }}</span>
                    </div>
                    <div class="border-t pt-2">
                        <span class="text-gray-500 block text-xs uppercase">Khu vực hành chính (Quận/Huyện)</span>
                        <span class="text-gray-700 font-medium">{{
                            boardingHouse?.district || "Chưa cập nhật"
                            }}</span>
                    </div>
                    <div class="border-t pt-2">
                        <span class="text-gray-500 block text-xs uppercase">Địa chỉ chi tiết</span>
                        <span class="text-gray-700 font-medium">{{
                            boardingHouse?.address_detail || "Chưa cập nhật"
                            }}</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <h4 class="text-sm font-bold text-gray-700 flex items-center">
                        📁 Giấy tờ pháp lý / Ảnh hợp đồng mẫu:
                    </h4>
                    <div class="grid grid-cols-3 gap-2" v-if="boardingHouse?.contract_images?.length">
                        <div v-for="(
path, index
                            ) in boardingHouse.contract_images" :key="'contract-' + index"
                            class="relative group overflow-hidden rounded-lg border">
                            <img :src="getPrivateImageUrl(path, 'contracts')"
                                class="w-full h-24 object-cover group-hover:scale-110 transition duration-200 cursor-pointer" />
                        </div>
                    </div>
                    <p v-else class="text-xs text-gray-400 italic">
                        Không có ảnh hợp đồng nào được tải lên.
                    </p>
                </div>

                <div class="space-y-2 border-t pt-4">
                    <h4 class="text-sm font-bold text-gray-700 flex items-center">
                        🖼️ Hình ảnh không gian trọ thực tế:
                    </h4>
                    <div class="grid grid-cols-3 gap-2" v-if="boardingHouse?.room_images?.length">
                        <div v-for="(path, index) in boardingHouse.room_images" :key="'room-' + index"
                            class="relative group overflow-hidden rounded-lg border">
                            <img :src="getPrivateImageUrl(path, 'rooms')"
                                class="w-full h-24 object-cover group-hover:scale-110 transition duration-200 cursor-pointer" />
                        </div>
                    </div>
                    <p v-else class="text-xs text-gray-400 italic">
                        Không có ảnh không gian trọ nào được tải lên.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
