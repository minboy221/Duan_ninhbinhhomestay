<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

// 1. Nhận biến logs phân trang từ AdminController gửi sang
const props = defineProps({
    logs: Object,
    filters: Object,
});

const search = ref(props.filters?.search || "");
const typeFilter = ref(props.filters?.type || "all");

//hàm gửi request lọc dữ liệu
function handleFilter() {
    router.get(
        route("admin.auditlog.index"),
        {
            search: search.value,
            type: typeFilter.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

// Bản đồ ánh xạ mã hành động sang màu sắc và icon hiển thị trực quan
const actionMap = {
    login: {
        label: "Đăng nhập",
        icon: "bi-box-arrow-in-right",
        color: "#3b82f6",
    },
    lock_user: {
        label: "Khóa tài khoản",
        icon: "bi-lock-fill",
        color: "#ef4444",
    },
    unlock_user: {
        label: "Mở khóa tài khoản",
        icon: "bi-unlock-fill",
        color: "#22c55e",
    },
    delete_user: {
        label: "Xóa tài khoản",
        icon: "bi-trash-fill",
        color: "#ef4444",
    },
    approve_post: {
        label: "Duyệt tin trọ",
        icon: "bi-check-circle-fill",
        color: "#22c55e",
    },
    reject_post: {
        label: "Từ chối tin trọ",
        icon: "bi-x-circle-fill",
        color: "#f59e0b",
    },
    approve_verification: {
        label: "Duyệt chủ trọ",
        icon: "bi-person-check-fill",
        color: "#22c55e",
    },
    reject_verification: {
        label: "Từ chối chủ trọ",
        icon: "bi-person-x-fill",
        color: "#f59e0b",
    },
    approve_boarding_house: {
        label: "Duyệt cơ sở trọ",
        icon: "bi-building-check",
        color: "#22c55e",
    },
    reject_boarding_house: {
        label: "Từ chối cơ sở trọ",
        icon: "bi-building-exclamation",
        color: "#f59e0b",
    },
    update_website: {
        label: "Cấu hình Website",
        icon: "bi-gear-fill",
        color: "#7c3aed",
    },
    // thêm các hành động duyệt & báo cáo của admin
    resolve_report: {
        lablel: "Giải quyết khiếu nại",
        icon: "bi-shield-check",
        color: "#10b981",
    },
    ignore_report: {
        lable: "Bỏ qua kiếu nại",
        icon: "bi-shield-slash",
        color: "#64748b",
    },
    //thêm các hành động dịch vụ & hoá đơn của chủ trọ
    update_service: {
        label: "Cập nhật dịch vụ",
        icon: "bi-hammer",
        color: "#3b82f6",
    },
    abnormal_service_price: {
        label: "Giá dịch vụ bất thường",
        icon: "bi-exclamation-triangle-fill",
        color: "#ef4444",
    },
    create_invoice: {
        label: "Tạo hoá đơn",
        icon: "bi-receipt-cutoff",
        color: "#3b82f6",
    },
    update_invoice: {
        label: "Cập nhật hoá đơn",
        icon: "bi-receipt",
        color: "#06b6d4",
    },
    abnormal_invoice: {
        label: "Hóa đơn bất thường",
        icon: "bi-exclamation-octagon-fill",
        color: "#ef4444",
    },
    abnormal_invoice_update: {
        label: "Sửa hóa đơn bất thường",
        icon: "bi-exclamation-octagon-fill",
        color: "#ef4444",
    },
    //Thêm các hành động hợp đồng của chủ trọ
    create_contract: {
        label: "Lập hợp đồng nháp",
        icon: "bi-file-earmark-plus-fill",
        color: "#3b82f6",
    },
    abnormal_contract: {
        label: "Hợp đồng bất thường",
        icon: "bi-file-earmark-ruled-fill",
        color: "#ef4444",
    },
    sign_contract: {
        label: "Ký kết hợp đồng",
        icon: "bi-file-earmark-check-fill",
        color: "#10b981",
    },
    extend_contract: {
        label: "Gia hạn hợp đồng",
        icon: "bi-file-earmark-arrow-up-fill",
        color: "#8b5cf6",
    },
    terminate_contract: {
        lablel: "Hủy/Thanh lý hợp đồng",
        icon: "bi-file-earmark-x-fill",
        color: "#dc2626",
    },
    bulk_invoice: {
        label: 'Lập hàng loạt',
        icon: 'bi-lightning-charge-fill',
        color: '#f59e0b',
    },
};

// 2. Hàm định dạng thời gian đẹp mắt hiển thị lên giao diện
const formatDateTime = (dateStr) => {
    if (!dateStr) return "N/A";
    return new Date(dateStr).toLocaleString("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

// 3. Lọc danh sách log động dựa trên ô tìm kiếm & bộ lọc phân loại
const filtered = computed(() => {
    const rawLogs = props.logs?.data || []; // Lấy mảng logs từ object phân trang
    return rawLogs.filter((l) => {
        const q = search.value.toLowerCase();
        const userName = l.user ? l.user.name : "Hệ thống";

        const matchesSearch =
            !q ||
            userName.toLowerCase().includes(q) ||
            (l.target && l.target.toLowerCase().includes(q)) ||
            (l.ip_address && l.ip_address.includes(q));
        const isSensitive = !!Number(l.sensitive);
        const matchesType =
            typeFilter.value === "all" ||
            (typeFilter.value === "sensitive"
                ? l.sensitive
                : l.action === typeFilter.value);

        return matchesSearch && matchesType;
    });
});
</script>

<template>

    <Head title="Admin - Audit Log" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Audit Log — Giám Sát Bảo Mật</h1>
                <p class="page-sub">
                    Nhật ký toàn bộ hành động quản trị hệ thống
                </p>
            </div>
        </template>

        <!-- Alert hiển thị tổng số hành động nhạy cảm động từ DB -->
        <div class="alert-banner" v-if="
            props.logs?.data?.filter((l) => !!Number(l.sensitive)).length >
            0
        ">
            <i class="bi bi-shield-exclamation"></i>
            <span>Phát hiện
                <strong>{{
                    props.logs.data.filter((l) => l.sensitive).length
                    }}
                    hành động nhạy cảm</strong>
                trong danh sách. Hãy kiểm tra kỹ!</span>
        </div>

        <!-- Filters -->
        <div class="filter-bar">
            <div class="search-wrap">
                <i class="bi bi-search si"></i>
                <input v-model="search" @input="handleFilter" type="text"
                    placeholder="Tìm theo người dùng, IP, nội dung..." class="search-input" />
            </div>
            <select v-model="typeFilter" @change="handleFilter" class="filter-select">
                <option value="all">Tất cả hành động</option>
                <option value="sensitive"> Nhạy cảm / Cảnh báo</option>
                <option value="login">Đăng nhập</option>
                <option value="lock_user">Khóa tài khoản</option>
                <option value="approve_post">Duyệt tin trọ</option>
                <option value="update_website">Cấu hình Website</option>
                <option value="all">Tất cả hành động kiểm duyệt</option>
                <option value="approve_verification">Duyệt tài khoản Chủ trọ</option>
                <option value="reject_verification">Từ chối tài khoản Chủ trọ</option>
                <option value="approve_boarding_house">Duyệt Cơ sở trọ</option>
                <option value="reject_boarding_house">Từ chối Cơ sở trọ</option>
            </select>
        </div>

        <!-- Log table -->
        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px">#</th>
                        <th>Người thực hiện</th>
                        <th>IP</th>
                        <th>Hành động</th>
                        <th>Nội dung chi tiết</th>
                        <th>Thời gian</th>
                        <th style="text-align: center">Mức độ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!filtered.length">
                        <td colspan="7" class="empty-row">
                            <i class="bi bi-inbox"></i>
                            <p>Không có log lịch sử nào</p>
                        </td>
                    </tr>
                    <tr v-for="(log, i) in filtered" :key="log.id"
                        :class="['trow', log.sensitive ? 'row-sensitive' : '']">
                        <td class="idx">{{ i + 1 }}</td>
                        <td>
                            <div class="user-cell">
                                <div class="user-dot"
                                    :style="`background:${actionMap[log.action]?.color || '#64748b'}20`">
                                    <i :class="[
                                        'bi',
                                        actionMap[log.action]?.icon ||
                                        'bi-activity',
                                    ]" :style="`color:${actionMap[log.action]?.color || '#64748b'}`"></i>
                                </div>
                                <span class="fw">{{
                                    log.user ? log.user.name : "Hệ thống"
                                }}</span>
                            </div>
                        </td>
                        <td>
                            <code class="ip-code">{{
                                log.ip_address || "N/A"
                            }}</code>
                        </td>
                        <td>
                            <span class="action-badge"
                                :style="`background:${actionMap[log.action]?.color || '#64748b'}15;color:${actionMap[log.action]?.color || '#64748b'}`">
                                {{ actionMap[log.action]?.label || log.action }}
                            </span>
                        </td>
                        <td class="target-cell">{{ log.target }}</td>
                        <td class="sm-gray">
                            {{ formatDateTime(log.created_at) }}
                        </td>
                        <td style="text-align: center">
                            <span v-if="log.sensitive" class="sensitive-chip">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                Nhạy cảm
                            </span>
                            <span v-else class="normal-chip">Bình thường</span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Điều hướng phân trang (Pagination) ở dưới bảng -->
            <div v-if="props.logs?.links?.length > 3"
                class="flex items-center justify-between p-4 border-t border-slate-100 bg-slate-50/50" style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 16px;
                ">
                <span class="text-xs text-slate-500">
                    Hiển thị {{ props.logs.from || 0 }} -
                    {{ props.logs.to || 0 }} trong tổng số
                    {{ props.logs.total }} logs
                </span>
                <div class="flex gap-1" style="display: flex; gap: 4px">
                    <component v-for="link in props.logs.links" :key="link.label" :is="link.url ? 'Link' : 'span'"
                        :href="link.url" v-html="link.label" :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-md border',
                            link.active
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50',
                        ]" style="
                            padding: 6px 12px;
                            font-size: 12px;
                            border-radius: 6px;
                            border: 1px solid #e2e8f0;
                            text-decoration: none;
                            cursor: pointer;
                        " :style="link.active
                            ? 'background-color:#4f46e5; color:white; border-color:#4f46e5;'
                            : 'background-color:white; color:#475569;'
                            " />
                </div>
            </div>
        </div>
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

.alert-banner {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #92400e;
    margin-bottom: 16px;
}

.alert-banner i {
    font-size: 18px;
    color: #f59e0b;
    flex-shrink: 0;
}

.filter-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
    flex-wrap: wrap;
    align-items: center;
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

.export-btn {
    padding: 9px 14px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
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
    padding: 13px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
    letter-spacing: 0.04em;
}

.data-table td {
    padding: 11px 16px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}

.trow:last-child td {
    border-bottom: none;
}

.trow:hover td {
    background: #fafbff;
}

.row-sensitive td {
    background: #fffbeb !important;
}

.row-sensitive:hover td {
    background: #fef3c7 !important;
}

.idx {
    color: #cbd5e1;
    font-size: 12px;
    font-weight: 600;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-dot {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.fw {
    font-weight: 600;
    color: #0f172a;
}

.ip-code {
    font-family: monospace;
    font-size: 12px;
    background: #f1f5f9;
    padding: 2px 7px;
    border-radius: 4px;
    color: #475569;
}

.action-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 99px;
}

.target-cell {
    font-size: 12px;
    color: #334155;
    max-width: 220px;
}

.sm-gray {
    color: #94a3b8;
    font-size: 12px;
}

.sensitive-chip {
    font-size: 11px;
    font-weight: 700;
    background: #fef3c7;
    color: #92400e;
    padding: 3px 9px;
    border-radius: 99px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.normal-chip {
    font-size: 11px;
    font-weight: 600;
    background: #f1f5f9;
    color: #64748b;
    padding: 3px 9px;
    border-radius: 99px;
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
</style>
