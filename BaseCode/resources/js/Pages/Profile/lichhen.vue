<script setup>
import UserLayout from "@/Layouts/UserLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    user: { type: Object, required: true },
    appointments: { type: Array, default: () => [] },
    favoriteRoomIds: { type: Array, default: () => [] },
});

const favoritedRoomIds = ref([...props.favoriteRoomIds]);
import { router } from "@inertiajs/vue3";

const isRoomFavorited = (roomId) => {
    return favoritedRoomIds.value.includes(roomId);
};

const statusMap = {
    pending: {
        label: "Chờ Duyệt",
        cls: "bg-amber-50 text-amber-600 border-amber-100",
        dot: "bg-amber-500",
    },
    approved: {
        label: "Đã Duyệt",
        cls: "bg-emerald-50 text-emerald-600 border-emerald-100",
        dot: "bg-emerald-500",
    },
    waiting_contract: {
        label: "Chờ hợp đồng",
        cls: "bg-blue-50 text-blue-600 border-blue-100",
        dot: "bg-blue-500",
    },
    rejected: {
        label: "Từ Chối",
        cls: "bg-slate-50 text-slate-500 border-slate-100",
        dot: "bg-slate-500",
    },
    expired: {
        label: "Quá giờ hẹn",
        cls: "bg-rose-50 text-rose-500 border-rose-100",
        dot: "bg-rose-500",
    },
    success_matched: {
        label: "Đã thuê trọ",
        cls: "bg-teal-50 text-teal-600 border-teal-100",
        dot: "bg-teal-500",
    },
    false_matched: {
        label: "Không thuê",
        cls: "bg-gray-50 text-gray-500 border-gray-100",
        dot: "bg-gray-400",
    },
};

//hàm lấy trạng thái an toàn, nếu gặp trạng thái lạ sẽ tự động hiển thị trạng thái mặc định
const getStatusData = (status) => {
    return (
        statusMap[status] || {
            label: status || "Không rõ",
            cls: "bg-slate-50 text-slate-500 border-slate-100",
            dot: "bg-slate-500",
        }
    );
};

//trạng thái hiển thị modal chỉ dãn
const activeGuideAppointment = ref(null);
//mở hướng dẫn xem phòng
function showGuide(appointment) {
    activeGuideAppointment.value = appointment;
}

//tạo link google map dẫn đến vị trí cơ sở trọ
function getGoogleMapsSearchUrl(appointment) {
    const bh =
        appointment.room?.boardingHouse || appointment.room?.boarding_house;
    const address = bh?.address_detail || appointment.room?.address || "";
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
}

const isToday = (dateStr) => {
    const today = new Date().toISOString().split("T")[0];
    return dateStr === today;
};

import { usePage } from "@inertiajs/vue3";
import { showSuccess, showError } from "@/Utils/swal";

const page = usePage();

// State cho Modal xác nhận Ưng / Không ưng
const showConfirmModal = ref(false);
const confirmAction = ref("interested"); // 'interested' hoặc 'not_interested'
const confirmApt = ref(null);
const tenantCccd = ref("");

function openConfirmInterest(apt, isInterested) {
    confirmApt.value = apt;
    confirmAction.value = isInterested ? "interested" : "not_interested";
    tenantCccd.value = page.props.auth?.user?.cccd_number || "";
    showConfirmModal.value = true;
}

function closeConfirmModal() {
    showConfirmModal.value = false;
    confirmApt.value = null;
}

function executeInterest() {
    if (!confirmApt.value) return;

    router.post(
        route("appointments.interest", confirmApt.value.id),
        {
            result: confirmAction.value,
            cccd: tenantCccd.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeConfirmModal();
                showSuccess("Thành công", "Đã ghi nhận lựa chọn của bạn!");
            },
            onError: () => {
                showError("Lỗi", "Không thể thực hiện thao tác. Vui lòng thử lại!");
            },
        }
    );
}

// State cho Modal Hủy Hợp Đồng / Đổi ý
const showCancelModal = ref(false);
const cancelApt = ref(null);
const cancelReason = ref("");

function openCancelInterestModal(apt) {
    cancelApt.value = apt;
    cancelReason.value = "";
    showCancelModal.value = true;
}

function closeCancelModal() {
    showCancelModal.value = false;
    cancelApt.value = null;
    cancelReason.value = "";
}

function executeCancelInterest() {
    if (!cancelApt.value) return;
    if (!cancelReason.value || !cancelReason.value.trim()) {
        showWarning("Thiếu lý do", "Vui lòng nhập lý do muốn hủy hợp đồng / hủy đăng ký!");
        return;
    }

    router.post(
        route("appointments.cancel_interest", cancelApt.value.id),
        {
            reason: cancelReason.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeCancelModal();
                showSuccess("Đã gửi yêu cầu", "Yêu cầu hủy đăng ký hợp đồng đã được gửi đến Chủ trọ phê duyệt!");
            },
            onError: () => {
                showError("Lỗi", "Không thể gửi yêu cầu hủy. Vui lòng thử lại!");
            },
        }
    );
}

// Cấu hình phân trang phía Client
const currentPage = ref(1);
const pageSize = ref(7);

const totalPages = computed(() => {
    return Math.ceil(props.appointments.length / pageSize.value);
});

const paginatedAppointments = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    const end = start + pageSize.value;
    return props.appointments.slice(start, end);
});
</script>

<template>

    <Head title="Lịch Hẹn Xem Phòng | Ninh Bình HomeStay" />
    <UserLayout>
        <div class="bao_item">
            <div class="infor_noidung">
                <div class="title_noio">
                    <h2>LỊCH HẸN XEM PHÒNG</h2>
                    <p class="text-xs text-slate-400">
                        Danh sách lịch hẹn xem phòng của bạn với các chủ trọ
                    </p>
                </div>

                <!-- Appointments Table -->
                <div class="table-container" style="margin-top: 20px; overflow-x: auto">
                    <table class="lichhen-table" style="
                            width: 100%;
                            border-collapse: collapse;
                            text-align: left;
                            font-size: 13px;
                        ">
                        <thead>
                            <tr style="
                                    background-color: #f8fafc;
                                    border-bottom: 2px solid #e2e8f0;
                                    color: #475569;
                                ">
                                <th style="padding: 12px 16px">Phòng đặt</th>
                                <th style="padding: 12px 16px">Thời gian</th>
                                <th style="padding: 12px 16px">Chủ trọ</th>
                                <th style="padding: 12px 16px">Trạng thái</th>
                                <th style="
                                        padding: 12px 16px;
                                        text-align: center;
                                    ">
                                    Hành động
                                </th>
                            </tr>
                        </thead>
                        <tbody style="color: #334155">
                            <tr v-if="appointments.length === 0">
                                <td colspan="5" style="
                                        padding: 32px;
                                        text-align: center;
                                        color: #94a3b8;
                                    ">
                                    <i class="bi bi-calendar-x" style="
                                            font-size: 32px;
                                            display: block;
                                            margin-bottom: 8px;
                                        "></i>
                                    Bạn chưa có lịch hẹn xem phòng nào.
                                </td>
                            </tr>
                            <tr v-for="apt in paginatedAppointments" :key="apt.id"
                                style="border-bottom: 1px solid #f1f5f9">
                                <td style="padding: 12px 16px">
                                    <div style="font-weight: 600; color: #1e293b">
                                        Phòng {{ apt.room?.room_number }}
                                    </div>
                                    <div style="
                                            font-size: 11.5px;
                                            color: #64748b;
                                        ">
                                        {{
                                            apt.room?.boardingHouse?.name ||
                                            apt.room?.boarding_house?.name
                                        }}
                                    </div>
                                </td>
                                <td style="padding: 12px 16px">
                                    <div style="font-weight: 550">
                                        {{
                                            new Date(
                                                apt.date,
                                            ).toLocaleDateString("vi-VN")
                                        }}
                                    </div>
                                    <div style="font-size: 11px; color: #64748b">
                                        Lúc {{ apt.time.substring(0, 5) }}
                                        <span v-if="isToday(apt.date)" class="today-badge">Hôm nay!</span>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px">
                                    <div>
                                        {{
                                            apt.room?.boardingHouse?.landlord
                                                ?.name ||
                                            apt.room?.boarding_house?.landlord
                                                .name
                                        }}
                                    </div>
                                    <div style="
                                            font-size: 11.5px;
                                            color: #64748b;
                                        ">
                                        SĐT:
                                        {{
                                            apt.room?.boardingHouse?.landlord
                                                ?.phone ||
                                            apt.room?.boarding_house?.landlord
                                                ?.phone
                                        }}
                                    </div>
                                </td>
                                <td style="padding: 12px 16px">
                                    <span :class="[
                                        'status-badge',
                                        getStatusData(apt.status).cls,
                                    ]" style="
                                            display: inline-flex;
                                            align-items: center;
                                            gap: 4px;
                                            padding: 4px 8px;
                                            border-radius: 6px;
                                            font-size: 11px;
                                            font-weight: 600;
                                            border: 1px solid;
                                        ">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusData(apt.status).dot
                                            " style="
                                                width: 6px;
                                                height: 6px;
                                                border-radius: 50%;
                                            "></span>
                                        {{ getStatusData(apt.status).label }}
                                    </span>
                                </td>
                                <td style="
                                        padding: 12px 16px;
                                        text-align: center;
                                    ">
                                    <div style="
                                            display: flex;
                                            gap: 8px;
                                            justify-content: center;
                                        ">
                                        <Link :href="route('chitiettro', apt.room_id)
                                            " class="btn-action btn-view" title="Xem phòng">
                                            <i class="bi bi-eye-fill"></i>
                                        </Link>
                                        <button v-if="apt.status === 'approved'" @click="showGuide(apt)"
                                            class="btn-action btn-map" title="Chỉ dẫn đường đi">
                                            <i class="bi bi-geo-alt-fill"></i>
                                        </button>
                                    </div>
                                    <div v-if="['approved', 'viewed'].includes(apt.status) && !apt.feedback_result"
                                        style="display: flex; gap: 8px; justify-content: center; margin-top: 8px;">
                                        <button @click="openConfirmInterest(apt, true)" class="btn-action btn-interest"
                                            title="Ưng thuê"
                                            style="background-color: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; width: auto; padding: 0 10px; font-size: 12px; font-weight: bold; cursor: pointer;">
                                            <i class="bi bi-hand-thumbs-up-fill" style="margin-right: 4px;"></i> Ưng
                                        </button>
                                        <button @click="openConfirmInterest(apt, false)"
                                            class="btn-action btn-not-interest" title="Không ưng"
                                            style="background-color: #fef2f2; color: #ef4444; border: 1px solid #fecaca; width: auto; padding: 0 10px; font-size: 12px; font-weight: bold; cursor: pointer;">
                                            <i class="bi bi-hand-thumbs-down-fill" style="margin-right: 4px;"></i> Không
                                            ưng
                                        </button>
                                    </div>
                                    <div v-else-if="apt.feedback_result" style="text-align: center; margin-top: 8px;" class="space-y-1">
                                        <span v-if="['interested', 'like'].includes(apt.feedback_result)"
                                            style="background-color: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 4px; font-size: 10.5px; font-weight: bold; display: inline-block;">
                                            <i class="bi bi-check-circle-fill"></i> Đã chốt: Ưng
                                        </span>
                                        <span v-else-if="apt.feedback_result === 'cancel_requested'"
                                            style="background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a; padding: 3px 8px; border-radius: 6px; font-size: 10.5px; font-weight: bold; display: inline-block;">
                                            <i class="bi bi-clock-history"></i> Đã gửi yêu cầu hủy HĐ (Chờ duyệt)
                                        </span>
                                        <span v-else-if="['not_interested', 'dislike'].includes(apt.feedback_result)"
                                            style="background-color: #fef2f2; color: #ef4444; border: 1px solid #fecaca; padding: 2px 8px; border-radius: 4px; font-size: 10.5px; font-weight: bold;">
                                            <i class="bi bi-x-circle-fill"></i> Đã chốt: Không ưng
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-slate-100 pt-4 mt-4"
                    style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; margin-top: 16px; border-top: 1px solid #f1f5f9;">
                    <span class="text-xs text-slate-400 font-semibold"
                        style="color: #94a3b8; font-size: 12px; font-weight: 600;">
                        Hiển thị {{ (currentPage - 1) * pageSize + 1 }} - {{ Math.min(currentPage * pageSize,
                        appointments.length) }} trong số {{ appointments.length }}
                    </span>
                    <div class="flex items-center gap-1" style="display: flex; gap: 4px; align-items: center;">
                        <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
                            class="w-7 h-7 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-transparent transition-all"
                            style="width: 28px; height: 28px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; background: white; cursor: pointer; transition: all 0.2s;">
                            <i class="bi bi-chevron-left" style="font-size: 10px;"></i>
                        </button>

                        <button v-for="p in totalPages" :key="p" @click="currentPage = p" :class="[
                            'w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-bold transition-all border',
                            currentPage === p
                                ? 'bg-blue-600 border-blue-600 text-white shadow-sm'
                                : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                        ]"
                            style="width: 28px; height: 28px; border-radius: 8px; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;"
                            :style="currentPage === p ? 'background-color: #2563eb; border-color: #2563eb; color: white;' : 'background-color: white; border-color: #e2e8f0; color: #475569;'">
                            {{ p }}
                        </button>

                        <button @click="currentPage = Math.min(totalPages, currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="w-7 h-7 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-transparent transition-all"
                            style="width: 28px; height: 28px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; background: white; cursor: pointer; transition: all 0.2s;">
                            <i class="bi bi-chevron-right" style="font-size: 10px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Cẩm nang hướng dẫn đến xem phòng -->
        <Transition name="modal">
            <div v-if="activeGuideAppointment" class="guide-modal-overlay" @click.self="activeGuideAppointment = null">
                <div class="guide-modal-card">
                    <!-- Header -->
                    <div class="guide-modal-header">
                        <div class="header-title-wrapper">
                            <div class="header-icon-badge">
                                <i class="bi bi-compass-fill"></i>
                            </div>
                            <div>
                                <h3 class="guide-modal-title">Cẩm Nang Tìm Phòng</h3>
                                <p class="guide-modal-subtitle">Thông tin chỉ dẫn & định vị chi tiết</p>
                            </div>
                        </div>
                        <button class="guide-close-btn" @click="activeGuideAppointment = null" title="Đóng">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Content Body -->
                    <div class="guide-modal-body">
                        <!-- Địa chỉ chi tiết -->
                        <div class="guide-info-card address-card">
                            <div class="card-icon blue-icon">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div class="card-content">
                                <div class="card-label">Địa chỉ cơ sở</div>
                                <p class="card-text">
                                    {{
                                        activeGuideAppointment.room?.boardingHouse?.address_detail ||
                                        activeGuideAppointment.room?.boarding_house?.address_detail ||
                                        activeGuideAppointment.room?.address
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Chỉ dẫn ngõ ngách chi tiết -->
                        <div class="guide-info-card directions-card"
                            :class="{ 'no-guide': !(activeGuideAppointment.room?.boardingHouse?.directions_guide || activeGuideAppointment.room?.boarding_house?.directions_guide) }">
                            <div class="card-icon amber-icon">
                                <i class="bi bi-signpost-2-fill"></i>
                            </div>
                            <div class="card-content">
                                <div class="card-label">Chỉ dẫn ngõ ngách</div>
                                <p v-if="activeGuideAppointment.room?.boardingHouse?.directions_guide || activeGuideAppointment.room?.boarding_house?.directions_guide"
                                    class="card-text directions-text">
                                    {{ activeGuideAppointment.room?.boardingHouse?.directions_guide ||
                                        activeGuideAppointment.room?.boarding_house?.directions_guide }}
                                </p>
                                <p v-else class="card-text empty-guide">
                                    Chủ nhà chưa cập nhật chỉ dẫn ngõ ngách. Vui lòng gọi điện thoại để chủ trọ hướng
                                    dẫn trực tiếp.
                                </p>
                            </div>
                        </div>

                        <!-- Số điện thoại & nút gọi nhanh -->
                        <div class="guide-info-card contact-card">
                            <div class="contact-avatar">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <div class="contact-details">
                                <div class="card-label">Chủ trọ liên hệ</div>
                                <div class="contact-name">
                                    {{
                                        activeGuideAppointment.room?.boardingHouse?.landlord?.name ||
                                        activeGuideAppointment.room?.boarding_house?.landlord?.name ||
                                        "Chủ trọ"
                                    }}
                                </div>
                                <div class="contact-phone">
                                    SĐT: {{
                                        activeGuideAppointment.room?.boardingHouse?.landlord?.phone ||
                                        activeGuideAppointment.room?.boarding_house?.landlord?.phone
                                    }}
                                </div>
                            </div>
                            <a :href="`tel:${activeGuideAppointment.room?.boardingHouse?.landlord?.phone || activeGuideAppointment.room?.boarding_house?.landlord?.phone}`"
                                class="call-action-btn">
                                <i class="bi bi-telephone-fill animate-pulse-slow"></i>
                                <span>Gọi điện</span>
                            </a>
                        </div>
                    </div>

                    <!-- Footer (Nút chỉ đường Google Maps) -->
                    <div class="guide-modal-footer">
                        <a :href="getGoogleMapsSearchUrl(activeGuideAppointment)" target="_blank"
                            class="btn-google-maps">
                            <i class="bi bi-map-fill"></i>
                            <span>📍 Chỉ đường bằng Google Maps</span>
                        </a>
                    </div>
                </div>
            </div>
        </Transition>
    </UserLayout>

    <!-- Confirm Interest Modal -->
    <Teleport to="body">
        <div v-if="showConfirmModal" class="review-modal-overlay" @click.self="closeConfirmModal">
            <div class="review-modal-box">
                <div class="review-modal-header">
                    <h3>
                        <i v-if="confirmAction === 'interested'" class="bi bi-info-circle-fill text-emerald-500"
                            style="color: #10b981;"></i>
                        <i v-else class="bi bi-exclamation-triangle-fill text-amber-500" style="color: #f59e0b;"></i>
                        Xác nhận thông tin
                    </h3>
                    <button @click="closeConfirmModal" class="review-close-btn"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="review-modal-body">
                    <div v-if="confirmAction === 'interested'"
                        style="font-size: 14px; color: #475569; line-height: 1.6; margin: 0;">
                        <p style="margin-bottom: 12px;">Bạn có chắc chắn <strong style="color: #059669;">ƯNG</strong>
                            phòng <strong>{{ confirmApt?.room?.room_number }}</strong> và muốn tiến hành thuê không?</p>
                        <p style="margin-bottom: 16px;">Hệ thống sẽ gửi thông báo đến chủ trọ để tạo hợp đồng cho bạn.
                        </p>
                        <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <label
                                style="display: block; font-weight: bold; font-size: 12px; color: #64748b; margin-bottom: 6px;">Số
                                CCCD / CMND (Tùy chọn)</label>
                            <input v-model="tenantCccd"
                                @input="tenantCccd = tenantCccd.replace(/[^0-9]/g, '').slice(0, 12)" type="text"
                                maxlength="12" placeholder="Nhập để chủ trọ tạo hợp đồng nhanh hơn..."
                                style="width: 100%; padding: 10px 12px; border-radius: 6px; border: 1px solid #cbd5e1; outline: none; font-size: 13px; transition: all 0.2s; box-sizing: border-box;"
                                onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 2px rgba(16, 185, 129, 0.1)'"
                                onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'" />
                        </div>
                    </div>
                    <p v-else style="font-size: 14px; color: #475569; line-height: 1.6; margin: 0;">
                        Bạn chắc chắn <strong style="color: #e11d48;">KHÔNG ƯNG</strong> phòng <strong>{{
                            confirmApt?.room?.room_number }}</strong> này?<br><br>
                        Quyết định của bạn sẽ được lưu lại để giúp chúng tôi gợi ý tốt hơn trong tương lai.
                    </p>

                    <div class="review-modal-footer" style="margin-top: 24px;">
                        <button @click="closeConfirmModal" class="btn-review-cancel">Hủy bỏ</button>
                        <button @click="executeInterest"
                            :style="confirmAction === 'interested' ? 'background: #10b981; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);' : 'background: #ef4444; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);'"
                            class="btn-review-submit">
                            <i class="bi bi-check-lg"></i> Xác nhận
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Cancel Interest Modal (Đổi ý Hủy Hợp Đồng) -->
    <Teleport to="body">
        <div v-if="showCancelModal" class="review-modal-overlay" @click.self="closeCancelModal">
            <div class="review-modal-box">
                <div class="review-modal-header" style="border-bottom: 1px solid #fecdd3; background-color: #fff1f2;">
                    <h3 style="color: #e11d48; font-size: 15px; font-weight: bold; margin: 0; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-exclamation-octagon-fill" style="color: #ef4444;"></i>
                        Yêu Cầu Hủy Đăng Ký Hợp Đồng
                    </h3>
                    <button @click="closeCancelModal" class="review-close-btn"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="review-modal-body">
                    <div style="font-size: 13.5px; color: #475569; line-height: 1.6; margin: 0;">
                        <p style="margin-bottom: 8px;">Bạn đang yêu cầu <strong style="color: #e11d48;">HỦY HỢP ĐỒNG / ĐỔI Ý KHÔNG THUÊ</strong> phòng <strong>{{ cancelApt?.room?.room_number }}</strong>.</p>
                        <p style="margin-bottom: 14px; font-size: 12px; color: #64748b;">Lý do hủy của bạn sẽ được gửi tới Chủ trọ để phê duyệt và cập nhật danh sách.</p>

                        <div style="background: #fff1f2; padding: 12px; border-radius: 12px; border: 1px solid #fecdd3;">
                            <label style="display: block; font-weight: bold; font-size: 12px; color: #be123c; margin-bottom: 6px;">
                                Lý do muốn hủy hợp đồng <span style="color: #ef4444;">*</span>
                            </label>
                            <textarea v-model="cancelReason" rows="3" placeholder="Nhập lý do cụ thể (VD: Đã tìm được phòng khác gần cơ quan hơn, thay đổi kế hoạch chuyển đi...)"
                                style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #fda4af; outline: none; font-size: 13px; box-sizing: border-box; background: white;"
                                onfocus="this.style.borderColor='#e11d48'; this.style.boxShadow='0 0 0 2px rgba(225, 29, 72, 0.1)'"
                                onblur="this.style.borderColor='#fda4af'; this.style.boxShadow='none'"></textarea>
                        </div>
                    </div>

                    <div class="review-modal-footer" style="margin-top: 20px;">
                        <button @click="closeCancelModal" class="btn-review-cancel">Hủy bỏ</button>
                        <button @click="executeCancelInterest" style="background: #e11d48; box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.3);" class="btn-review-submit">
                            <i class="bi bi-send-fill"></i> Gửi Yêu Cầu Hủy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
@import "../../css/user.css";
@import "../../css/lichhen.css";
@import "../../css/responsive/responsivetranguser.css";
</style>
