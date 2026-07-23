<script setup>
import UserLayout from "@/Layouts/UserLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    reports: {
        type: Object,
        default: () => ({ data: [] }),
    },
});

const showDetailModal = ref(false);
const selectedReport = ref(null);

const resolveForm = useForm({
    action: "",
    response_note: "",
});

const statusMap = {
    pending: { label: "Đang chờ duyệt", class: "status-pending" },
    investigating: {
        label: "Đang thương lượng",
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
            alert("Cập nhật trạng thái thành công!");
        },
        onError: (err) => {
            alert(Object.values(err).join("\n"));
        },
    });
}
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
                        Theo dõi tiến trình xử lý và tự thương lượng các khiếu
                        nại của bạn.
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
                                            r.reportable_type ===
                                                "App\\Models\\Room"
                                                ? "Phòng trọ"
                                                : "Hóa đơn"
                                        }}
                                        #{{ r.reportable_id }}
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500">
                                    {{
                                        new Date(
                                            r.created_at,
                                        ).toLocaleDateString("vi-VN")
                                    }}
                                </td>
                                <td class="text-center">
                                    <span :class="[
                                        'status-badge',
                                        statusMap[r.status]?.class,
                                    ]">
                                        {{ statusMap[r.status]?.label }}
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

                <!-- Modal xem chi tiết và tự xử lý thương lượng -->
                <div v-if="showDetailModal && selectedReport" class="modal-backdrop">
                    <div class="modal-content-box">
                        <div class="modal-header-custom">
                            <h3>
                                <i class="bi bi-flag-fill text-indigo-500"></i>
                                Chi Tiết Báo Cáo #{{ selectedReport.id }}
                            </h3>
                            <button @click="showDetailModal = false" class="btn-close">
                                &times;
                            </button>
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
                            <div v-if="
                                selectedReport.evidence_images &&
                                selectedReport.evidence_images.length
                            " class="info-field">
                                <span class="field-label">Ảnh bằng bằng chứng</span>
                                <div class="image-grid mt-2">
                                    <img v-for="(
img, index
                                        ) in selectedReport.evidence_images" :key="index" :src="'/storage/' + img"
                                        class="evidence-image" />
                                </div>
                            </div>

                            <!-- Phần phản hồi từ bên bị báo cáo khi ở trạng thái 'investigating' -->
                            <div v-if="selectedReport.status === 'investigating'" class="negotiation-box">
                                <h4>
                                    <i class="bi bi-chat-left-dots-fill text-indigo-600"></i>
                                    Phản Hồi Từ Đối Tác / Chủ Trọ
                                </h4>
                                <p class="response-note">
                                    {{
                                        selectedReport.response_note ||
                                        "Chủ trọ xác nhận đã khắc phục sự cố."
                                    }}
                                </p>

                                <!-- Ảnh chứng cứ khắc phục -->
                                <div v-if="
                                    selectedReport.response_evidence &&
                                    selectedReport.response_evidence.length
                                " class="mt-3">
                                    <span class="field-label text-indigo-600">Ảnh chứng minh đã khắc phục:</span>
                                    <div class="image-grid mt-1">
                                        <img v-for="(
img, idx
                                            ) in selectedReport.response_evidence" :key="idx" :src="'/storage/' + img"
                                            class="evidence-image border-indigo-200" />
                                    </div>
                                </div>

                                <!-- Nút để Người báo cáo tự quyết định (Thương thảo) -->
                                <div class="action-buttons-grid">
                                    <button @click="
                                        handleSelfResolve('escalate_admin')
                                        " class="btn-escalate">
                                        <i class="bi bi-shield-exclamation"></i>
                                        Yêu Cầu Admin Can Thiệp
                                    </button>
                                    <button @click="
                                        handleSelfResolve('reporter_accept')
                                        " class="btn-accept">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Đồng Ý & Đóng Báo Cáo
                                    </button>
                                </div>
                            </div>

                            <div v-if="selectedReport.admin_note" class="admin-box">
                                <span class="field-label text-emerald-700">Ghi chú xử lý từ Admin</span>
                                <p class="admin-note-text">
                                    {{ selectedReport.admin_note }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </UserLayout>
</template>
<style scoped>
@import "../../css/listbaocao.css";
</style>
