<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { showSuccess, showError, showConfirm } from '@/Utils/swal';

const props = defineProps({
    roommateRequests: Array
});

const activeTab = ref('pending'); // 'all', 'pending', 'approved', 'rejected'

const filteredRequests = computed(() => {
    if (activeTab.value === 'all') {
        return props.roommateRequests || [];
    }
    return (props.roommateRequests || []).filter(r => r.status === activeTab.value);
});

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'pending': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'approved': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'rejected': return 'bg-rose-100 text-rose-800 border-rose-200';
        default: return 'bg-slate-100 text-slate-800 border-slate-200';
    }
};

const getStatusText = (status) => {
    switch (status) {
        case 'pending': return 'Đang chờ duyệt';
        case 'approved': return 'Đã phê duyệt';
        case 'rejected': return 'Đã từ chối';
        default: return status;
    }
};

const handleApprove = async (req) => {
    const message = req.type === 'stranger'
        ? 'Duyệt yêu cầu này đồng nghĩa với việc bạn đồng ý mở lại tin đăng để tìm người ở ghép cho phòng này. Tiếp tục?'
        : `Bạn có chắc chắn muốn duyệt và thêm cư dân "${req.new_resident_name}" vào phòng trọ không?`;

    const isConfirmed = await showConfirm('Xác nhận phê duyệt', message);
    if (!isConfirmed) return;

    router.post(route('landlord.roommate-requests.approve', req.id), {}, {
        onSuccess: (page) => {
            if (page.props.flash?.error) {
                showError('Lỗi', page.props.flash.error);
            } else {
                showSuccess('Thành công', 'Đã phê duyệt yêu cầu ở ghép thành công!');
            }
        },
        onError: (err) => {
            showError('Lỗi', Object.values(err).join('\n'));
        }
    });
};

const handleReject = async (req) => {
    const isConfirmed = await showConfirm('Từ chối yêu cầu', 'Bạn có chắc chắn muốn từ chối yêu cầu ở ghép này không?');
    if (!isConfirmed) return;

    router.post(route('landlord.roommate-requests.reject', req.id), {}, {
        onSuccess: () => {
            showSuccess('Thành công', 'Đã từ chối yêu cầu ở ghép.');
        },
        onError: (err) => {
            showError('Lỗi', err.message || 'Có lỗi xảy ra.');
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>

    <Head title="Yêu Cầu Ở Ghép | Chủ Trọ" />
    <LandlordLayout>
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header section -->
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-xs">
                <div class="space-y-1">
                    <h2 class="text-xl font-black text-slate-800 flex items-center gap-2">
                        <i class="bi bi-people-fill text-emerald-600"></i> Quản
                        Lý Yêu Cầu Ở Ghép
                    </h2>
                    <p class="text-xs text-slate-500 font-semibold">
                        Tiếp nhận, phê duyệt tin tìm ở ghép hoặc thêm thành viên
                        ở ghép do cư dân giới thiệu.
                    </p>
                </div>
            </div>

            <!-- Tabs filter bar -->
            <div
                class="flex bg-slate-100 p-1.5 rounded-2xl gap-1 text-xs font-bold text-slate-500 max-w-md shadow-inner">
                <button type="button" @click="activeTab = 'pending'" :class="activeTab === 'pending'
                    ? 'bg-white text-slate-800 shadow-sm'
                    : 'hover:text-slate-800'
                    " class="flex-1 py-2.5 rounded-xl transition-all text-center cursor-pointer">
                    Đang chờ duyệt
                </button>
                <button type="button" @click="activeTab = 'approved'" :class="activeTab === 'approved'
                    ? 'bg-white text-slate-800 shadow-sm'
                    : 'hover:text-slate-800'
                    " class="flex-1 py-2.5 rounded-xl transition-all text-center cursor-pointer">
                    Đã duyệt
                </button>
                <button type="button" @click="activeTab = 'rejected'" :class="activeTab === 'rejected'
                    ? 'bg-white text-slate-800 shadow-sm'
                    : 'hover:text-slate-800'
                    " class="flex-1 py-2.5 rounded-xl transition-all text-center cursor-pointer">
                    Đã từ chối
                </button>
                <button type="button" @click="activeTab = 'all'" :class="activeTab === 'all'
                    ? 'bg-white text-slate-800 shadow-sm'
                    : 'hover:text-slate-800'
                    " class="flex-1 py-2.5 rounded-xl transition-all text-center cursor-pointer">
                    Tất cả
                </button>
            </div>

            <!-- List requests cards -->
            <div class="grid grid-cols-1 gap-4">
                <div v-for="req in filteredRequests" :key="req.id"
                    class="bg-white rounded-3xl border border-slate-100 p-6 shadow-xs hover:shadow-md transition-all duration-300 grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                    <!-- Left: Room info -->
                    <div
                        class="md:col-span-3 space-y-1.5 border-b md:border-b-0 md:border-r border-slate-100 pb-3 md:pb-0 md:pr-4">
                        <span
                            class="px-2.5 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-200 text-[10px] font-black rounded-lg uppercase tracking-wider">
                            Phòng {{ req.room?.room_number }}
                        </span>
                        <div class="font-extrabold text-slate-800 text-sm mt-1">
                            {{ req.room?.boarding_house?.name }}
                        </div>
                        <div class="text-[11px] text-slate-400 font-semibold flex items-center gap-1">
                            <i class="bi bi-clock"></i> Gửi lúc:
                            {{ formatDate(req.created_at) }}
                        </div>
                    </div>

                    <!-- Middle left: Sender tenant info -->
                    <div class="md:col-span-3 space-y-1">
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Người gửi yêu
                            cầu</span>
                        <div class="font-bold text-slate-800 text-xs">
                            {{ req.tenant?.name }}
                        </div>
                        <div class="text-[11px] text-slate-500 font-semibold">
                            SĐT: {{ req.tenant?.phone }}
                        </div>
                    </div>

                    <!-- Middle right: Request details & type -->
                    <div class="md:col-span-4 space-y-2">
                        <div>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Loại yêu
                                cầu</span>
                            <span v-if="req.type === 'stranger'"
                                class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-100 mt-0.5">
                                <i class="bi bi-people-fill"></i> Tìm người lạ ở
                                ghép (Đăng tin)
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-100 mt-0.5">
                                <i class="bi bi-person-plus-fill"></i> Người
                                quen giới thiệu (Thêm thẳng)
                            </span>
                        </div>

                        <!-- Acquaintance details -->
                        <div v-if="req.type === 'acquaintance'"
                            class="p-3 bg-slate-50 border border-slate-100 rounded-2xl text-[11px] space-y-1 font-semibold text-slate-600">
                            <div>
                                Thành viên mới:
                                <strong class="text-slate-800">{{
                                    req.new_resident_name
                                }}</strong>
                            </div>
                            <div>
                                Số điện thoại:
                                <strong class="text-slate-800">{{
                                    req.new_resident_phone
                                }}</strong>
                            </div>
                            <div>
                                Email:
                                <strong class="text-slate-800">{{
                                    req.new_resident_email
                                }}</strong>
                            </div>
                            <div>
                                Số CCCD:
                                <strong class="text-slate-800">{{
                                    req.new_resident_cccd
                                }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Status & Actions -->
                    <div class="md:col-span-2 flex flex-col items-stretch sm:items-end justify-center gap-2">
                        <span :class="getStatusBadgeClass(req.status)"
                            class="px-3 py-1 border text-[11px] font-bold rounded-xl text-center self-stretch sm:self-auto">
                            {{ getStatusText(req.status) }}
                        </span>

                        <div v-if="req.status === 'pending'" class="flex gap-2 w-full">
                            <button @click="handleReject(req)"
                                class="flex-1 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 text-xs font-bold rounded-xl transition-all cursor-pointer">
                                Từ chối
                            </button>
                            <button @click="handleApprove(req)"
                                class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer">
                                Duyệt
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="filteredRequests.length === 0"
                    class="bg-white rounded-3xl border border-slate-100 p-12 text-center text-slate-400 font-bold text-sm flex flex-col items-center justify-center gap-3">
                    <i class="bi bi-inbox text-5xl text-slate-300"></i>
                    <span>Không có yêu cầu ở ghép nào thuộc trạng thái này.</span>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
