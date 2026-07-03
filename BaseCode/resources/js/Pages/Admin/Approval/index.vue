<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    listings: Array,
});

const activeTab = ref("pending");

//Phần phân loại dữ liệu tự đông theo tap dựa trên trạng thái
const filteredPosts = computed(() => {
    if (!props.listings) return [];
    return props.listings.filter((p) => p.status === activeTab.value);
});

//Phần cập nhật số lượng hiển thị trên các nhãn tap tự động hiển thị theo
const tabs = computed(() => [
    {
        key: "pending",
        label: "Chờ Duyệt",
        count:
            props.listings?.filter((p) => p.status === "pending").length || 0,
    },
    {
        key: "approved",
        label: "Đã Duyệt",
        count:
            props.listings?.filter((p) => p.status === "approved").length || 0,
    },
    {
        key: "rejected",
        label: "Từ Chối",
        count:
            props.listings?.filter((p) => p.status === "rejected").length || 0,
    },
]);

const showDetail = ref(false);
const detailPost = ref(null);
const rejectForm = useForm({
    reject_reason: "",
});

function openDetail(post) {
    detailPost.value = post;
    showDetail.value = true;
    rejectForm.reject_reason = post.reject_reason || ""; //Đổ lý do cũ ra nếu tin đó đã từng bị từ chối
    rejectForm.clearErrors();
}

//Phần kết nối với DB để gọi lệnh phê duyệt bài viết lên
function approvePost(post) {
    if (
        confirm(
            `Bạn có chắc chắn muốn phê duyệt bài đăng:"${post.title}" không`,
        )
    ) {
        router.post(
            route("admin.listings.approve", post.id),
            {},
            {
                onSuccess: () => {
                    showDetail.value = false;
                },
            },
        );
    }
}
//Phần kết nối với DB để gửi lý do từ chối phê duyệt bài viết lên
function rejectPost(post) {
    rejectForm.post(route("admin.listings.reject", post.id), {
        onSuccess: () => {
            showDetail.value = false;
            rejectForm.reject_reason = "";
        },
    });
}

//Hàm chuyển đổi định dạng hiển thị ngày chuẩn Việt Nam
const formatDate = (dateStr) => {
    if (!dateStr) return "_";
    return new Date(dateStr).toLocaleDateString("vi-VN");
};
//hàm định dạng tiền tệ
const formatMoney = (n) => (n ? new Intl.NumberFormat("vi-VN").format(n) : "-");

const typeClass = {
    "Phòng đơn": "type-blue",
    "Phòng ghép": "type-green",
    "Nhà nguyên căn": "type-purple",
    Studio: "type-orange",
};
</script>

<template>
    <Head title="Admin - Phê Duyệt Nội Dung" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Phê Duyệt Nội Dung</h1>
                <p class="page-sub">
                    Kiểm duyệt tin đăng trước khi hiển thị công khai trên hệ
                    thống Ninh Bình StayWork
                </p>
            </div>
        </template>

        <div class="tabs-bar">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                :class="['tab-btn', activeTab === tab.key ? 'tab-active' : '']"
            >
                {{ tab.label }}
                <span
                    :class="[
                        'tab-count',
                        activeTab === tab.key ? 'count-active' : '',
                    ]"
                >
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <div class="post-list">
            <div v-if="filteredPosts.length === 0" class="empty-state">
                <i class="bi bi-inbox text-3xl text-gray-300"></i>
                <p class="text-sm text-gray-400 mt-2">
                    Không có tin đăng nào trong danh mục này
                </p>
            </div>

            <div v-for="post in filteredPosts" :key="post.id" class="post-card">
                <div class="post-thumb">
                    <img
                        v-if="post.image && post.image.length > 0"
                        :src="post.image[0]"
                        class="w-full h-full object-cover rounded-lg"
                    />
                    <i
                        v-else
                        class="bi bi-house-door text-4xl text-slate-300"
                    ></i>
                </div>

                <div class="post-info">
                    <div class="post-meta">
                        <span
                            :class="[
                                'post-type',
                                typeClass[post.room?.room_type] || 'type-blue',
                            ]"
                        >
                            {{ post.room?.room_type || "Phòng trọ" }}
                        </span>
                        <span class="post-id">#{{ post.id }}</span>
                    </div>
                    <h3 class="post-title">{{ post.title }}</h3>
                    <div class="post-details">
                        <span
                            ><i class="bi bi-person"></i>
                            {{ post.landlord?.name }}</span
                        >
                        <span class="font-bold text-red-600"
                            ><i class="bi bi-cash"></i>
                            {{ formatMoney(post.room?.price) }}đ/tháng</span
                        >
                        <span
                            ><i class="bi bi-calendar3"></i>
                            {{ formatDate(post.created_at) }}</span
                        >
                    </div>
                </div>

                <div class="post-actions">
                    <Link
                        :href="route('admin.listings.show', post.id)"
                        class="act-view inline-flex items-center gap-1 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg font-bold text-xs transition-colors"
                    >
                        <i class="bi bi-eye"></i> Xem chi tiết
                    </Link>
                    <template v-if="activeTab === 'pending'">
                        <button @click="approvePost(post)" class="act-approve">
                            <i class="bi bi-check-lg"></i> Duyệt
                        </button>
                        <button @click="openDetail(post)" class="act-reject">
                            <i class="bi bi-x-lg"></i> Từ chối
                        </button>
                    </template>
                    <span
                        v-else-if="activeTab === 'approved'"
                        class="badge-done"
                        >✓ Đã duyệt</span
                    >
                    <span v-else class="badge-reject">✗ Đã từ chối</span>
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

.tabs-bar {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    background: #fff;
    padding: 6px;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
    width: fit-content;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.tab-btn {
    padding: 8px 18px;
    border-radius: 10px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: all 0.15s;
}

.tab-btn:hover {
    background: #f8fafc;
}

.tab-active {
    background: #7c3aed;
    color: #fff;
}

.tab-count {
    min-width: 20px;
    height: 20px;
    border-radius: 99px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    padding: 0 6px;
    background: rgba(0, 0, 0, 0.1);
}

.count-active {
    background: rgba(255, 255, 255, 0.25);
}

.post-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.empty-state {
    text-align: center;
    padding: 60px;
    color: #94a3b8;
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
}

.empty-state i {
    font-size: 48px;
    display: block;
    margin-bottom: 12px;
}

.post-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    transition: box-shadow 0.2s;
}

.post-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.post-thumb {
    width: 70px;
    height: 70px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.post-info {
    flex: 1;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.post-type {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 99px;
}

.type-blue {
    background: #eff6ff;
    color: #2563eb;
}

.type-green {
    background: #f0fdf4;
    color: #16a34a;
}

.type-purple {
    background: #faf5ff;
    color: #7c3aed;
}

.type-orange {
    background: #fff7ed;
    color: #ea580c;
}

.post-id {
    font-size: 11px;
    color: #94a3b8;
}

.post-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 6px;
}

.post-details {
    display: flex;
    gap: 14px;
    font-size: 12px;
    color: #64748b;
}

.post-details span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.post-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 7px;
    flex-shrink: 0;
}

.act-view {
    padding: 7px 14px;
    border-radius: 9px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s;
}

.act-view:hover {
    border-color: #7c3aed;
    color: #7c3aed;
}

.act-approve {
    padding: 7px 14px;
    border-radius: 9px;
    border: none;
    background: #22c55e;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: background 0.15s;
}

.act-approve:hover {
    background: #16a34a;
}

.act-reject {
    padding: 7px 14px;
    border-radius: 9px;
    border: none;
    background: #fef2f2;
    color: #ef4444;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s;
}

.act-reject:hover {
    background: #ef4444;
    color: #fff;
}

.badge-done {
    font-size: 12px;
    font-weight: 700;
    color: #16a34a;
    background: #f0fdf4;
    padding: 5px 12px;
    border-radius: 99px;
}

.badge-reject {
    font-size: 12px;
    font-weight: 700;
    color: #dc2626;
    background: #fef2f2;
    padding: 5px 12px;
    border-radius: 99px;
}

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(3px);
}

.modal-box {
    background: #fff;
    border-radius: 20px;
    width: 520px;
    max-width: 92vw;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    overflow: hidden;
}

.modal-lg {
    width: 560px;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #f1f5f9;
}

.modal-header h3 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 20px 24px;
}

.detail-thumb {
    width: 100%;
    height: 140px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 40px;
    margin-bottom: 16px;
}

.detail-thumb span {
    font-size: 12px;
    margin-top: 4px;
}

.detail-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 16px;
}

.detail-row {
    display: flex;
    gap: 12px;
    font-size: 13px;
    padding: 6px 0;
    border-bottom: 1px solid #f8fafc;
}

.dl {
    width: 100px;
    flex-shrink: 0;
    color: #94a3b8;
    font-weight: 500;
}

.dv {
    color: #0f172a;
    font-weight: 500;
}

.reject-label {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    display: block;
    margin-bottom: 6px;
}

.reject-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px;
    resize: none;
    outline: none;
    box-sizing: border-box;
}

.reject-input:focus {
    border-color: #7c3aed;
}

.modal-footer {
    display: flex;
    gap: 8px;
    padding: 16px 24px;
    border-top: 1px solid #f1f5f9;
}

.btn-cancel {
    flex: 1;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.btn-reject-confirm {
    flex: 1;
    padding: 10px;
    border-radius: 10px;
    border: none;
    background: #fef2f2;
    color: #ef4444;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.btn-reject-confirm:hover {
    background: #ef4444;
    color: #fff;
}

.btn-approve-confirm {
    flex: 2;
    padding: 10px;
    border-radius: 10px;
    border: none;
    background: #7c3aed;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.btn-approve-confirm:hover {
    background: #6d28d9;
}
</style>
