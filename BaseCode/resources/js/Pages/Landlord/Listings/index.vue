<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { showConfirm, showWarning } from "@/Utils/swal";
import { getStatusLabel, getStatusClass } from "@/Utils/statusHelper";

const props = defineProps({
    listings: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const activeTab = ref("all"); // 'all' | 'approved' | 'pending'  | 'draft'
const selectedPackage = ref("standard");

const flashSuccess = computed(() => page.props.flash?.success);
const statusMap = {
    approved: {
        label: "Đang Hiển Thị",
        cls: "bg-emerald-50 text-emerald-600 border-emerald-100",
        dot: "bg-emerald-500",
    },
    pending: {
        label: "Chờ Duyệt",
        cls: "bg-amber-50 text-amber-600 border-amber-100",
        dot: "bg-amber-500",
    },
    rejected: {
        label: "Bị Từ Chối",
        cls: "bg-rose-50 text-rose-600 border-rose-100",
        dot: "bg-rose-500",
    },
    hidden: {
        label: "Đã Ẩn",
        cls: "bg-slate-50 text-slate-500 border-slate-100",
        dot: "bg-slate-500",
    },
    draft: {
        label: "Bản Nháp",
        cls: "bg-gray-50 text-gray-600 border-gray-100",
        dot: "bg-gray-500",
    },
};

const formatMoney = (n) => new Intl.NumberFormat("vi-VN").format(n) + "đ";
const formatDateTime = (dateStr) => {
    if (!dateStr) return "";
    return new Date(dateStr).toLocaleString("vi-VN", {
        hour: "2-digit",
        minute: "2-digit",
        day: "2-digit",
        month: "2-digit",
    });
};

const deleteListing = async (id) => {
    const confirmed = await showConfirm(
        "Xác nhận xóa",
        "Bạn có chắc chắn muốn xóa bài đăng này?",
        "Xóa tin",
        "Hủy"
    );
    if (confirmed) {
        router.delete(route("landlord.listings.destroy", id));
    }
};

// Hàm đóng tin đăng
const closeListing = async (id, title) => {
    const confirmed = await showConfirm(
        "Xác nhận đóng tin đăng",
        `Sau khi đóng, khách thuê sẽ không tìm thấy tin đăng "${title}" này nữa.`,
        "Đóng tin",
        "Hủy"
    );
    if (confirmed) {
        router.post(route("landlord.listings.close", id));
    }
};

//Hàm xoá tin đăng
const handleDeletePost = async (id) => {
    const confirmed = await showConfirm(
        "Xác nhận xóa vĩnh viễn",
        "Bạn có chắc chắn muốn xoá vĩnh viễn bài đăng này không? Hành động này không thể hoàn tác.",
        "Xóa vĩnh viễn",
        "Hủy"
    );
    if (confirmed) {
        router.delete(route("landlord.listings.destroy", id));
    }
};

//hàm xử lý đẩy tin lên TOP
const bumpListing = async (id) => {
    //check nếu hết lượt đẩy tin -> mua gói
    if ((user.value?.bump_credits || 0) <= 0) {
        const confirmBuy = await showConfirm("Hết lượt đẩy tin!",
            "Tài khoản của bạn đã hết lượt đẩy tin. Bạn có muốn chuyển sang trang Gói Dịch vụ để nâng cấp thêm lượt không?",
            "Nâng cấp gói ngay",
            "Để sau"
        );
        if (confirmBuy) {
            router.get(route("landlord.subscriptions.index"));
        }
        return;
    }
    //nếu còn lượt -> xác nhận đẩy tin
    const confirmed = await showConfirm(
        "Xác nhận Đẩy Tin lên TOP",
        "Thao tác này sẽ trừ 1 lượt đẩy tin và ngay lập tức đưa bài đăng lên vị trí đầu trang chủ.",
        "Đẩy tin ngay",
        "Huỷ"
    );
    if (confirmed) {
        router.post(route("landlord.listings.bump", id));
    }
};
//hàm kiểm tra phòng đã lấp đầy
const isRoomFull = (ls) => {
    if (!ls || !ls.room) return false;
    const capacity = ls.room.capacity || 1;
    const currentPeople = ls.room.current_people || 0;
    return currentPeople >= capacity;
};

// Quản lý Modal Lịch sử Đẩy tin
const showHistoryModal = ref(false);
const bumpLogs = ref([]);
const isLoadingLogs = ref(false);

const fetchBumpHistory = async () => {
    showHistoryModal.value = true;
    isLoadingLogs.value = true;
    try {
        const res = await axios.get(route('landlord.listings.bump-history'));
        bumpLogs.value = res.data;
    } catch (err) {
        console.error("Lỗi lấy lịch sử đẩy tin:", err);
    } finally {
        isLoadingLogs.value = false;
    }
};
import axios from "axios";
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <div v-if="flashSuccess"
                class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-semibold">
                {{ flashSuccess }}
            </div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Tin đăng</span>
            </div>

            <!-- Page Title -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">
                        Quản lý Tin đăng
                    </h2>
                    <p class="text-xs text-slate-400">
                        Đăng và cập nhật thông tin phòng trống lên sàn Ninh Bình
                        Homestay
                    </p>
                </div>
                <Link :href="route('landlord.listings.create')"
                    class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-emerald-500/10 flex items-center gap-1.5">
                    <i class="bi bi-plus-lg"></i>
                    Đăng tin mới
                </Link>
            </div>

            <!-- Gói Đẩy Tin Card -->
            <div
                class="p-6 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-3xl text-white shadow-xl shadow-emerald-500/10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-lg text-[10px] font-bold uppercase tracking-wider">
                            {{ user?.package_name || 'Gói mặc định' }}
                        </span>
                    </div>
                    <h3 class="text-lg font-extrabold flex items-center gap-1">
                        Số lượt đẩy tin còn lại:
                        <span class="text-yellow-300 text-2xl font-black ml-1">
                            {{ user?.bump_credits || 0 }}
                        </span> lượt
                    </h3>
                    <p class="text-[11px] text-emerald-100/90 max-w-xl leading-relaxed">
                        Đẩy tin giúp bài đăng của bạn đưa lên đầu danh sách trang chủ Ninh Bình Homestay và trang tìm
                        kiếm để tiếp cận nhiều khách thuê hơn.
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Nút xem Lịch sử đẩy tin -->
                    <button @click="fetchBumpHistory" type="button"
                        class="px-3.5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white rounded-2xl font-bold text-xs transition-all flex items-center gap-1.5 border border-white/20 shadow-sm">
                        <i class="bi bi-clock-history"></i>
                        <span>Lịch sử đẩy tin</span>
                    </button>

                    <!-- Nút bấm mở trang Mua Gói Dịch Vụ chính thức -->
                    <Link :href="route('landlord.subscriptions.index')"
                        class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-2xl shadow-md transition-all flex items-center gap-2">
                        <i class="bi bi-gem font-bold"></i>
                        <span>Mua thêm gói lượt đẩy</span>
                    </Link>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="border-b border-slate-100 flex gap-6 text-xs font-bold text-slate-400">
                <!-- Tab Tất cả -->
                <button @click="activeTab = 'all'" :class="[
                    'pb-3 border-b-2 transition-colors',
                    activeTab === 'all'
                        ? 'border-emerald-500 text-emerald-600'
                        : 'border-transparent hover:text-slate-600',
                ]">
                    Tất cả ({{ listings.data.length }})
                </button>
                <!-- Tab Đang hiển thị (được đổi từ 'active' thành 'approved' để khớp với database) -->
                <button @click="activeTab = 'approved'" :class="[
                    'pb-3 border-b-2 transition-colors',
                    activeTab === 'approved'
                        ? 'border-emerald-500 text-emerald-600'
                        : 'border-transparent hover:text-slate-600',
                ]">
                    Đang hiển thị ({{
                        listings.data.filter((l) => l.status === "approved")
                            .length
                    }})
                </button>
                <!-- Tab Chờ duyệt -->
                <button @click="activeTab = 'pending'" :class="[
                    'pb-3 border-b-2 transition-colors',
                    activeTab === 'pending'
                        ? 'border-emerald-500 text-emerald-600'
                        : 'border-transparent hover:text-slate-600',
                ]">
                    Chờ duyệt ({{
                        listings.data.filter((l) => l.status === "pending")
                            .length
                    }})
                </button>
                <!-- Tab Bản nháp (Tab mới thêm vào) -->
                <button @click="activeTab = 'draft'" :class="[
                    'pb-3 border-b-2 transition-colors',
                    activeTab === 'draft'
                        ? 'border-emerald-500 text-emerald-600'
                        : 'border-transparent hover:text-slate-600',
                ]">
                    Bản nháp ({{
                        listings.data.filter((l) => l.status === "draft")
                            .length
                    }})
                </button>
            </div>

            <!-- Listings Cards Deck -->
            <div v-if="!listings.data || listings.data.length === 0"
                class="p-8 text-center text-slate-400 text-xs font-medium space-y-2 bg-white rounded-3xl border border-slate-100">
                <i class="bi bi-megaphone text-3xl text-slate-300 block"></i>
                <span>Chưa có tin đăng nào. Hãy tạo tin đăng đầu tiên của
                    bạn!</span>
            </div>
            <div v-else class="space-y-4">
                <div v-for="ls in listings.data" :key="ls.id" v-show="activeTab === 'all' || ls.status === activeTab"
                    class="bg-white border border-slate-100 rounded-3xl overflow-hidden flex flex-col md:flex-row hover:shadow-md transition-all duration-200">
                    <!-- Left Image -->
                    <div
                        class="w-full md:w-56 bg-slate-50 flex items-center justify-center text-slate-300 min-h-[140px] md:min-h-0 relative overflow-hidden">
                        <img v-if="ls.image && ls.image.length > 0" :src="ls.image[0]"
                            class="w-full h-full object-cover absolute inset-0" alt="Room Image" />
                        <i v-else class="bi bi-image text-3xl"></i>

                        <span
                            class="absolute top-3 left-3 px-2 py-1 bg-white/95 backdrop-blur-sm shadow-sm rounded-lg text-[9px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusMap[ls.status]?.dot"></span>
                            {{ statusMap[ls.status]?.label || ls.status }}
                        </span>

                        <span v-if="ls.room?.current_people > 0 || ls.room?.status === 'rented'"
                            class="absolute bottom-3 left-3 px-2.5 py-1 bg-emerald-600/90 text-white backdrop-blur-sm shadow-sm rounded-lg text-[9.5px] font-bold flex items-center gap-1">
                            <i class="bi bi-person-check-fill"></i> Đã có {{ ls.room?.current_people || 1 }} người ở
                        </span>
                    </div>

                    <!-- Middle Info Body -->
                    <div class="p-6 flex-1 flex flex-col justify-between gap-4">
                        <div class="space-y-2">
                            <h3 class="text-sm font-bold text-slate-800 leading-snug">
                                {{ ls.title }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-500">
                                <span class="flex items-center gap-1"><i class="bi bi-house-door text-emerald-600"></i>
                                    {{ ls.room?.room_number }}</span>
                                <span class="flex items-center gap-1"><i
                                        class="bi bi-aspect-ratio text-emerald-600"></i>
                                    {{ ls.room?.area }} m²</span>
                                <span class="flex items-center gap-1"><i class="bi bi-geo-alt text-emerald-600"></i>
                                    {{ ls.room?.boarding_house?.name }}</span>
                                <span
                                    class="flex items-center gap-1 px-2 py-0.5 bg-blue-50 text-blue-700 rounded-lg font-bold border border-blue-100"><i
                                        class="bi bi-person-fill text-blue-600"></i>
                                    Đã có {{ ls.room?.current_people || 0 }}/{{ ls.room?.capacity || 1 }} người ở</span>
                            </div>
                        </div>

                        <!-- Price & AI suggestions -->
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="text-slate-800 font-black text-base">
                                {{ formatMoney(ls.room?.price || 0)
                                }}<span class="text-[10px] text-slate-400 font-bold">/tháng</span>
                            </div>
                        </div>

                        <!-- View Stats -->
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] text-slate-400 font-bold">
                            <span><i class="bi bi-eye mr-1"></i>
                                {{ ls.view_count }} lượt xem</span>
                            <span><i class="bi bi-calendar3 mr-1"></i> Ngày tạo:
                                {{
                                    new Date(ls.created_at).toLocaleDateString(
                                        "vi-VN",
                                    )
                                }}</span>
                            <span v-if="ls.bump_count > 0"
                                class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-100 flex items-center gap-1">
                                <i class="bi bi-arrow-up-circle"></i> Đã đẩy {{ ls.bump_count }} lần
                            </span>
                            <span v-if="ls.bumped_at" class="text-slate-500 flex items-center gap-1">
                                <i class="bi bi-clock-history"></i> Đẩy cuối: {{ formatDateTime(ls.bumped_at) }}
                            </span>
                        </div>
                    </div>

                    <!-- Right Action Bar -->
                    <div
                        class="p-6 md:border-l border-slate-50 flex flex-row md:flex-col justify-center gap-2 bg-slate-50/35">

                        <!-- Nút Chi tiết -->
                        <Link :href="route('landlord.listings.show', ls.id)"
                            class="flex-1 md:flex-none px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1 border border-blue-200/40">
                            <i class="bi bi-eye"></i>
                            Chi tiết
                        </Link>

                        <!-- 1. Nút Chỉnh sửa: Luôn luôn hiển thị -->
                        <Link v-if="!isRoomFull(ls)" :href="route('landlord.listings.edit', ls.id)"
                            class="flex-1 md:flex-none px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1">
                            <i class="bi bi-pencil-square"></i>
                            Chỉnh sửa
                        </Link>
                        <span v-else
                            class="flex-1 md:flex-none px-3 py-2 bg-slate-100 text-slate-400 font-semibold text-[11px] rounded-xl flex items-center justify-center gap-1 border border-slate-200/60 cursor-not-allowed"
                            title="Phòng đã đủ số lượng người ở, không thể chỉnh sửa tin đăng">
                            <i class="bi bi-lock-fill"></i> Đã đủ người
                        </span>

                        <!-- Đẩy Tin Button -->
                        <button v-if="ls.status === 'approved'" type="button" @click="bumpListing(ls.id)"
                            class="flex-1 md:flex-none px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1 border border-emerald-200/40">
                            <i class="bi bi-arrow-up-circle-fill text-emerald-500"></i>
                            Đẩy tin
                        </button>

                        <button v-if="ls.status === 'approved'" type="button" @click="closeListing(ls.id, ls.title)"
                            class="flex-1 md:flex-none px-4 py-2 bg-amber-50 hover:bg-amber-100 text-amber-600 font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1 border border-amber-200/40">
                            <i class="bi bi-lock-fill"></i>
                            Đóng tin
                        </button>

                        <button v-else type="button" @click="deleteListing(ls.id)"
                            class="px-3.5 py-2 hover:bg-rose-50 text-rose-500 rounded-xl transition-colors flex items-center justify-center"
                            title="Xóa vĩnh viễn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- PHÂN TRANG -->
        <div v-if="listings.links && listings.links.length > 3" class="flex justify-center gap-2 pt-6">
            <Component :is="link.url ? Link : 'span'" v-for="(link, index) in listings.links" :key="index"
                :href="link.url" v-html="link.label" preserve-scroll :class="[
                    'px-3 py-2 rounded-lg text-xs border transition-colors',
                    link.active
                        ? 'bg-emerald-500 text-white border-emerald-500 font-bold'
                        : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50',
                    !link.url && 'opacity-50 cursor-not-allowed',
                ]" />
        </div>

        <!-- Modal Popup Lịch sử đẩy tin -->
        <div v-if="showHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-200">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="font-extrabold text-slate-800 text-base flex items-center gap-2">
                        <i class="bi bi-clock-history text-emerald-600"></i>
                        Nhật Ký Đẩy Tin Đăng
                    </h3>
                    <button @click="showHistoryModal = false" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>

                <div v-if="isLoadingLogs" class="py-8 text-center text-slate-400 text-xs font-semibold">
                    <i class="bi bi-arrow-repeat animate-spin text-xl block mb-2"></i> Đang tải lịch sử...
                </div>
                
                <div v-else-if="bumpLogs.length === 0" class="py-8 text-center text-slate-400 text-xs">
                    Chưa có lịch sử đẩy tin nào.
                </div>

                <div v-else class="max-h-80 overflow-y-auto space-y-2.5 pr-1">
                    <div v-for="log in bumpLogs" :key="log.id" class="p-3 bg-slate-50 rounded-2xl flex items-center justify-between gap-3 text-xs border border-slate-100">
                        <div class="space-y-1 flex-1">
                            <p class="font-bold text-slate-800 line-clamp-1">{{ log.title }}</p>
                            <span class="text-[10px] text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md font-semibold border border-emerald-100">
                                {{ log.package_name }}
                            </span>
                        </div>
                        <span class="text-[10px] text-slate-400 font-semibold flex items-center gap-1 shrink-0">
                            <i class="bi bi-calendar-event"></i> {{ log.bumped_at }}
                        </span>
                    </div>
                </div>

                <div class="pt-2 text-right">
                    <button @click="showHistoryModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>