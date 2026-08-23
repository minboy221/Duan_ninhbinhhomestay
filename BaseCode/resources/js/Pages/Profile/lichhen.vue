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
import { showWarning } from "@/Utils/swal";

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
    cancel_requested: {
        label: "Yêu cầu hủy",
        cls: "bg-orange-50 text-orange-600 border-orange-200",
        dot: "bg-orange-500",
    },
    cancelled: {
        label: "Đã hủy",
        cls: "bg-rose-50 text-rose-600 border-rose-200",
        dot: "bg-rose-500",
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
        label: "Đã ký HĐ & Đóng cọc",
        cls: "bg-emerald-50 text-emerald-700 border-emerald-200 font-bold",
        dot: "bg-emerald-500",
    },
    joined_roommate: {
        label: "Đã tham gia ở ghép",
        cls: "bg-purple-50 text-purple-700 border-purple-200 font-bold",
        dot: "bg-purple-500",
    },
    roommate_removed: {
        label: "Đã rời phòng ở ghép",
        cls: "bg-rose-50 text-rose-700 border-rose-200 font-bold",
        dot: "bg-rose-500",
    },
    became_main_tenant: {
        label: "Đã đứng tên Hợp đồng",
        cls: "bg-teal-50 text-teal-700 border-teal-200 font-bold",
        dot: "bg-teal-500",
    },
    terminated: {
        label: "Hợp đồng đã thanh lý",
        cls: "bg-slate-100 text-slate-600 border-slate-200 font-bold",
        dot: "bg-slate-500",
    },
    false_matched: {
        label: "Không thuê",
        cls: "bg-gray-50 text-gray-500 border-gray-100",
        dot: "bg-gray-400",
    },
};

//hàm lấy trạng thái an toàn, nếu gặp trạng thái lạ sẽ tự động hiển thị trạng thái mặc định
const getStatusData = (aptOrStatus) => {
    let key = aptOrStatus;
    if (typeof aptOrStatus === "object" && aptOrStatus !== null) {
        if (
            aptOrStatus.feedback_result === "cancel_requested" ||
            aptOrStatus.status === "cancel_requested"
        ) {
            key = "cancel_requested";
        } else if (
            aptOrStatus.feedback_result === "cancelled" ||
            aptOrStatus.status === "cancelled"
        ) {
            key = "cancelled";
        } else {
            key = aptOrStatus.status;
        }
        if (
            key === "waiting_contract" &&
            (aptOrStatus.room?.current_people > 0 ||
                aptOrStatus.room?.room_posts?.some(
                    (p) => p.type === "stranger",
                ))
        ) {
            return {
                label: "Chờ duyệt ở ghép",
                cls: "bg-purple-50 text-purple-600 border-purple-200 font-bold",
                dot: "bg-purple-500",
            };
        }
    }
    return (
        statusMap[key] || {
            label: key || "Không rõ",
            cls: "bg-slate-50 text-slate-500 border-slate-100",
            dot: "bg-slate-500",
        }
    );
};

//trạng thái hiển thị modal chỉ dãn & bản đồ trực tiếp
const activeGuideAppointment = ref(null);
const mapMode = ref("place"); // 'place' hoặc 'directions'
const userCoords = ref(null);

const expandedMapAptId = ref(null);
const inlineMapMode = ref("place");

function toggleInlineMap(apt) {
    if (expandedMapAptId.value === apt.id) {
        expandedMapAptId.value = null;
    } else {
        expandedMapAptId.value = apt.id;
        inlineMapMode.value = "place";
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    userCoords.value = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                    };
                },
                () => {
                    userCoords.value = null;
                },
            );
        }
    }
}

//mở hướng dẫn xem phòng
function showGuide(appointment) {
    activeGuideAppointment.value = appointment;
    mapMode.value = "place";
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                userCoords.value = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                };
            },
            () => {
                userCoords.value = null;
            },
        );
    }
}

//tạo link google map nhúng trực tiếp
function getGoogleMapsEmbedUrl(appointment) {
    if (!appointment) return "";
    const bh =
        appointment.room?.boardingHouse || appointment.room?.boarding_house;
    const address =
        bh?.address_detail || appointment.room?.address || "Ninh Bình";

    if (mapMode.value === "directions") {
        if (userCoords.value) {
            return `https://maps.google.com/maps?saddr=${userCoords.value.lat},${userCoords.value.lng}&daddr=${encodeURIComponent(address)}&output=embed`;
        }
        return `https://maps.google.com/maps?saddr=My+Location&daddr=${encodeURIComponent(address)}&output=embed`;
    }

    return `https://maps.google.com/maps?q=${encodeURIComponent(address)}&t=&z=15&ie=UTF8&iwloc=&output=embed`;
}

function getInlineGoogleMapsEmbedUrl(appointment) {
    if (!appointment) return "";
    const bh =
        appointment.room?.boardingHouse || appointment.room?.boarding_house;
    const address =
        bh?.address_detail || appointment.room?.address || "Ninh Bình";

    if (inlineMapMode.value === "directions") {
        if (userCoords.value) {
            return `https://maps.google.com/maps?saddr=${userCoords.value.lat},${userCoords.value.lng}&daddr=${encodeURIComponent(address)}&output=embed`;
        }
        return `https://maps.google.com/maps?saddr=My+Location&daddr=${encodeURIComponent(address)}&output=embed`;
    }

    return `https://maps.google.com/maps?q=${encodeURIComponent(address)}&t=&z=15&ie=UTF8&iwloc=&output=embed`;
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
import axios from "axios";

const page = usePage();

// State cho Modal xác nhận Ưng / Không ưng
const showConfirmModal = ref(false);
const confirmAction = ref("interested"); // 'interested' hoặc 'not_interested'
const confirmApt = ref(null);
const tenantCccd = ref("");
const selectedReason = ref("");
const otherReasonDetail = ref("");
const submittingInterest = ref(false);

// State cho Modal Gợi Ý 3 Phòng Trọ Tương Tự từ AI
const showAiAlternativesModal = ref(false);
const loadingAiAlternatives = ref(false);
const aiAlternativesData = ref(null);

function openConfirmInterest(apt, isInterested) {
    confirmApt.value = apt;
    confirmAction.value = isInterested ? "interested" : "not_interested";
    tenantCccd.value = page.props.auth?.user?.cccd_number || "";
    selectedReason.value = "";
    otherReasonDetail.value = "";
    showConfirmModal.value = true;
}

function closeConfirmModal() {
    showConfirmModal.value = false;
    confirmApt.value = null;
    selectedReason.value = "";
    otherReasonDetail.value = "";
    submittingInterest.value = false;
}

function executeInterest() {
    if (!confirmApt.value) return;

    let finalReason = null;
    if (confirmAction.value === "not_interested") {
        if (!selectedReason.value) {
            showWarning("Chú ý", "Vui lòng chọn hoặc nhập lý do không ưng!");
            return;
        }
        finalReason =
            selectedReason.value === "Lý do khác"
                ? otherReasonDetail.value.trim()
                : selectedReason.value;

        if (!finalReason) {
            showWarning("Chú ý", "Vui lòng nhập chi tiết lý do!");
            return;
        }

        submittingInterest.value = true;
        axios
            .post(route("appointments.interest", confirmApt.value.id), {
                result: "not_interested",
                cccd: tenantCccd.value,
                reason: finalReason,
            })
            .then((res) => {
                closeConfirmModal();
                if (
                    res.data &&
                    res.data.ai_recommendations &&
                    res.data.ai_recommendations.rooms &&
                    res.data.ai_recommendations.rooms.length > 0
                ) {
                    aiAlternativesData.value = res.data.ai_recommendations;
                    showAiAlternativesModal.value = true;
                } else {
                    showSuccess("Thành công", "Đã ghi nhận lựa chọn của bạn!");
                }
                router.reload({ only: ["appointments"] });
            })
            .catch(() => {
                showError(
                    "Lỗi",
                    "Không thể thực hiện thao tác. Vui lòng thử lại!",
                );
            })
            .finally(() => {
                submittingInterest.value = false;
            });

        return;
    }

    // Trường hợp Ưng thuê
    router.post(
        route("appointments.interest", confirmApt.value.id),
        {
            result: confirmAction.value,
            cccd: tenantCccd.value,
            reason: finalReason,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeConfirmModal();
                showSuccess("Thành công", "Đã ghi nhận lựa chọn của bạn!");
            },
            onError: () => {
                showError(
                    "Lỗi",
                    "Không thể thực hiện thao tác. Vui lòng thử lại!",
                );
            },
        },
    );
}

// Hàm mở Modal xem 3 phòng AI gợi ý cho lịch hẹn đã chốt không ưng
function fetchAndShowAiAlternatives(apt) {
    loadingAiAlternatives.value = true;
    showAiAlternativesModal.value = true;
    aiAlternativesData.value = null;

    axios
        .get(route("appointments.ai-alternatives", apt.id))
        .then((res) => {
            if (res.data && res.data.success) {
                aiAlternativesData.value = res.data;
            } else {
                showError(
                    "Thông báo",
                    res.data.message || "Hiện chưa có phòng phù hợp.",
                );
                showAiAlternativesModal.value = false;
            }
        })
        .catch(() => {
            showError("Lỗi", "Không thể tải danh sách gợi ý phòng từ AI.");
            showAiAlternativesModal.value = false;
        })
        .finally(() => {
            loadingAiAlternatives.value = false;
        });
}

function closeAiAlternativesModal() {
    showAiAlternativesModal.value = false;
    aiAlternativesData.value = null;
    loadingAiAlternatives.value = false;
}

// State cho Modal Hủy Hợp Đồng / Đổi ý
const showCancelModal = ref(false);
const cancelApt = ref(null);
const cancelReason = ref("");

const openCancelModal = (apt) => {
    cancelApt.value = apt;
    cancelReason.value = "";
    showCancelModal.value = true;
};

function closeCancelModal() {
    showCancelModal.value = false;
    cancelApt.value = null;
    cancelReason.value = "";
}

function executeCancelInterest() {
    if (!cancelApt.value) return;
    if (!cancelReason.value || !cancelReason.value.trim()) {
        showError(
            "Thiếu lý do",
            "Vui lòng nhập lý do muốn hủy hợp đồng / hủy đăng ký!",
        );
        return;
    }

    router.post(
        route(
            "appointments.cancel_interest",
            cancelApt.value.hash_id || cancelApt.value.id,
        ),
        {
            reason: cancelReason.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeCancelModal();
                showSuccess(
                    "Đã gửi yêu cầu",
                    "Yêu cầu hủy đăng ký hợp đồng đã được gửi đến Chủ trọ!",
                );
            },
            onError: () => {
                showError(
                    "Lỗi",
                    "Không thể gửi yêu cầu hủy. Vui lòng thử lại!",
                );
            },
        },
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

                <!-- Appointments Table (Desktop View) -->
                <div class="table-container desktop-only-table lichhen-table-wrapper">
                    <table class="lichhen-table">
                        <thead>
                            <tr class="lichhen-table-head-row">
                                <th class="lichhen-th">Phòng đặt</th>
                                <th class="lichhen-th">Thời gian</th>
                                <th class="lichhen-th">Chủ trọ</th>
                                <th class="lichhen-th">Trạng thái</th>
                                <th class="lichhen-th lichhen-th-center">
                                    Hành động
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="appointments.length === 0" class="lichhen-tr-empty">
                                <td colspan="5">
                                    <i class="bi bi-calendar-x lichhen-icon-empty"></i>
                                    Bạn chưa có lịch hẹn xem phòng nào.
                                </td>
                            </tr>
                            <template v-for="apt in paginatedAppointments" :key="apt.id">
                                <tr class="lichhen-tr-row">
                                    <td class="lichhen-td">
                                        <div class="room-number-title">
                                            Phòng {{ apt.room?.room_number }}
                                        </div>
                                        <div class="bh-name-subtitle">
                                            {{
                                                apt.room?.boardingHouse?.name ||
                                                apt.room?.boarding_house?.name
                                            }}
                                        </div>
                                    </td>
                                    <td class="lichhen-td">
                                        <div class="apt-time-date">
                                            {{
                                                new Date(
                                                    apt.date,
                                                ).toLocaleDateString("vi-VN")
                                            }}
                                        </div>
                                        <div class="apt-time-sub">
                                            Lúc {{ apt.time.substring(0, 5) }}
                                            <span v-if="isToday(apt.date)" class="today-badge">Hôm nay!</span>
                                        </div>
                                    </td>
                                    <td class="lichhen-td">
                                        <div>
                                            {{
                                                apt.room?.boardingHouse
                                                    ?.landlord?.name ||
                                                apt.room?.boarding_house
                                                    ?.landlord.name
                                            }}
                                        </div>
                                        <div class="landlord-phone-sub">
                                            SĐT:
                                            {{
                                                apt.room?.boardingHouse
                                                    ?.landlord?.phone ||
                                                apt.room?.boarding_house
                                                    ?.landlord?.phone
                                            }}
                                        </div>
                                    </td>
                                    <td class="lichhen-td">
                                        <span :class="[
                                            'status-badge status-badge-inline',
                                            getStatusData(apt.status).cls,
                                        ]">
                                            <span class="status-dot-inline" :class="getStatusData(apt.status)
                                                    .dot
                                                "></span>
                                            {{
                                                getStatusData(apt.status).label
                                            }}
                                        </span>
                                    </td>
                                    <td class="lichhen-td-center">
                                        <div class="action-btn-group">
                                            <Link :href="route(
                                                'chitiettro',
                                                apt.room_id,
                                            )
                                                " class="btn-action btn-view" title="Xem phòng">
                                                <i class="bi bi-eye-fill"></i>
                                            </Link>
                                            <button v-if="apt.status === 'approved'" @click="toggleInlineMap(apt)"
                                                class="btn-action btn-map" title="Hiện bản đồ & đường đi trực tiếp"
                                                :class="{
                                                    active:
                                                        expandedMapAptId ===
                                                        apt.id,
                                                }">
                                                <i class="bi bi-geo-alt-fill"></i>
                                            </button>
                                        </div>
                                        <div v-if="
                                            ['approved', 'viewed'].includes(
                                                apt.status,
                                            ) && !apt.feedback_result
                                        " class="feedback-btn-group"></div>
                                        <div v-else-if="apt.feedback_result" style="
                                                text-align: center;
                                                margin-top: 8px;
                                            ">
                                            <!-- Trường hợp đã bấm Ưng -->
                                            <div v-if="
                                                [
                                                    'interested',
                                                    'like',
                                                ].includes(
                                                    apt.feedback_result,
                                                )
                                            " style="
                                                    display: flex;
                                                    flex-direction: column;
                                                    align-items: center;
                                                    gap: 4px;
                                                ">
                                                <span class="badge-interested">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    Đã chốt: Ưng
                                                </span>
                                                <!-- Nút Đổi ý (Chỉ hiển thị khi CHƯA chốt Hợp đồng/Thanh toán/Vào ở ghép/Thanh lý) -->
                                                <button v-if="
                                                    ![
                                                        'success_matched',
                                                        'joined_roommate',
                                                        'roommate_removed',
                                                        'became_main_tenant',
                                                        'terminated',
                                                    ].includes(apt.status)
                                                " @click="
                                                        openCancelModal(apt)
                                                        " class="btn-cancel-interest">
                                                    Đổi ý / Hủy đăng ký
                                                </button>
                                            </div>

                                            <!-- Trường hợp đã gửi Yêu cầu Hủy -->
                                            <span v-else-if="
                                                apt.feedback_result ===
                                                'cancel_requested'
                                            " class="badge-cancel-requested">
                                                <i class="bi bi-exclamation-circle-fill"></i>
                                                Đã gửi Yêu cầu Hủy
                                            </span>

                                            <!-- Trường hợp Không ưng -->
                                            <div v-else-if="
                                                [
                                                    'not_interested',
                                                    'dislike',
                                                ].includes(
                                                    apt.feedback_result,
                                                )
                                            " style="
                                                    display: flex;
                                                    flex-direction: column;
                                                    align-items: center;
                                                    gap: 4px;
                                                ">
                                                <span class="badge-not-interested">
                                                    <i class="bi bi-x-circle-fill"></i>
                                                    Đã chốt: Không ưng
                                                </span>
                                                <button type="button" @click="
                                                    fetchAndShowAiAlternatives(
                                                        apt,
                                                    )
                                                    " class="btn-ai-suggest-badge"
                                                    title="Xem 3 phòng trọ tương tự do AI gợi ý">
                                                    <i class="bi bi-stars"></i>
                                                    3 phòng AI gợi ý
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Hàng Bản Đồ Trực Tiếp Hiện Ở Khoảng Trống Ngay Dưới Dòng (Desktop) -->
                                <tr v-if="expandedMapAptId === apt.id" style="
                                        background: #f8fafc;
                                        border-bottom: 2px solid #e2e8f0;
                                    ">
                                    <td colspan="5" style="padding: 16px">
                                        <div style="
                                                background: #ffffff;
                                                border-radius: 16px;
                                                border: 1px solid #cbd5e1;
                                                padding: 16px;
                                                box-shadow: 0 4px 14px
                                                    rgba(15, 23, 42, 0.06);
                                            ">
                                            <!-- Header khối bản đồ -->
                                            <div style="
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: space-between;
                                                    margin-bottom: 12px;
                                                    flex-wrap: wrap;
                                                    gap: 10px;
                                                ">
                                                <div>
                                                    <h4 style="
                                                            font-size: 14px;
                                                            font-weight: 800;
                                                            color: #0f172a;
                                                            margin: 0;
                                                            display: flex;
                                                            align-items: center;
                                                            gap: 6px;
                                                        ">
                                                        <i class="bi bi-map-fill" style="
                                                                color: #2563eb;
                                                            "></i>
                                                        Bản đồ trực tiếp — Phòng
                                                        {{
                                                            apt.room
                                                                ?.room_number
                                                        }}
                                                        ({{
                                                            apt.room
                                                                ?.boardingHouse
                                                                ?.name ||
                                                            apt.room
                                                                ?.boarding_house
                                                                ?.name
                                                        }})
                                                    </h4>
                                                    <p style="
                                                            font-size: 12px;
                                                            color: #64748b;
                                                            margin: 3px 0 0 0;
                                                        ">
                                                        📍
                                                        {{
                                                            apt.room
                                                                ?.boardingHouse
                                                                ?.address_detail ||
                                                            apt.room
                                                                ?.boarding_house
                                                                ?.address_detail ||
                                                            apt.room?.address
                                                        }}
                                                    </p>
                                                </div>
                                                <div style="
                                                        display: flex;
                                                        gap: 8px;
                                                        align-items: center;
                                                    ">
                                                    <button type="button" @click="
                                                        inlineMapMode =
                                                        'place'
                                                        " :style="{
                                                            padding: '6px 12px',
                                                            fontSize: '12px',
                                                            fontWeight: '700',
                                                            borderRadius: '8px',
                                                            border:
                                                                '1px solid ' +
                                                                (inlineMapMode ===
                                                                    'place'
                                                                    ? '#2563eb'
                                                                    : '#cbd5e1'),
                                                            background:
                                                                inlineMapMode ===
                                                                    'place'
                                                                    ? '#eff6ff'
                                                                    : '#ffffff',
                                                            color:
                                                                inlineMapMode ===
                                                                    'place'
                                                                    ? '#2563eb'
                                                                    : '#64748b',
                                                            cursor: 'pointer',
                                                            transition:
                                                                'all 0.2s',
                                                        }">
                                                        <i class="bi bi-geo-alt-fill"></i>
                                                        Vị trí trọ
                                                    </button>
                                                    <button type="button" @click="
                                                        inlineMapMode =
                                                        'directions'
                                                        " :style="{
                                                            padding: '6px 12px',
                                                            fontSize: '12px',
                                                            fontWeight: '700',
                                                            borderRadius: '8px',
                                                            border:
                                                                '1px solid ' +
                                                                (inlineMapMode ===
                                                                    'directions'
                                                                    ? '#16a34a'
                                                                    : '#cbd5e1'),
                                                            background:
                                                                inlineMapMode ===
                                                                    'directions'
                                                                    ? '#f0fdf4'
                                                                    : '#ffffff',
                                                            color:
                                                                inlineMapMode ===
                                                                    'directions'
                                                                    ? '#16a34a'
                                                                    : '#64748b',
                                                            cursor: 'pointer',
                                                            transition:
                                                                'all 0.2s',
                                                        }">
                                                        <i class="bi bi-signpost-split-fill"></i>
                                                        Chỉ đường từ tôi
                                                    </button>
                                                    <button type="button" @click="showGuide(apt)" class="btn-map-guide">
                                                        <i class="bi bi-compass-fill"></i>
                                                        Cẩm nang
                                                    </button>
                                                    <button type="button" @click="
                                                        expandedMapAptId =
                                                        null
                                                        " title="Đóng bản đồ" class="btn-map-close">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Ghi chú ngõ ngách nếu có -->
                                            <div v-if="
                                                apt.room?.boardingHouse
                                                    ?.directions_guide ||
                                                apt.room?.boarding_house
                                                    ?.directions_guide
                                            " class="directions-guide-box">
                                                <i class="bi bi-signpost-2-fill text-amber-600 text-base"></i>
                                                <span><strong>Chỉ dẫn ngõ
                                                        ngách:</strong>
                                                    {{
                                                        apt.room?.boardingHouse
                                                            ?.directions_guide ||
                                                        apt.room?.boarding_house
                                                            ?.directions_guide
                                                    }}</span>
                                            </div>

                                            <!-- Khung nhúng Google Maps -->
                                            <div class="inline-iframe-box">
                                                <iframe class="inline-iframe" loading="lazy" allowfullscreen
                                                    referrerpolicy="no-referrer-when-downgrade" :src="getInlineGoogleMapsEmbedUrl(
                                                        apt,
                                                    )
                                                        ">
                                                </iframe>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- GIAO DIỆN MOBILE: DANH SÁCH THẺ DẠNG CARD -->
                <div class="mobile-appointment-list">
                    <div v-if="appointments.length === 0" style="
                            text-align: center;
                            padding: 32px 16px;
                            background: #ffffff;
                            border-radius: 16px;
                            border: 1px solid #e2e8f0;
                            color: #94a3b8;
                        ">
                        <i class="bi bi-calendar-x" style="
                                font-size: 32px;
                                display: block;
                                margin-bottom: 8px;
                            "></i>
                        <span style="font-size: 13px; font-weight: 600">Bạn chưa có lịch hẹn xem phòng nào.</span>
                    </div>

                    <div v-for="apt in paginatedAppointments" :key="'mob-' + apt.id" class="mobile-apt-card">
                        <!-- Header Card: Tên phòng & Trạng thái -->
                        <div class="mobile-apt-header">
                            <div>
                                <h3 class="mobile-apt-title">
                                    Phòng {{ apt.room?.room_number }}
                                </h3>
                                <p class="mobile-apt-house">
                                    {{
                                        apt.room?.boardingHouse?.name ||
                                        apt.room?.boarding_house?.name
                                    }}
                                </p>
                            </div>
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
                                <span style="
                                        width: 6px;
                                        height: 6px;
                                        border-radius: 50%;
                                    " :class="getStatusData(apt.status).dot"></span>
                                {{ getStatusData(apt.status).label }}
                            </span>
                        </div>

                        <!-- Body Card: Thời gian & Chủ trọ -->
                        <div class="mobile-apt-body">
                            <div class="mobile-apt-info-item">
                                <i class="bi bi-clock-fill text-blue-500"></i>
                                <div>
                                    <span class="info-label">Thời gian hẹn xem</span>
                                    <span class="info-value">
                                        {{
                                            new Date(
                                                apt.date,
                                            ).toLocaleDateString("vi-VN")
                                        }}
                                        lúc {{ apt.time.substring(0, 5) }}
                                        <span v-if="isToday(apt.date)" class="today-badge" style="margin-left: 4px">Hôm
                                            nay!</span>
                                    </span>
                                </div>
                            </div>

                            <div class="mobile-apt-info-item">
                                <i class="bi bi-person-fill text-emerald-500"></i>
                                <div>
                                    <span class="info-label">Chủ trọ</span>
                                    <span class="info-value">
                                        {{
                                            apt.room?.boardingHouse?.landlord
                                                ?.name ||
                                            apt.room?.boarding_house?.landlord
                                                ?.name
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="
                                apt.room?.boardingHouse?.landlord?.phone ||
                                apt.room?.boarding_house?.landlord?.phone
                            " class="mobile-apt-info-item">
                                <i class="bi bi-telephone-fill text-indigo-500"></i>
                                <div>
                                    <span class="info-label">Số điện thoại</span>
                                    <span class="info-value">
                                        <a :href="`tel:${apt.room?.boardingHouse?.landlord?.phone || apt.room?.boarding_house?.landlord?.phone}`"
                                            class="mobile-call-link" style="
                                                color: #2563eb;
                                                font-weight: bold;
                                                text-decoration: underline;
                                            ">
                                            {{
                                                apt.room?.boardingHouse
                                                    ?.landlord?.phone ||
                                                apt.room?.boarding_house
                                                    ?.landlord?.phone
                                            }}
                                        </a>
                                    </span>
                                </div>
                                        {{ expandedMapAptId === apt.id
                                            ? 'Ẩn bản đồ'
                                            : 'Bản đồ & Đường đi'
                                        }}
                                    </span>
                                </button>
                            </div>
                                        }}
                                    </a>
                                </div>
                            </div>

                            <!-- Action Card Footer -->
                            <div class="mobile-apt-actions">
                                <!-- Hàng nút chính -->
                                <div class="mobile-action-row">
                                    <Link :href="route('chitiettro', apt.room_id)"
                                        class="mobile-action-btn btn-view-room">
                                        <i class="bi bi-eye-fill"></i>
                                        <span>Xem phòng</span>
                                    </Link>

                                    <button v-if="apt.status === 'approved'" type="button" @click="toggleInlineMap(apt)"
                                        class="mobile-action-btn btn-map-toggle" :class="{
                                            active: expandedMapAptId === apt.id,
                                        }">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <span>
                                            {{
                                                expandedMapAptId === apt.id
                                                    ? "Ẩn bản đồ"
                                                    : "Bản đồ & Đường đi"
                                            }}
                                        </span>
                                    </button>
                                </div>

                                <!-- Nút Ưng / Không ưng -->
                                <div v-if="
                                    ['approved', 'viewed'].includes(
                                        apt.status,
                                    ) && !apt.feedback_result
                                " class="mobile-action-row">
                                    <button type="button" @click="openConfirmInterest(apt, true)"
                                        class="mobile-action-btn btn-like">
                                        <i class="bi bi-hand-thumbs-up-fill"></i>
                                        <span>Ưng thuê</span>
                                    </button>

                                    <button type="button" @click="openConfirmInterest(apt, false)"
                                        class="mobile-action-btn btn-dislike">
                                        <i class="bi bi-hand-thumbs-down-fill"></i>
                                        <span>Không ưng</span>
                                    </button>
                                </div>

                                <!-- Đã phản hồi -->
                                <div v-else-if="apt.feedback_result" class="mobile-feedback-result">
                                    <span v-if="
                                        ['interested', 'like'].includes(
                                            apt.feedback_result,
                                        )
                                    " class="feedback-badge like">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Đã chốt: Ưng thuê
                                    </span>

                                    <span v-else-if="
                                        apt.feedback_result ===
                                        'cancel_requested'
                                    " class="feedback-badge" style="
                                            background-color: #fffbeb;
                                            color: #d97706;
                                            border: 1px solid #fde68a;
                                            padding: 4px 8px;
                                            border-radius: 6px;
                                            font-size: 11px;
                                            font-weight: bold;
                                        ">
                                        <i class="bi bi-clock-history"></i>
                                        Đã gửi yêu cầu hủy HĐ (Chờ duyệt)
                                    </span>

                                    <div v-else-if="
                                        [
                                            'not_interested',
                                            'dislike',
                                        ].includes(apt.feedback_result)
                                    " style="
                                            display: flex;
                                            flex-direction: column;
                                            align-items: center;
                                            width: 100%;
                                        ">
                                        <span class="feedback-badge dislike">
                                            <i class="bi bi-x-circle-fill"></i>
                                            Đã chốt: Không ưng
                                        </span>
                                        <button type="button" @click="
                                            fetchAndShowAiAlternatives(apt)
                                            " class="mobile-btn-ai-suggest">
                                            <i class="bi bi-stars"></i> Xem 3
                                            phòng AI gợi ý
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Khối Bản Đồ Nhúng Trực Tiếp Cho Mobile -->
                            <div v-if="expandedMapAptId === apt.id" class="mobile-map-container">
                                <div class="mobile-map-header">
                                    <div style="
                                            display: flex;
                                            align-items: center;
                                            justify-content: space-between;
                                            width: 100%;
                                            border-bottom: 1px solid #f1f5f9;
                                            padding-bottom: 6px;
                                        ">
                                        <span class="mobile-map-title">
                                            <i class="bi bi-map-fill" style="color: #2563eb"></i>
                                            Bản đồ P.{{ apt.room?.room_number }}
                                        </span>
                                        <button type="button" @click="expandedMapAptId = null" class="mobile-map-close">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <!-- Thanh nút tab 100% width vừa vặn -->
                                    <div class="mobile-map-tabs">
                                        <button type="button" @click="inlineMapMode = 'place'" :class="{
                                            active:
                                                inlineMapMode === 'place',
                                        }" class="tab-btn">
                                            <i class="bi bi-geo-alt-fill"></i>
                                            Vị trí
                                        </button>
                                        <button type="button" @click="
                                            inlineMapMode = 'directions'
                                            " :class="{
                                                active:
                                                    inlineMapMode ===
                                                    'directions',
                                            }" class="tab-btn">
                                            <i class="bi bi-signpost-split-fill"></i>
                                            Chỉ đường
                                        </button>
                                        <button type="button" @click="showGuide(apt)" class="tab-btn guide">
                                            <i class="bi bi-compass-fill"></i>
                                            Cẩm nang
                                        </button>
                                    </div>
                                </div>

                                <!-- Ghi chú ngõ ngách nếu có -->
                                <div v-if="
                                    apt.room?.boardingHouse
                                        ?.directions_guide ||
                                    apt.room?.boarding_house
                                        ?.directions_guide
                                " class="mobile-directions-guide">
                                    <i class="bi bi-signpost-2-fill" style="color: #d97706"></i>
                                    <span><strong>Ngõ ngách:</strong>
                                        {{
                                            apt.room?.boardingHouse
                                                ?.directions_guide ||
                                            apt.room?.boarding_house
                                                ?.directions_guide
                                        }}</span>
                                </div>

                                <!-- Iframe Bản đồ -->
                                <div class="mobile-iframe-wrapper">
                                    <iframe width="100%" height="100%" style="border: 0; display: block" loading="lazy"
                                        allowfullscreen referrerpolicy="no-referrer-when-downgrade"
                                        :src="getInlineGoogleMapsEmbedUrl(apt)">
                                    </iframe>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Phân trang -->
                    <div v-if="totalPages > 1"
                        class="flex items-center justify-between border-t border-slate-100 pt-4 mt-4" style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding-top: 16px;
                            margin-top: 16px;
                            border-top: 1px solid #f1f5f9;
                        ">
                        <span class="text-xs text-slate-400 font-semibold" style="
                                color: #94a3b8;
                                font-size: 12px;
                                font-weight: 600;
                            ">
                            Hiển thị {{ (currentPage - 1) * pageSize + 1 }} -
                            {{
                                Math.min(
                                    currentPage * pageSize,
                                    appointments.length,
                                )
                            }}
                            trong số {{ appointments.length }}
                        </span>
                        <div class="flex items-center gap-1" style="display: flex; gap: 4px; align-items: center">
                            <button @click="
                                currentPage = Math.max(1, currentPage - 1)
                                " :disabled="currentPage === 1"
                                class="w-7 h-7 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-transparent transition-all"
                                style="
                                    width: 28px;
                                    height: 28px;
                                    border-radius: 8px;
                                    border: 1px solid #e2e8f0;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    background: white;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                ">
                                <i class="bi bi-chevron-left" style="font-size: 10px"></i>
                            </button>

                            <button v-for="p in totalPages" :key="p" @click="currentPage = p" :class="[
                                'w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-bold transition-all border',
                                currentPage === p
                                    ? 'bg-blue-600 border-blue-600 text-white shadow-sm'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-50',
                            ]" style="
                                    width: 28px;
                                    height: 28px;
                                    border-radius: 8px;
                                    font-size: 11px;
                                    font-weight: 700;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                " :style="currentPage === p
                                        ? 'background-color: #2563eb; border-color: #2563eb; color: white;'
                                        : 'background-color: white; border-color: #e2e8f0; color: #475569;'
                                    ">
                                {{ p }}
                            </button>

                            <button @click="
                                currentPage = Math.min(
                                    totalPages,
                                    currentPage + 1,
                                )
                                " :disabled="currentPage === totalPages"
                                class="w-7 h-7 rounded-lg flex items-center justify-center border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-transparent transition-all"
                                style="
                                    width: 28px;
                                    height: 28px;
                                    border-radius: 8px;
                                    border: 1px solid #e2e8f0;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    background: white;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                ">
                                <i class="bi bi-chevron-right" style="font-size: 10px"></i>
                            </button>
                        </div>
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
                                <h3 class="guide-modal-title">
                                    Cẩm Nang Tìm Phòng
                                </h3>
                                <p class="guide-modal-subtitle">
                                    Thông tin chỉ dẫn & định vị chi tiết
                                </p>
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
                                        activeGuideAppointment.room
                                            ?.boardingHouse?.address_detail ||
                                        activeGuideAppointment.room
                                            ?.boarding_house?.address_detail ||
                                        activeGuideAppointment.room?.address
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Khối Bản Đồ Google Nhúng Trực Tiếp -->
                        <div class="guide-map-card" style="
                                background: #ffffff;
                                border-radius: 16px;
                                border: 1px solid #e2e8f0;
                                padding: 12px;
                                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
                            ">
                            <div style="
                                    display: flex;
                                    align-items: center;
                                    justify-content: space-between;
                                    margin-bottom: 10px;
                                ">
                                <span style="
                                        font-size: 13px;
                                        font-weight: 800;
                                        color: #0f172a;
                                        display: flex;
                                        align-items: center;
                                        gap: 6px;
                                    ">
                                    <i class="bi bi-map-fill" style="color: #2563eb"></i>
                                    Bản đồ trực tiếp
                                </span>
                                <div style="display: flex; gap: 6px">
                                    <button type="button" @click="mapMode = 'place'" :style="{
                                        padding: '5px 11px',
                                        fontSize: '11px',
                                        fontWeight: '700',
                                        borderRadius: '8px',
                                        border:
                                            '1px solid ' +
                                            (mapMode === 'place'
                                                ? '#2563eb'
                                                : '#cbd5e1'),
                                        background:
                                            mapMode === 'place'
                                                ? '#eff6ff'
                                                : '#ffffff',
                                        color:
                                            mapMode === 'place'
                                                ? '#2563eb'
                                                : '#64748b',
                                        cursor: 'pointer',
                                        transition: 'all 0.2s',
                                    }">
                                        <i class="bi bi-geo-alt-fill"></i> Vị
                                        trí trọ
                                    </button>
                                    <button type="button" @click="mapMode = 'directions'" :style="{
                                        padding: '5px 11px',
                                        fontSize: '11px',
                                        fontWeight: '700',
                                        borderRadius: '8px',
                                        border:
                                            '1px solid ' +
                                            (mapMode === 'directions'
                                                ? '#16a34a'
                                                : '#cbd5e1'),
                                        background:
                                            mapMode === 'directions'
                                                ? '#f0fdf4'
                                                : '#ffffff',
                                        color:
                                            mapMode === 'directions'
                                                ? '#16a34a'
                                                : '#64748b',
                                        cursor: 'pointer',
                                        transition: 'all 0.2s',
                                    }">
                                        <i class="bi bi-signpost-split-fill"></i>
                                        Chỉ đường
                                    </button>
                                </div>
                            </div>
                            <div style="
                                    border-radius: 12px;
                                    overflow: hidden;
                                    border: 1px solid #e2e8f0;
                                    height: 260px;
                                    position: relative;
                                    background: #f8fafc;
                                ">
                                <iframe width="100%" height="100%" style="border: 0; display: block" loading="lazy"
                                    allowfullscreen referrerpolicy="no-referrer-when-downgrade" :src="getGoogleMapsEmbedUrl(
                                        activeGuideAppointment,
                                    )
                                        ">
                                </iframe>
                            </div>
                        </div>

                        <!-- Chỉ dẫn ngõ ngách chi tiết -->
                        <div class="guide-info-card directions-card" :class="{
                            'no-guide': !(
                                activeGuideAppointment.room?.boardingHouse
                                    ?.directions_guide ||
                                activeGuideAppointment.room?.boarding_house
                                    ?.directions_guide
                            ),
                        }">
                            <div class="card-icon amber-icon">
                                <i class="bi bi-signpost-2-fill"></i>
                            </div>
                            <div class="card-content">
                                <div class="card-label">Chỉ dẫn ngõ ngách</div>
                                <p v-if="
                                    activeGuideAppointment.room
                                        ?.boardingHouse?.directions_guide ||
                                    activeGuideAppointment.room
                                        ?.boarding_house?.directions_guide
                                " class="card-text directions-text">
                                    {{
                                        activeGuideAppointment.room
                                            ?.boardingHouse?.directions_guide ||
                                        activeGuideAppointment.room
                                            ?.boarding_house?.directions_guide
                                    }}
                                </p>
                                <p v-else class="card-text empty-guide">
                                    Chủ nhà chưa cập nhật chỉ dẫn ngõ ngách. Vui
                                    lòng gọi điện thoại để chủ trọ hướng dẫn
                                    trực tiếp.
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
                                        activeGuideAppointment.room
                                            ?.boardingHouse?.landlord?.name ||
                                        activeGuideAppointment.room
                                            ?.boarding_house?.landlord?.name ||
                                        "Chủ trọ"
                                    }}
                                </div>
                                <div class="contact-phone">
                                    SĐT:
                                    {{
                                        activeGuideAppointment.room
                                            ?.boardingHouse?.landlord?.phone ||
                                        activeGuideAppointment.room
                                            ?.boarding_house?.landlord?.phone
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
                        <a :href="getGoogleMapsSearchUrl(activeGuideAppointment)
                            " target="_blank" class="btn-google-maps">
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
                            style="color: #10b981"></i>
                        <i v-else class="bi bi-exclamation-triangle-fill text-amber-500" style="color: #f59e0b"></i>
                        Xác nhận thông tin
                    </h3>
                    <button @click="closeConfirmModal" class="review-close-btn">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="review-modal-body">
                    <div v-if="confirmAction === 'interested'" style="
                            font-size: 14px;
                            color: #475569;
                            line-height: 1.6;
                            margin: 0;
                        ">
                        <p style="margin-bottom: 12px">
                            Bạn có chắc chắn
                            <strong style="color: #059669">ƯNG</strong> phòng
                            <strong>{{ confirmApt?.room?.room_number }}</strong>
                            và muốn tiến hành thuê không?
                        </p>
                        <p style="margin-bottom: 16px">
                            Hệ thống sẽ gửi thông báo đến chủ trọ để tạo hợp
                            đồng cho bạn.
                        </p>
                    </div>
                    <div v-else style="
                            font-size: 14px;
                            color: #475569;
                            line-height: 1.6;
                            margin: 0;
                        ">
                        <p style="margin-bottom: 12px">
                            Bạn chắc chắn
                            <strong style="color: #e11d48">KHÔNG ƯNG</strong>
                            phòng
                            <strong>{{ confirmApt?.room?.room_number }}</strong>
                            này?
                        </p>

                        <!-- Danh sách lý do động -->
                        <div class="reason-radio-box">
                            <label class="reason-radio-label">Vui lòng chọn lý do cụ thể:</label>

                            <div v-for="(reason, idx) in $page.props.settings
                                .not_interested_reasons || []" :key="idx" class="reason-radio-item">
                                <input type="radio" :id="'reason_' + idx" v-model="selectedReason" :value="reason"
                                    class="reason-radio-input" />
                                <label :for="'reason_' + idx" class="reason-radio-text">{{ reason }}</label>
                            </div>
                        </div>

                        <!-- Ô nhập lý do khác chi tiết (Chỉ hiện khi chọn "Lý do khác") -->
                        <div v-if="selectedReason === 'Lý do khác'" class="other-reason-box">
                            <label class="other-reason-label">Mô tả chi tiết lý do khác:</label>
                            <textarea v-model="otherReasonDetail" rows="2" placeholder="Nhập lý do chi tiết..."
                                class="other-reason-textarea"></textarea>
                        </div>
                    </div>

                    <div class="review-modal-footer" style="margin-top: 24px">
                        <button @click="closeConfirmModal" class="btn-review-cancel">
                            Hủy bỏ
                        </button>
                        <button @click="executeInterest" :style="confirmAction === 'interested'
                                ? 'background: #10b981; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);'
                                : 'background: #ef4444; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);'
                            " class="btn-review-submit">
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
                <div class="review-modal-header header-cancel-modal">
                    <h3 class="title-cancel-modal">
                        <i class="bi bi-exclamation-octagon-fill"></i>
                        Yêu Cầu Hủy Đăng Ký Hợp Đồng
                    </h3>
                    <button @click="closeCancelModal" class="review-close-btn">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="review-modal-body">
                    <div class="cancel-modal-content">
                        <p style="margin-bottom: 8px">
                            Bạn đang yêu cầu
                            <strong style="color: #e11d48">HỦY HỢP ĐỒNG / ĐỔI Ý KHÔNG THUÊ</strong>
                            phòng
                            <strong>{{ cancelApt?.room?.room_number }}</strong>.
                        </p>
                        <p class="cancel-modal-subtext">
                            Lý do hủy của bạn sẽ được gửi tới Chủ trọ để phê
                            duyệt và cập nhật danh sách.
                        </p>

                        <div class="cancel-modal-textarea-box">
                            <label class="cancel-modal-label">
                                Lý do muốn hủy hợp đồng
                                <span style="color: #ef4444">*</span>
                            </label>
                            <textarea v-model="cancelReason" rows="3"
                                placeholder="Nhập lý do cụ thể (VD: Đã tìm được phòng khác gần cơ quan hơn, thay đổi kế hoạch chuyển đi...)"
                                class="cancel-modal-textarea"></textarea>
                        </div>
                    </div>

                    <div class="review-modal-footer" style="margin-top: 20px">
                        <button @click="closeCancelModal" class="btn-review-cancel">
                            Hủy bỏ
                        </button>
                        <button type="button" @click="executeCancelInterest"
                            class="btn-review-submit btn-submit-cancel">
                            <i class="bi bi-send-fill"></i> Gửi Yêu Cầu Hủy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- AI Alternative Rooms Modal -->
    <Teleport to="body">
        <div v-if="showAiAlternativesModal" class="ai-modal-overlay" @click.self="closeAiAlternativesModal">
            <div class="ai-modal-box">
                <!-- Modal Header -->
                <div class="ai-modal-header">
                    <div class="ai-header-left">
                        <div class="ai-header-avatar-box">
                            <i class="bi bi-robot" style="font-size: 24px; color: #ffffff"></i>
                            <span class="ai-sparkle-dot"></span>
                        </div>
                        <div class="ai-header-title-box">
                            <h3>
                                <span>Gợi Ý Phòng Trọ Thay Thế</span>
                                <span class="ai-header-badge"><i class="bi bi-stars"></i> AI Smart</span>
                            </h3>
                            <p>
                                Trợ lý AI Ninh Bình tự động đề xuất 3 phòng
                                tương tự sát nhất
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="closeAiAlternativesModal" class="ai-modal-close-btn" title="Đóng">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="ai-modal-body">
                    <!-- Loading State -->
                    <div v-if="loadingAiAlternatives" class="ai-loading-state">
                        <div class="ai-spinner"></div>
                        <div>
                            <h4 style="
                                    font-size: 15px;
                                    font-weight: 700;
                                    color: #1e293b;
                                    margin: 0 0 4px 0;
                                ">
                                Đang tìm phòng tương tự...
                            </h4>
                            <p style="
                                    font-size: 13px;
                                    color: #64748b;
                                    margin: 0;
                                ">
                                Trợ lý AI đang phân tích kho phòng và chọn 3
                                phòng phù hợp nhất với bạn
                            </p>
                        </div>
                    </div>

                    <!-- Loaded Content -->
                    <template v-else-if="aiAlternativesData">
                        <!-- Viewed Room Summary Banner -->
                        <div class="ai-viewed-summary-card">
                            <div class="ai-viewed-info">
                                <span class="ai-viewed-label">Phòng bạn vừa xem</span>
                                <span class="ai-viewed-title">
                                    🏠
                                    {{
                                        aiAlternativesData.viewed_room
                                            ?.room_number
                                    }}
                                    —
                                    {{
                                        aiAlternativesData.viewed_room
                                            ?.house_name
                                    }}
                                </span>
                                <span class="ai-viewed-sub">
                                    📍
                                    {{
                                        aiAlternativesData.viewed_room?.address
                                    }}
                                    <template v-if="
                                        aiAlternativesData.viewed_room
                                            ?.price_formatted
                                    ">
                                        • 💰
                                        {{
                                            aiAlternativesData.viewed_room
                                                ?.price_formatted
                                        }}
                                    </template>
                                </span>
                            </div>

                            <div v-if="aiAlternativesData.viewed_room?.reason" class="ai-reason-pill">
                                <i class="bi bi-chat-left-quote-fill"></i>
                                <span>Lý do:
                                    <strong>{{
                                        aiAlternativesData.viewed_room?.reason
                                        }}</strong></span>
                            </div>
                        </div>

                        <!-- AI Advice Message -->
                        <div class="ai-message-box">
                            <i class="bi bi-lightbulb-fill"></i>
                            <span>{{ aiAlternativesData.ai_message }}</span>
                        </div>

                        <!-- 3 Room Cards Grid -->
                        <div v-if="
                            aiAlternativesData.rooms &&
                            aiAlternativesData.rooms.length > 0
                        " class="ai-recommendations-grid">
                            <div v-for="room in aiAlternativesData.rooms" :key="'ai-rec-' + room.id"
                                class="ai-room-card">
                                <div class="ai-room-image-box">
                                    <img :src="room.image || '/anh/phong1.jpg'" :alt="room.title" class="ai-room-img"
                                        @error="
                                            $event.target.src =
                                            '/anh/phong1.jpg'
                                            " />
                                    <span class="ai-room-occupancy-badge" :class="room.has_residents
                                            ? 'has-people'
                                            : 'empty'
                                        ">
                                        <i :class="room.has_residents
                                                ? 'bi bi-person-check-fill'
                                                : 'bi bi-door-open-fill'
                                            "></i>
                                        {{ room.status_label }}
                                    </span>
                                </div>

                                <div class="ai-room-content">
                                    <h4 class="ai-room-title" :title="room.title">
                                        {{ room.title }}
                                    </h4>
                                    <div class="ai-room-price">
                                        {{ room.price_formatted }}
                                    </div>
                                    <div class="ai-room-meta-tags">
                                        <span v-if="room.floor" class="ai-meta-tag"><i class="bi bi-layers-fill"></i>
                                            {{ room.floor }}</span>
                                        <span v-if="room.area" class="ai-meta-tag"><i
                                                class="bi bi-aspect-ratio-fill"></i>
                                            {{ room.area }} m²</span>
                                    </div>
                                    <div class="ai-room-address" :title="room.address">
                                        <i class="bi bi-geo-alt-fill text-blue-500"></i>
                                        <span>{{ room.address }}</span>
                                    </div>

                                    <div class="ai-room-actions">
                                        <a :href="room.url" target="_blank" class="btn-ai-view-room">
                                            <span>Xem phòng này</span>
                                            <i class="bi bi-arrow-right-short text-lg"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else style="
                                text-align: center;
                                padding: 30px;
                                background: #ffffff;
                                border-radius: 16px;
                                border: 1px dashed #cbd5e1;
                            ">
                            <i class="bi bi-emoji-smile text-3xl text-slate-400"></i>
                            <p style="
                                    margin: 10px 0 0 0;
                                    color: #64748b;
                                    font-size: 14px;
                                ">
                                Hiện chưa có thêm phòng phù hợp khác trong cùng
                                phân khúc.
                            </p>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="ai-modal-footer">
                    <Link :href="route('timtro')" class="btn-ai-footer-explore">
                        <i class="bi bi-search"></i> Khám phá thêm trên bản đồ
                        Tìm Trọ
                    </Link>
                    <button type="button" @click="closeAiAlternativesModal" class="btn-ai-footer-close">
                        Đóng
                    </button>
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
