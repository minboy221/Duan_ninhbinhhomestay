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
                            <tr v-for="apt in appointments" :key="apt.id" style="border-bottom: 1px solid #f1f5f9">
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
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Leaflet Routing Map container -->
                <!-- Modal Cẩm nang hướng dẫn đến xem phòng -->
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
                                <div class="guide-info-card directions-card" :class="{ 'no-guide': !(activeGuideAppointment.room?.boardingHouse?.directions_guide || activeGuideAppointment.room?.boarding_house?.directions_guide) }">
                                    <div class="card-icon amber-icon">
                                        <i class="bi bi-signpost-2-fill"></i>
                                    </div>
                                    <div class="card-content">
                                        <div class="card-label">Chỉ dẫn ngõ ngách</div>
                                        <p v-if="activeGuideAppointment.room?.boardingHouse?.directions_guide || activeGuideAppointment.room?.boarding_house?.directions_guide" class="card-text directions-text">
                                            {{ activeGuideAppointment.room?.boardingHouse?.directions_guide || activeGuideAppointment.room?.boarding_house?.directions_guide }}
                                        </p>
                                        <p v-else class="card-text empty-guide">
                                            Chủ nhà chưa cập nhật chỉ dẫn ngõ ngách. Vui lòng gọi điện thoại để chủ trọ hướng dẫn trực tiếp.
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
                                    <a :href="`tel:${activeGuideAppointment.room?.boardingHouse?.landlord?.phone || activeGuideAppointment.room?.boarding_house?.landlord?.phone}`" class="call-action-btn">
                                        <i class="bi bi-telephone-fill animate-pulse-slow"></i>
                                        <span>Gọi điện</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Footer (Nút chỉ đường Google Maps) -->
                            <div class="guide-modal-footer">
                                <a :href="getGoogleMapsSearchUrl(activeGuideAppointment)" target="_blank" class="btn-google-maps">
                                    <i class="bi bi-map-fill"></i>
                                    <span>📍 Chỉ đường bằng Google Maps</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </Transition>

            </div>
        </div>
    </UserLayout>
</template>

<style scoped>
@import "../../css/user.css";
@import "../../css/responsive/responsivetranguser.css";

/* Guide Modal Styles & Transitions */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .guide-modal-card,
.modal-leave-active .guide-modal-card {
    transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
}

.modal-enter-from .guide-modal-card,
.modal-leave-to .guide-modal-card {
    transform: scale(0.92) translateY(20px);
    opacity: 0;
}

.guide-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(8px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.guide-modal-card {
    background: #ffffff;
    border-radius: 28px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.18);
    overflow: hidden;
    border: 1px solid rgba(241, 245, 249, 0.8);
}

.guide-modal-header {
    padding: 22px 26px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-title-wrapper {
    display: flex;
    align-items: center;
    gap: 14px;
}

.header-icon-badge {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: inset 0 -2px 4px rgba(37, 99, 235, 0.05);
}

.guide-modal-title {
    font-size: 17px;
    font-weight: 850;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.2px;
}

.guide-modal-subtitle {
    font-size: 11.5px;
    color: #64748b;
    margin: 3px 0 0 0;
    font-weight: 600;
}

.guide-close-btn {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    font-size: 14px;
    cursor: pointer;
    color: #64748b;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.guide-close-btn:hover {
    background: #fee2e2;
    color: #ef4444;
    border-color: #fca5a5;
    transform: rotate(90deg);
}

.guide-modal-body {
    padding: 26px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

/* Info Cards */
.guide-info-card {
    display: flex;
    gap: 16px;
    padding: 18px;
    border-radius: 20px;
    transition: all 0.25s ease;
}

.card-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.blue-icon {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #dbeafe;
}

.amber-icon {
    background: #fffbeb;
    color: #d97706;
    border: 1px solid #fef3c7;
}

.card-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.card-label {
    font-size: 11px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.card-text {
    font-size: 14px;
    font-weight: 700;
    color: #334155;
    margin: 0;
    line-height: 1.5;
}

/* Address Card Specific */
.address-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
}

/* Directions Card Specific */
.directions-card {
    background: linear-gradient(135deg, #fffbeb 0%, #fffbeb 60%, #fef3c7 100%);
    border: 1px solid #fef3c7;
    box-shadow: 0 4px 6px -1px rgba(217, 119, 6, 0.02);
}

.directions-card.no-guide {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
}

.directions-card.no-guide .card-icon {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.directions-text {
    color: #78350f;
}

.empty-guide {
    color: #64748b;
    font-style: italic;
    font-weight: 600;
}

/* Contact Card Specific */
.contact-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
    align-items: center;
    gap: 14px;
}

.contact-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    border: 1px solid #e2e8f0;
}

.contact-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.contact-name {
    font-size: 14.5px;
    font-weight: 850;
    color: #0f172a;
}

.contact-phone {
    font-size: 12.5px;
    color: #64748b;
    font-weight: 700;
}

.call-action-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    border: none;
    padding: 10px 18px;
    border-radius: 14px;
    font-size: 12.5px;
    font-weight: 800;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.22);
    transition: all 0.25s ease;
}

.call-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
    filter: brightness(1.05);
}

.animate-pulse-slow {
    animation: pulseSlow 2s infinite;
}

@keyframes pulseSlow {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

/* Footer & Button maps */
.guide-modal-footer {
    padding: 18px 26px 26px;
    border-top: 1px solid #f1f5f9;
    background: #ffffff;
}

.btn-google-maps {
    width: 100%;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #ffffff;
    border: none;
    padding: 14px;
    border-radius: 16px;
    font-size: 13.5px;
    font-weight: 800;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.25);
    transition: all 0.25s ease;
}

.btn-google-maps:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.35);
    filter: brightness(1.05);
}

.status-badge {
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.today-badge {
    background-color: #ef4444;
    color: white;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 9.5px;
    font-weight: 700;
    margin-left: 4px;
    display: inline-block;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.05);
    }

    100% {
        transform: scale(1);
    }
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-view {
    background-color: #f1f5f9;
    color: #475569;
}

.btn-view:hover {
    background-color: #cbd5e1;
    color: #1e293b;
}

.btn-map {
    background-color: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

.btn-map:hover {
    background-color: #2563eb;
    color: #ffffff;
}

.btn-directions-google:hover {
    background-color: #0f4f7a;
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Spinner styling */
.spinner {
    border: 3px solid #cbd5e1;
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    animation: spin 1s linear infinite;
    display: inline-block;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

/* Favorites styles */
.btn-fav {
    background-color: #fff1f2;
    color: #f43f5e;
    border: 1px solid #fecdd3;
}

.btn-fav:hover {
    background-color: #f43f5e;
    color: #ffffff;
}

.text-red {
    color: #ef4444 !important;
}

/* Survey Card styling */
.survey-prompt-card {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 12px 20px;
    transition: all 0.3s;
}

.survey-prompt-card.favorited {
    background-color: #fff1f2;
    border-bottom-color: #ffe4e6;
}

.survey-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.survey-text-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
}

.survey-prompt-card.favorited .survey-text-wrapper {
    color: #9f1239;
}

.pulse-icon {
    animation: heartbeat 1.5s infinite;
}

@keyframes heartbeat {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.15);
    }

    100% {
        transform: scale(1);
    }
}

.btn-survey-favorite {
    padding: 6px 14px;
    border-radius: 8px;
    border: 1px solid #fecdd3;
    background: #fff;
    color: #f43f5e;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-survey-favorite:hover {
    background: #fff1f2;
}

.btn-survey-favorite.active {
    background: #f43f5e;
    color: #fff;
    border-color: #f43f5e;
}

.btn-survey-favorite.active:hover {
    background: #e11d48;
}
</style>
