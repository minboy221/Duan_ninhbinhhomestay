<script setup>
import UserLayout from "@/Layouts/UserLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    reports: {
        type: Object,
        default: () => ({ data: [] }),
    },
});

onMounted(() => {
    window.Echo.channel("reports").listen("ReportUpdated", (e) => {
        if (selectedReport.value && selectedReport.value.id === e.report.id) {
            selectedReport.value = {
                ...selectedReport.value,
                ...e.report,
            };
        }
        // Cập nhật lại item trong danh sách
        const reportList = props.reports?.data || props.reports || [];
        const index = reportList.findIndex((r) => r.id === e.report.id);
        if (index !== -1) {
            reportList[index] = {
                ...reportList[index],
                ...e.report,
            };
        }
    });
});

onUnmounted(() => {
    window.Echo.leaveChannel("reports");
});

const showDetailModal = ref(false);
const selectedReport = ref(null);

const resolveForm = useForm({
    action: "",
    response_note: "",
});

const statusMap = {
    pending: { label: "Đang chờ Admin duyệt", class: "status-pending" },
    investigating: {
        label: "Đang giải quyết",
        class: "status-investigating",
    },
    resolved: { label: "Đã giải quyết", class: "status-resolved" },
    rejected: { label: "Đã từ chối", class: "status-rejected" },
};

function openDetail(report) {
    selectedReport.value = report;
    showDetailModal.value = true;
}

function handleSelfResolve(actionType) {
    if (
        actionType === "reporter_accept" &&
        !confirm("Bạn đồng ý đóng báo cáo này vì đã giải quyết xong?")
    ) {
        return;
    }
    if (
        actionType === "escalate_admin" &&
        !confirm("Bạn muốn chuyển báo cáo này lên Admin giải quyết?")
    ) {
        return;
    }

    resolveForm.action = actionType;
    resolveForm.post(route("reports.self-resolve", selectedReport.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDetailModal.value = false;
        },
        onError: (err) => {
            alert(Object.values(err).join("\n"));
        },
    });
}

// Xử lý Phóng To Ảnh (Zoom)
const showZoomModal = ref(false);
const zoomImageUrl = ref("");

function zoomImage(url) {
    zoomImageUrl.value = url;
    showZoomModal.value = true;
}

// Helper chuẩn hóa đường dẫn ảnh (Hỗ trợ cả Cloud R2 & Local Storage)
const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return path.startsWith('/') ? '/storage' + path : '/storage/' + path;
};
</script>

<template>
    <Head title="Lịch Sử Báo Cáo | Ninh Bình HomeStay" />
    <UserLayout>
        <div class="bao_item">
            <div class="infor_noidung pb-8">
                <div class="title_noidung mb-6">
                    <h2 class="text-xl font-bold text-slate-800">
                        Lịch Sử Báo Cáo Vi Phạm
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Theo dõi tiến trình xử lý và tự thương lượng các khiếu nại của bạn.
                    </p>
                </div>

                <!-- Danh sách báo cáo -->
                <div class="table-container mt-4">
                    <table class="data-table-custom">
                        <thead>
                            <tr>
                                <th>Lý do báo cáo</th>
                                <th>Đối tượng</th>
                                <th>Ngày gửi</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="reports.data.length === 0">
                                <td colspan="5" class="empty-row text-center text-slate-400 py-8 font-semibold">
                                    <i class="bi bi-inbox text-3xl block mb-2 text-slate-300"></i>
                                    Bạn chưa gửi báo cáo vi phạm nào.
                                </td>
                            </tr>
                            <tr v-for="r in reports.data" :key="r.id" class="trow">
                                <td class="font-medium text-slate-800">
                                    {{ r.reason }}
                                </td>
                                <td class="text-xs text-slate-600">
                                    <span class="type-pill">
                                        {{
                                            r.reportable_type === "App\\Models\\Room"
                                                ? "Phòng " + (r.reportable?.room_number || r.reportable_id)
                                                : "Hóa đơn #" + (r.reportable?.invoice_code || r.reportable_id)
                                        }}
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500">
                                    {{ new Date(r.created_at).toLocaleDateString("vi-VN") }}
                                </td>
                                <td class="text-center">
                                    <span :class="['status-badge', statusMap[r.status]?.class]">
                                        {{ statusMap[r.status]?.label || r.status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button @click="openDetail(r)" class="btn-view">
                                        <i class="bi bi-eye"></i> Xem Chi Tiết
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <Pagination :links="reports.links" />
            </div>
        </div>
    </UserLayout>

    <!-- Modal xem chi tiết và tự xử lý thương lượng -->
    <div v-if="showDetailModal && selectedReport" class="modal-backdrop">
        <div class="modal-content-box">
            <div class="modal-header-custom">
                <h3>
                    <i class="bi bi-flag-fill text-indigo-500"></i>
                    {{
                        selectedReport.reportable_type === "App\\Models\\Room"
                            ? "Chi Tiết Báo Cáo Phòng " + (selectedReport.reportable?.room_number || selectedReport.reportable_id)
                            : "Chi Tiết Báo Cáo Hóa Đơn #" + (selectedReport.reportable?.invoice_code || selectedReport.reportable_id)
                    }}
                </h3>
                <button @click="showDetailModal = false" class="btn-close">&times;</button>
            </div>

            <div class="modal-body-custom">
                <div class="info-field">
                    <span class="field-label">Lý do báo cáo</span>
                    <p class="field-value font-semibold text-slate-800">
                        {{ selectedReport.reason }}
                    </p>
                </div>

                <div v-if="selectedReport.description" class="info-field">
                    <span class="field-label">Mô tả thêm</span>
                    <p class="field-value text-slate-600">
                        {{ selectedReport.description }}
                    </p>
                </div>

                <!-- Chứng cứ gửi kèm từ người báo cáo -->
                <div v-if="selectedReport.evidence_images && selectedReport.evidence_images.length" class="info-field">
                    <span class="field-label">Ảnh bằng chứng từ bạn</span>
                    <div class="image-grid mt-2">
                        <img v-for="(img, index) in selectedReport.evidence_images" :key="index" :src="getImageUrl(img)"
                            class="evidence-image cursor-pointer hover:opacity-95 transition-opacity"
                            style="cursor: zoom-in" @click="zoomImage(getImageUrl(img))" />
                    </div>
                </div>

                <!-- Luồng thương lượng giải quyết sự cố -->
                <div v-if="selectedReport.status === 'investigating'" class="negotiation-box mt-4">
                    <!-- TRƯỜNG HỢP 1: CHỦ TRỌ CHƯA KHẮC PHỤC / CHƯA PHẢN HỒI -->
                    <div v-if="!selectedReport.target_resolved" class="text-center py-3">
                        <p class="text-xs font-bold text-amber-700 mb-1">
                            <i class="bi bi-hourglass-split"></i> Đang chờ Chủ trọ kiểm tra và khắc phục sự cố...
                        </p>
                        <p class="text-[11px] text-slate-400 mb-3">
                            Chủ trọ có thời gian để xử lý trực tiếp. Nếu quá hạn hoặc không xử lý, bạn có thể chuyển lên Admin.
                        </p>

                        <button @click="handleSelfResolve('escalate_admin')" class="btn-escalate">
                            <i class="bi bi-shield-exclamation"></i> Chuyển Ban Quản Trị / Admin Can Thiệp
                        </button>
                    </div>

                    <!-- TRƯỜNG HỢP 2: CHỦ TRỌ ĐÃ PHẢN HỒI & KHẮC PHỤC -->
                    <div v-else>
                        <h4 class="font-bold text-indigo-700 mb-1">
                            <i class="bi bi-chat-left-dots-fill text-indigo-600"></i> Kết Quả Khắc Phục Từ Chủ Trọ
                        </h4>
                        <p class="response-note text-xs bg-indigo-50 p-2.5 rounded-lg border border-indigo-100 text-slate-700">
                            {{ selectedReport.response_note || "Chủ trọ xác nhận đã khắc phục sự cố." }}
                        </p>

                        <!-- Ảnh chứng cứ khắc phục từ Chủ trọ -->
                        <div v-if="selectedReport.response_evidence && selectedReport.response_evidence.length" class="mt-3">
                            <span class="field-label text-indigo-600">Ảnh chứng minh Chủ trọ đã khắc phục:</span>
                            <div class="image-grid mt-1">
                                <img v-for="(img, idx) in selectedReport.response_evidence" :key="idx"
                                    :src="getImageUrl(img)" class="evidence-image border-indigo-200"
                                    style="cursor: zoom-in" @click="zoomImage(getImageUrl(img))" />
                            </div>
                        </div>

                        <!-- Cụm 2 nút duyệt dành cho Khách thuê -->
                        <div class="action-buttons-grid mt-4 flex gap-2">
                            <button @click="handleSelfResolve('escalate_admin')" class="btn-escalate flex-1">
                                <i class="bi bi-x-circle-fill"></i> Chưa Hài Lòng - Nhờ Admin
                            </button>
                            <button @click="handleSelfResolve('reporter_accept')" class="btn-accept flex-1">
                                <i class="bi bi-check-circle-fill"></i> Hài Lòng & Đóng Báo Cáo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Ghi chú xử lý từ Admin (nếu có) -->
                <div v-if="selectedReport.admin_note" class="admin-box mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                    <span class="field-label text-emerald-700 font-bold block mb-1">Ghi chú xử lý từ Admin:</span>
                    <p class="admin-note-text text-xs text-emerald-800">
                        {{ selectedReport.admin_note }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Phóng To Ảnh (Zoom) -->
    <Teleport to="body">
        <div v-if="showZoomModal"
            class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/85 backdrop-blur-sm p-4"
            @click.self="showZoomModal = false">
            <div class="relative max-w-4xl max-h-[90vh] w-full flex items-center justify-center">
                <button @click="showZoomModal = false"
                    class="absolute -top-12 right-0 text-white hover:text-gray-300 text-4xl font-light">
                    &times;
                </button>
                <img :src="zoomImageUrl" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl" />
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
@import "../../css/listbaocao.css";
</style>
