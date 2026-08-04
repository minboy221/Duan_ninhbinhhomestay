<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUnmounted } from "vue";

const search = ref("");
const typeFilter = ref("all");
const statusFilter = ref("all");
const props = defineProps({
    reports: {
        type: Array,
        default: () => [],
    },
    negotiationDays: {
        type: Number,
        default: 2,
    },
});

const settingsForm = useForm({
    days: props.negotiationDays,
});

function saveSettings() {
    settingsForm.post(route("admin.reports.update-days"), {
        preserveScroll: true,
        onSuccess: () => {
            alert("Cập nhật thời hạn thương lượng thành công!");
        },
    });
}

const typeMap = {
    tin_ao: { label: "Tin ảo", class: "type-orange" },
    ghosting: { label: "Ghosting", class: "type-red" },
    lua_dao: { label: "Lừa đảo", class: "type-red" },
    khac: { label: "Khác", class: "type-gray" },
};
const statusMap = {
    pending: { label: "Chờ xử lý", class: "s-orange" },
    resolved: { label: "Đã xử lý", class: "s-green" },
    ignored: { label: "Bỏ qua", class: "s-gray" },
};

const filtered = computed(() =>
    props.reports.filter((r) => {
        const q = search.value.toLowerCase();
        const mSearch =
            !q ||
            r.from.toLowerCase().includes(q) ||
            r.target.toLowerCase().includes(q);
        const mType =
            typeFilter.value === "all" ||
            (typeFilter.value === "tin_ao" &&
                r.reason.toLowerCase().includes("ảo")) ||
            (typeFilter.value === "lua_dao" &&
                r.reason.toLowerCase().includes("lừa đảo")) ||
            (typeFilter.value === "ghosting" &&
                r.reason.toLowerCase().includes("chủ trọ")) ||
            (r.reason &&
                r.reason
                    .toLowerCase()
                    .includes(typeFilter.value.toLowerCase()));
        const mStatus =
            statusFilter.value === "all" || r.status === statusFilter.value;
        return mSearch && mType && mStatus;
    }),
);

const currentPage = ref(1);
const perPage = 10;

watch([search, typeFilter, statusFilter], () => {
    currentPage.value = 1;
});

const paginatedFiltered = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filtered.value.slice(start, start + perPage);
});

const updateForm = useForm({
    status: "",
    admin_note: "",
});
const showModal = ref(false);
const selected = ref(null);
const adminNote = ref("");
const action = ref("");

function openReport(r) {
    selected.value = r;
    adminNote.value = r.note;
    showModal.value = true;
}
function handleAction(act) {
    //ánh xạ hành động sang trạng thái database
    const statusMapAction = {
        resolve: "resolved",
        ignore: "ignored",
    };

    updateForm.status = statusMapAction[act] || "resolved";
    updateForm.admin_note = adminNote.value;

    updateForm.patch(route("admin.reports.update", selected.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            alert("xử lý báo cáo thành công!");
        },
        onError: (errors) => {
            alert(Object.values(errors).join("\n"));
        },
    });
}

// Trạng thái và hàm Zoom ảnh
const showZoomModal = ref(false);
const zoomImageUrl = ref("");

function zoomImage(url) {
    zoomImageUrl.value = url;
    showZoomModal.value = true;
}

onMounted(() => {
    window.Echo.channel("reports").listen("ReportUpdated", (e) => {
        if (selectedReport.value && selectedReport.value.id === e.report.id) {
            selectedReport.value = {
                ...selectedReport.value,
                ...e.report,
            };
        }
        //dành cho admin
        if (
            typeof selected !== "undefined" &&
            selected.value &&
            selected.value.id === e.report.id
        ) {
            selected.value = { ...selected.value, ...e.report };
        }
        //cập nhật trạng thái hiển thị
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
    window.Echo.leaveChannel('reports');
})
</script>

<template>

    <Head title="Admin - Báo Cáo & Khiếu Nại" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Báo Cáo & Khiếu Nại</h1>
                <p class="page-sub">Tiếp nhận và xử lý vi phạm từ người dùng</p>
            </div>
        </template>

        <!-- Cấu hình thời hạn thương lượng -->
        <div class="settings-box mb-6 p-4 bg-white rounded-lg border border-slate-200 flex justify-between items-center"
            style="
                background: #fff;
                padding: 16px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            ">
            <div>
                <h3 class="font-bold text-slate-800 text-sm" style="margin: 0; font-size: 14px; font-weight: 700">
                    Hạn Tự Thương Lượng Của Hệ Thống
                </h3>
                <p class="text-xs text-slate-400" style="margin: 4px 0 0 0; font-size: 11px; color: #94a3b8">
                    Số ngày tối đa để Chủ trọ và Khách thuê tự thương lượng khắc
                    phục trước khi Admin can thiệp.
                </p>
            </div>
            <div class="flex items-center gap-2" style="display: flex; align-items: center; gap: 8px">
                <input v-model="settingsForm.days" type="number" min="1" max="30"
                    class="border rounded p-1.5 w-20 text-center font-bold" style="
                        width: 80px;
                        padding: 6px;
                        text-align: center;
                        border: 1px solid #cbd5e1;
                        border-radius: 6px;
                    " />
                <button @click="saveSettings"
                    class="px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded hover:bg-indigo-700 transition"
                    :disabled="settingsForm.processing" style="
                        background: #4f46e5;
                        color: #fff;
                        padding: 8px 16px;
                        border: none;
                        border-radius: 6px;
                        font-size: 12px;
                        font-weight: 700;
                        cursor: pointer;
                    ">
                    Lưu cấu hình
                </button>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="summary-row">
            <div class="sum-card sum-orange">
                <i class="bi bi-hourglass-split"></i>
                <div>
                    <p class="sum-num">
                        {{
                            props.reports.filter((r) => r.status === "pending")
                                .length
                        }}
                    </p>
                    <p class="sum-lbl">Chờ xử lý</p>
                </div>
            </div>
            <div class="sum-card sum-green">
                <i class="bi bi-check-circle-fill"></i>
                <div>
                    <p class="sum-num">
                        {{
                            props.reports.filter((r) => r.status === "resolved")
                                .length
                        }}
                    </p>
                    <p class="sum-lbl">Đã xử lý</p>
                </div>
            </div>
            <div class="sum-card sum-gray">
                <i class="bi bi-dash-circle-fill"></i>
                <div>
                    <p class="sum-num">
                        {{
                            props.reports.filter((r) => r.status === "ignored")
                                .length
                        }}
                    </p>
                    <p class="sum-lbl">Bỏ qua</p>
                </div>
            </div>
            <div class="sum-card sum-blue">
                <i class="bi bi-flag-fill"></i>
                <div>
                    <p class="sum-num">{{ props.reports.length }}</p>
                    <p class="sum-lbl">Tổng cộng</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-bar">
            <div class="search-wrap">
                <i class="bi bi-search si"></i>
                <input v-model="search" type="text" placeholder="Tìm người báo cáo, đối tượng..."
                    class="search-input" />
            </div>
            <select v-model="typeFilter" class="filter-select">
                <option value="all">Tất cả loại</option>
                <option value="tin_ao">Tin ảo</option>
                <option value="ghosting">Ghosting</option>
                <option value="lua_dao">Lừa đảo</option>
            </select>
            <select v-model="statusFilter" class="filter-select">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending">Chờ xử lý</option>
                <option value="resolved">Đã xử lý</option>
                <option value="ignored">Bỏ qua</option>
            </select>
        </div>

        <!-- Table -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Người báo cáo</th>
                        <th>Đối tượng bị báo cáo</th>
                        <th>Loại vi phạm</th>
                        <th>Ngày</th>
                        <th>Trạng thái</th>
                        <th style="text-align: center">Xử lý</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!filtered.length">
                        <td colspan="7" class="empty-row">
                            <i class="bi bi-inbox"></i>
                            <p>Không có báo cáo nào</p>
                        </td>
                    </tr>
                    <tr v-for="(r, i) in paginatedFiltered" :key="r.id" class="trow">
                        <td class="idx">
                            {{ (currentPage - 1) * perPage + i + 1 }}
                        </td>
                        <td>
                            <p class="fw">{{ r.from }}</p>
                            <p class="sm-gray">{{ r.fromEmail }}</p>
                        </td>
                        <td class="sm-target">{{ r.target }}</td>
                        <td>
                            <span class="type-badge type-gray">{{
                                r.reason
                                }}</span>
                        </td>
                        <td class="sm-gray">{{ r.date }}</td>
                        <td>
                            <span :class="[
                                'status-chip',
                                statusMap[r.status]?.class,
                            ]">{{ statusMap[r.status]?.label }}</span>
                        </td>
                        <td style="text-align: center">
                            <button @click="openReport(r)" class="act-btn" :class="r.status === 'pending'
                                    ? 'act-primary'
                                    : 'act-view'
                                ">
                                <i :class="[
                                    'bi',
                                    r.status === 'pending'
                                        ? 'bi-clipboard2-check'
                                        : 'bi-eye',
                                ]"></i>
                                {{ r.status === "pending" ? "Xử lý" : "Xem" }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Phân trang client-side cho admin -->
            <div class="flex justify-center items-center gap-1.5 mt-4 p-4 border-t border-slate-100"
                v-if="filtered.length > perPage" style="
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    gap: 6px;
                    padding: 16px;
                    border-top: 1px solid #f1f5f9;
                    background: #fff;
                ">
                <button @click="currentPage > 1 && currentPage--" :disabled="currentPage === 1"
                    class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold transition bg-white"
                    :style="currentPage === 1
                            ? 'color: #94a3b8; cursor: not-allowed; background: #f8fafc;'
                            : 'color: #334155; cursor: pointer;'
                        ">
                    Trước
                </button>

                <span class="text-xs text-slate-500 font-semibold mx-2">
                    Trang {{ currentPage }} /
                    {{ Math.ceil(filtered.length / perPage) }}
                </span>

                <button @click="
                    currentPage < Math.ceil(filtered.length / perPage) &&
                    currentPage++
                    " :disabled="currentPage === Math.ceil(filtered.length / perPage)
                        " class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold transition bg-white"
                    :style="currentPage === Math.ceil(filtered.length / perPage)
                            ? 'color: #94a3b8; cursor: not-allowed; background: #f8fafc;'
                            : 'color: #334155; cursor: pointer;'
                        ">
                    Sau
                </button>
            </div>
        </div>

        <!-- Modal xử lý -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
                <div class="modal-box">
                    <div class="modal-header">
                        <h3>Xử Lý Báo Cáo #{{ selected?.id }}</h3>
                        <button @click="showModal = false" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto">
                        <div class="info-block">
                            <div class="ib-row">
                                <span class="ib-l">Người BC</span><span class="ib-v">{{ selected?.from }} ({{
                                    selected?.fromEmail
                                }})</span>
                            </div>
                            <div class="ib-row">
                                <span class="ib-l">Đối tượng</span><span class="ib-v font-bold text-indigo-600">{{
                                    selected?.target
                                    }}</span>
                            </div>
                            <div class="ib-row">
                                <span class="ib-l">Lý do</span><span class="ib-v"><span
                                        class="type-badge type-orange">{{
                                            selected?.reason
                                        }}</span></span>
                            </div>
                            <div class="ib-row">
                                <span class="ib-l">Trạng thái</span><span class="ib-v"><span :class="[
                                    'status-chip',
                                    statusMap[selected?.status]?.class,
                                ]">{{
                                            statusMap[selected?.status]?.label
                                        }}</span></span>
                            </div>
                            <div class="ib-row" v-if="selected?.negotiation_deadline">
                                <span class="ib-l">Hạn thương lượng</span><span class="ib-v text-slate-500">{{
                                    selected?.negotiation_deadline
                                    }}</span>
                            </div>
                        </div>

                        <!-- Nội dung mô tả chi tiết của khách thuê -->
                        <div class="mt-4 border-t pt-3">
                            <label class="form-label text-slate-700">Mô tả sự việc từ khách thuê:</label>
                            <p
                                class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100 whitespace-pre-line">
                                {{
                                    selected?.description ||
                                    "Không có mô tả chi tiết."
                                }}
                            </p>
                        </div>

                        <!-- Ảnh bằng chứng của khách thuê -->
                        <div v-if="
                            selected?.evidence_images &&
                            selected.evidence_images.length
                        " class="mt-3">
                            <label class="form-label text-slate-700">Ảnh minh chứng vi phạm:</label>
                            <div class="flex flex-wrap gap-2 mt-1">
                                <img v-for="(
img, idx
                                    ) in selected.evidence_images" :key="idx" :src="'/storage/' + img"
                                    class="w-20 h-20 object-cover rounded-lg border border-slate-200 cursor-pointer hover:opacity-90"
                                    style="cursor: zoom-in" @click="zoomImage('/storage/' + img)" />
                            </div>
                        </div>

                        <!-- Giải trình từ chủ trọ nếu có -->
                        <div v-if="selected?.target_resolved"
                            class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50/50 p-3">
                            <h4 class="flex items-center gap-1 text-xs font-bold text-emerald-800">
                                <i class="bi bi-chat-left-dots-fill"></i>
                                Giải trình & Khắc phục từ Chủ trọ:
                            </h4>

                            <p class="mt-1.5 whitespace-pre-line text-xs text-slate-700">
                                {{
                                    selected?.response_note ||
                                    "Chủ trọ xác nhận đã khắc phục sự cố."
                                }}
                            </p>

                            <!-- Ảnh khắc phục của chủ trọ -->
                            <div v-if="selected?.response_evidence?.length" class="mt-3">
                                <span class="text-[11px] font-semibold text-emerald-700">
                                    Ảnh chứng cứ đã khắc phục:
                                </span>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    <img v-for="(
img, idx
                                        ) in selected.response_evidence" :key="idx" :src="`/storage/${img}`"
                                        class="h-16 w-16 cursor-zoom-in rounded-lg border border-emerald-200 object-cover transition-opacity hover:opacity-90"
                                        @click="zoomImage(`/storage/${img}`)" />
                                </div>
                            </div>
                        </div>

                        <!-- Phần ghi chú của Admin -->
                        <div class="mt-4 border-t pt-3">
                            <label class="form-label mt-2">Ghi chú xử lý của Admin:</label>
                            <textarea v-model="adminNote" class="form-textarea" rows="3"
                                placeholder="Ghi chú kết quả xử lý..."
                                :disabled="selected?.status !== 'pending'"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer" v-if="selected?.status === 'pending'">
                        <button @click="showModal = false" class="btn-cancel">
                            Hủy
                        </button>
                        <button @click="handleAction('ignore')" class="btn-ignore">
                            <i class="bi bi-dash-circle"></i> Bỏ qua
                        </button>
                        <button @click="handleAction('resolve')" class="btn-resolve">
                            <i class="bi bi-check-circle-fill"></i> Đã xử lý
                        </button>
                    </div>
                    <div class="modal-footer" v-else>
                        <button @click="showModal = false" class="btn-cancel" style="flex: 1">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Phóng To Ảnh -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                <div v-if="showZoomModal"
                    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/90 backdrop-blur-md p-4"
                    @click.self="showZoomModal = false">
                    <div class="relative flex max-h-[90vh] w-full max-w-6xl items-center justify-center">
                        <!-- Nút đóng -->
                        <button @click="showZoomModal = false"
                            class="absolute top-4 right-4 z-20 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-md transition-all duration-200 hover:scale-110 hover:bg-white/20">
                            <i class="bi bi-x-lg text-xl"></i>
                        </button>

                        <!-- Ảnh -->
                        <img :src="zoomImageUrl" alt="Zoom Image"
                            class="max-h-[88vh] max-w-full rounded-2xl border border-white/10 bg-white/5 object-contain shadow-[0_25px_80px_rgba(0,0,0,0.6)] transition-transform duration-300" />
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.page-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.page-sub {
    font-size: 12px;
    color: #94a3b8;
    margin: 2px 0 0;
}

.summary-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 18px;
}

.sum-card {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.sum-card i {
    font-size: 26px;
    flex-shrink: 0;
}

.sum-orange i,
.sum-orange .sum-num {
    color: #f97316;
}

.sum-green i,
.sum-green .sum-num {
    color: #22c55e;
}

.sum-gray i,
.sum-gray .sum-num {
    color: #94a3b8;
}

.sum-blue i,
.sum-blue .sum-num {
    color: #3b82f6;
}

.sum-num {
    font-size: 24px;
    font-weight: 800;
    margin: 0;
    line-height: 1;
}

.sum-lbl {
    font-size: 11px;
    color: #94a3b8;
    margin: 0;
}

.filter-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.search-wrap {
    position: relative;
    flex: 1;
    min-width: 180px;
}

.si {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}

.search-input {
    width: 100%;
    padding: 9px 12px 9px 36px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
}

.search-input:focus {
    border-color: #7c3aed;
}

.filter-select {
    padding: 9px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 13px;
    color: #334155;
    background: #fff;
    outline: none;
}

.table-card {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.data-table th {
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 13px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}

.data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}

.trow:last-child td {
    border-bottom: none;
}

.trow:hover td {
    background: #fafbff;
}

.idx {
    color: #cbd5e1;
    font-size: 12px;
    font-weight: 600;
}

.fw {
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}

.sm-gray {
    font-size: 11px;
    color: #94a3b8;
    margin: 0;
}

.sm-target {
    color: #334155;
    font-size: 12px;
    max-width: 200px;
}

.empty-row {
    text-align: center;
    padding: 48px !important;
    color: #94a3b8;
}

.empty-row i {
    display: block;
    font-size: 40px;
    margin-bottom: 8px;
}

.type-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 99px;
}

.type-orange {
    background: #fff7ed;
    color: #ea580c;
}

.type-red {
    background: #fef2f2;
    color: #dc2626;
}

.type-gray {
    background: #f1f5f9;
    color: #64748b;
}

.status-chip {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 99px;
}

.s-orange {
    background: #fff7ed;
    color: #ea580c;
}

.s-green {
    background: #f0fdf4;
    color: #16a34a;
}

.s-gray {
    background: #f1f5f9;
    color: #64748b;
}

.act-btn {
    padding: 7px 12px;
    border-radius: 6px;
    border: none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.act-primary {
    background: #7c3aed;
    color: #fff;
}

.act-primary:hover {
    background: #6d28d9;
}

.act-view {
    background: #f1f5f9;
    color: #64748b;
}

.act-view:hover {
    background: #e2e8f0;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(3px);
}

.modal-box {
    background: #fff;
    border-radius: 10px;
    width: 480px;
    max-width: 92vw;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid #f1f5f9;
}

.modal-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.modal-close {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 18px 22px;
}

.info-block {
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ib-row {
    display: flex;
    gap: 12px;
    font-size: 13px;
}

.ib-l {
    width: 90px;
    color: #94a3b8;
    font-weight: 500;
    flex-shrink: 0;
}

.ib-v {
    color: #0f172a;
    font-weight: 500;
}

.form-label {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    display: block;
    margin-bottom: 6px;
}

.mt-4 {
    margin-top: 14px;
}

.form-textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 13px;
    resize: none;
    outline: none;
    box-sizing: border-box;
}

.form-textarea:focus {
    border-color: #7c3aed;
}

.modal-footer {
    display: flex;
    gap: 8px;
    padding: 14px 22px;
    border-top: 1px solid #f1f5f9;
}

.btn-cancel {
    flex: 1;
    padding: 9px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.btn-ignore {
    flex: 1;
    padding: 9px;
    border-radius: 6px;
    border: none;
    background: #f1f5f9;
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.btn-resolve {
    flex: 2;
    padding: 9px;
    border-radius: 6px;
    border: none;
    background: #22c55e;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.btn-resolve:hover {
    background: #16a34a;
}
</style>
