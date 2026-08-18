<script setup>
import { ref } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    subscriptions: Object,
    currentStatus: String,
});

const subToApprove = ref(null);
const previewImage = ref(null);
const rejectModalSub = ref(null);

const rejectForm = useForm({
    admin_note: '',
});

const statusTabs = [
    { key: 'all', label: 'Tất cả' },
    { key: 'pending', label: 'Chờ duyệt' },
    { key: 'active', label: 'Đang hoạt động' },
    { key: 'rejected', label: 'Bị từ chối' },
    { key: 'expired', label: 'Hết hạn' },
];

const formatMoney = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);

const changeStatus = (st) => {
    router.get(route('admin.landlord-subscriptions.index'), { status: st }, { preserveState: true });
};

const getStatusBadge = (st) => {
    switch (st) {
        case 'active': return 'bg-emerald-100 text-emerald-700';
        case 'pending': return 'bg-amber-100 text-amber-700 animate-pulse';
        case 'rejected': return 'bg-rose-100 text-rose-700';
        case 'expired': return 'bg-slate-100 text-slate-600';
        default: return 'bg-slate-100 text-slate-600';
    }
};

const getStatusText = (st) => {
    switch (st) {
        case 'active': return 'Đang dùng';
        case 'pending': return 'Chờ duyệt';
        case 'rejected': return 'Bị từ chối';
        case 'expired': return 'Hết hạn';
        default: return st;
    }
};

const openApproveModal = (sub) => {
    subToApprove.value = sub;
};

const confirmApprove = () => {
    if (subToApprove.value) {
        router.post(route('admin.landlord-subscriptions.approve',
            subToApprove.value.id), {}, {
            onSuccess: () => subToApprove.value = null,
        });
    }
};

const openRejectModal = (sub) => {
    rejectModalSub.value = sub;
    rejectForm.admin_note = '';
};

const submitReject = () => {
    rejectForm.post(route('admin.landlord-subscriptions.reject', rejectModalSub.value.id), {
        onSuccess: () => rejectModalSub.value = null,
    });
};

//hàm định dạng ngày tháng
const formatDate = (dateSrt) => {
    if (!dateSrt) return "---";
    const date = new
        Date(dateSrt);
    if (isNaN(date.getTime()))
        return dateSrt;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};


</script>

<template>
    <AdminLayout title="Duyệt Đơn Mua Gói Chủ trọ">
        <div class="p-6 space-y-6">
            <!-- Header & Filter -->
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Quản lý Đăng ký Gói Chủ trọ</h1>
                    <p class="text-slate-500 text-sm mt-1">Duyệt và kiểm tra lịch sử thanh toán mua gói</p>
                </div>

                <!-- Tabs Filter Status -->
                <div class="flex bg-slate-100 p-1 rounded-xl gap-1 text-sm font-medium">
                    <button v-for="st in statusTabs" :key="st.key" @click="changeStatus(st.key)"
                        class="px-3.5 py-1.5 rounded-lg transition-all"
                        :class="currentStatus === st.key ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                        {{ st.label }}
                    </button>
                </div>
            </div>

            <!-- Bảng danh sách đơn mua gói -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-slate-500 font-semibold uppercase text-xs">
                        <tr>
                            <th class="p-4">Mã GD / Chủ trọ</th>
                            <th class="p-4">Gói dịch vụ</th>
                            <th class="p-4">Số tiền</th>
                            <th class="p-4">Thời gian</th>
                            <th class="p-4">Ảnh Chuyển Khoản</th>
                            <th class="p-4">Trạng thái</th>
                            <th class="p-4 text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="sub in subscriptions.data" :key="sub.id" class="hover:bg-slate-50/50">
                            <td class="p-4">
                                <div class="font-bold text-slate-800">{{ sub.payment_code || '---' }}</div>
                                <div class="text-xs text-slate-500 font-medium mt-0.5">{{ sub.user?.name }} ({{
                                    sub.user?.phone }})</div>
                            </td>
                            <td class="p-4 font-medium text-slate-800">
                                {{ sub.plan?.name }}
                            </td>
                            <td class="p-4 font-bold text-indigo-600">
                                {{ formatMoney(sub.price_at_purchase) }}
                            </td>
                            <td class="p-4 text-xs font-medium">
                                <div v-if="sub.start_date" class="text-slate-700">Từ: <span
                                        class="font-bold text-slate-900">{{ formatDate(sub.start_date) }}</span></div>
                                <div v-if="sub.end_date" class="text-slate-700">Đến: <span
                                        class="font-bold text-slate-900">{{ formatDate(sub.end_date) }}</span></div>
                                <div v-if="!sub.start_date" class="text-slate-400">Chưa kích hoạt</div>
                            </td>
                            <td class="p-4">
                                <div v-if="sub.proof_image">
                                    <img :src="sub.proof_image"
                                        class="w-12 h-12 object-cover rounded-lg border border-slate-200 cursor-pointer hover:scale-110 transition-transform"
                                        @click="previewImage = sub.proof_image" />
                                </div>
                                <span v-else class="text-xs text-slate-400">Chưa tải bill</span>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :class="getStatusBadge(sub.status)">
                                    {{ getStatusText(sub.status) }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div v-if="sub.status === 'pending'" class="flex justify-end gap-2">
                                    <button @click="openApproveModal(sub)"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium shadow-sm transition-all">
                                        Duyệt
                                    </button>
                                    <button @click="openRejectModal(sub)"
                                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-medium shadow-sm">
                                        Từ chối
                                    </button>
                                </div>
                                <span v-else class="text-xs text-slate-400">Đã xử lý</span>
                            </td>
                        </tr>
                        <tr v-if="!subscriptions.data.length">
                            <td colspan="7" class="text-center py-8 text-slate-400">Chưa có đơn đăng ký gói nào trong
                                danh mục này.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Component Phân Trang (Pagination) -->
                <div v-if="subscriptions.links && subscriptions.links.length > 3"
                    class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                    
                    <!-- Số lượng bản ghi -->
                    <div class="text-xs text-slate-500">
                        Hiển thị từ <span class="font-bold text-slate-800">{{ subscriptions.from || 0 }}</span> 
                        đến <span class="font-bold text-slate-800">{{ subscriptions.to || 0 }}</span> 
                        trong tổng số <span class="font-bold text-slate-800">{{ subscriptions.total || 0 }}</span> đơn mua gói
                    </div>

                    <!-- Nút chuyển trang -->
                    <div class="flex items-center gap-1 flex-wrap">
                        <template v-for="(link, key) in subscriptions.links" :key="key">
                            <div v-if="link.url === null" 
                                class="px-3 py-1.5 text-xs text-slate-300 rounded-xl border border-slate-200 cursor-not-allowed select-none"
                                v-html="link.label" />

                            <Link v-else 
                                :href="link.url"
                                class="px-3 py-1.5 text-xs font-bold rounded-xl border transition-all cursor-pointer"
                                :class="link.active 
                                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-200' 
                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100 hover:text-indigo-600'"
                                v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal Phóng to Ảnh Bill -->
            <div v-if="previewImage" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80"
                @click="previewImage = null">
                <img :src="previewImage" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl" />
            </div>

            <!-- Modal Từ chối -->
            <div v-if="rejectModalSub"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
                    <h3 class="font-bold text-slate-800 text-lg mb-2">Từ chối đơn mua gói</h3>
                    <p class="text-xs text-slate-500 mb-4">Mã GD: {{ rejectModalSub.payment_code }} - Gói: {{
                        rejectModalSub.plan?.name }}</p>

                    <form @submit.prevent="submitReject">
                        <textarea v-model="rejectForm.admin_note" rows="3"
                            class="w-full rounded-xl border-slate-200 text-sm focus:ring-rose-500 focus:border-rose-500"
                            placeholder="Nhập lý do từ chối (Ví dụ: Không tìm thấy giao dịch ngân hàng...)"></textarea>
                        <!-- Hiển thị validate lỗi -->
                        <p v-if="rejectForm.errors.admin_note"
                            class="text-rose-500 text-xs font-semibold mt-1 flex items-center gap-1">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span>{{ rejectForm.errors.admin_note }}</span>
                        </p>
                        <div class="flex justify-end gap-3 mt-4">
                            <button type="button" @click="rejectModalSub = null"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-medium transition-all cursor-pointer">
                                Hủy
                            </button>
                            <button type="submit" :disabled="rejectForm.processing"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-medium shadow-md transition-all cursor-pointer disabled:opacity-50 flex items-center gap-2">
                                <span v-if="rejectForm.processing">Đang từ chối...</span>
                                <span v-else>Xác nhận Từ chối</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Modal Xác Nhận Duyệt Gói Cho Admin (Thay thế confirm thô) -->
        <div v-if="subToApprove"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div
                class="bg-white rounded-3xl max-w-md w-full p-6 text-center shadow-2xl border border-slate-100 relative animate-in fade-in zoom-in duration-200">
                <div
                    class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
                    <i class="bi bi-patch-check-fill"></i>
                </div>

                <h3 class="text-lg font-bold text-slate-800">Xác Nhận Duyệt Gói Dịch Vụ?</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                    Bạn có chắc chắn muốn duyệt và kích hoạt gói <strong>"{{ subToApprove.plan?.name }}"</strong> cho
                    chủ trọ <strong>{{ subToApprove.user?.name }}</strong>?
                </p>

                <div class="mt-6 flex items-center gap-3">
                    <button @click="subToApprove = null"
                        class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-2xl transition-all cursor-pointer">
                        Hủy bỏ
                    </button>
                    <button @click="confirmApprove"
                        class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-2xl shadow-lg shadow-emerald-600/20 transition-all cursor-pointer">
                        Kích Hoạt Ngay
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
