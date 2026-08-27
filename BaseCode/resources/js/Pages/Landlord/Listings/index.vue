<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { showConfirm } from "@/Utils/swal";
import { getStatusLabel, getStatusClass } from "@/Utils/statusHelper";

const props = defineProps({
    listings: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const activeTab = ref("all"); // 'all' | 'approved' | 'pending'  | 'draft'
const showPackageModal = ref(false);
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

const deleteListing = (id) => {
    if (confirm("Bạn có chắc chắn muốn xóa bài đăng này?")) {

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

const bumpListing = (id) => {
    if (confirm("Mỗi lần đẩy tin sẽ trừ 1 lượt đẩy trong tài khoản của bạn. Xác nhận đẩy tin đăng này lên đầu trang?")) {
        router.post(route("landlord.listings.bump", id));
    }
};

const buyPackage = (pkg) => {
    router.post(route("landlord.listings.buy-package"), { package: pkg }, {
        onSuccess: () => {
            showPackageModal.value = false;
        }
    });
//hàm kiểm tra phòng đã lấp đầy
const isRoomFull = (ls) => {
    if (!ls || !ls.room) return false;
    const capacity = ls.room.capacity || 1;
    const currentPeople = ls.room.current_people || 0;
    return currentPeople >= capacity;
};
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
            <div class="p-6 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-3xl text-white shadow-xl shadow-emerald-500/10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 bg-white/20 backdrop-blur-md rounded-lg text-[10px] font-bold uppercase tracking-wider">
                            {{ user?.package_name || 'Chưa mua gói' }}
                        </span>
                    </div>
                    <h3 class="text-lg font-extrabold flex items-center gap-1">
                        Số lượt đẩy tin còn lại: <span class="text-yellow-300 text-2xl font-black ml-1">{{ user?.bump_credits || 0 }}</span> lượt
                    </h3>
                    <p class="text-[11px] text-emerald-100/90 max-w-xl leading-relaxed">
                        Đẩy tin giúp bài đăng của bạn ngay lập tức đứng đầu danh sách hiển thị trên trang chủ Ninh Bình Homestay và trang tìm kiếm để tiếp cận nhiều khách thuê hơn.
                    </p>
                </div>
                <button @click="showPackageModal = true"
                    class="px-5 py-3 bg-white hover:bg-emerald-50 text-emerald-600 font-bold text-xs rounded-xl transition-all shadow-md shadow-black/5 flex items-center gap-1.5 self-stretch md:self-auto justify-center">
                    <i class="bi bi-gem text-amber-500"></i>
                    Mua thêm lượt đẩy
                </button>
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
                            <span v-if="ls.bump_count > 0" class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-100 flex items-center gap-1">
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

        <!-- Modal Mua Gói Đẩy Tin -->
        <div v-if="showPackageModal" class="fixed inset-0 z-50 overflow-y-auto animate-fade-in" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showPackageModal = false"></div>

                <!-- Center elements -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-[32px] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-100">
                    <div class="p-8">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                                <i class="bi bi-gem text-emerald-500 text-lg"></i>
                                Chọn gói đẩy tin đăng quảng cáo
                            </h3>
                            <button @click="showPackageModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </div>

                        <p class="text-xs text-slate-400 mb-8 font-medium">
                            Giúp tin đăng của bạn luôn hiển thị nổi bật ở các vị trí vàng trên trang chủ Ninh Bình Homestay. Thanh toán giả lập (mock payment) ngay lập tức cộng lượt đẩy vào tài khoản.
                        </p>

                        <!-- Gói Cước Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                            <!-- Gói Cơ bản -->
                            <div @click="selectedPackage = 'standard'" 
                                :class="[
                                    'p-5 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between gap-4 h-full relative overflow-hidden',
                                    selectedPackage === 'standard' 
                                        ? 'border-emerald-500 bg-emerald-50/20 shadow-lg shadow-emerald-500/5' 
                                        : 'border-slate-100 hover:border-slate-200 bg-white'
                                ]">
                                <div class="space-y-1">
                                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cơ bản</h4>
                                    <div class="text-2xl font-black text-slate-800">10 <span class="text-xs font-semibold text-slate-500">lượt</span></div>
                                    <p class="text-[10px] text-slate-400 leading-relaxed font-medium">Phù hợp cho chủ trọ ít phòng.</p>
                                </div>
                                <div class="text-sm font-extrabold text-emerald-600 mt-2">50.000đ</div>
                                <span v-if="selectedPackage === 'standard'" class="absolute top-2 right-2 text-emerald-500"><i class="bi bi-check-circle-fill"></i></span>
                            </div>

                            <!-- Gói Phổ thông -->
                            <div @click="selectedPackage = 'premium'" 
                                :class="[
                                    'p-5 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between gap-4 h-full relative overflow-hidden',
                                    selectedPackage === 'premium' 
                                        ? 'border-emerald-500 bg-emerald-50/20 shadow-lg shadow-emerald-500/5' 
                                        : 'border-slate-100 hover:border-slate-200 bg-white'
                                ]">
                                <div class="absolute top-0 right-0 bg-amber-500 text-white text-[8px] font-black uppercase px-2 py-0.5 rounded-bl-lg tracking-wider">Bán chạy</div>
                                <div class="space-y-1">
                                    <h4 class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">Phổ thông</h4>
                                    <div class="text-2xl font-black text-slate-800">30 <span class="text-xs font-semibold text-slate-500">lượt</span></div>
                                    <p class="text-[10px] text-slate-400 leading-relaxed font-medium">Lựa chọn tối ưu chi phí.</p>
                                </div>
                                <div class="text-sm font-extrabold text-emerald-600 mt-2">120.000đ</div>
                                <span v-if="selectedPackage === 'premium'" class="absolute top-2 right-2 text-emerald-500"><i class="bi bi-check-circle-fill"></i></span>
                            </div>

                            <!-- Gói Đặc quyền -->
                            <div @click="selectedPackage = 'vip'" 
                                :class="[
                                    'p-5 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between gap-4 h-full relative overflow-hidden',
                                    selectedPackage === 'vip' 
                                        ? 'border-emerald-500 bg-emerald-50/20 shadow-lg shadow-emerald-500/5' 
                                        : 'border-slate-100 hover:border-slate-200 bg-white'
                                ]">
                                <div class="space-y-1">
                                    <h4 class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Đặc quyền</h4>
                                    <div class="text-2xl font-black text-slate-800">100 <span class="text-xs font-semibold text-slate-500">lượt</span></div>
                                    <p class="text-[10px] text-slate-400 leading-relaxed font-medium">Dành cho hệ thống nhiều phòng.</p>
                                </div>
                                <div class="text-sm font-extrabold text-emerald-600 mt-2">300.000đ</div>
                                <span v-if="selectedPackage === 'vip'" class="absolute top-2 right-2 text-emerald-500"><i class="bi bi-check-circle-fill"></i></span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 justify-end">
                            <button type="button" @click="showPackageModal = false"
                                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition-colors">
                                Hủy bỏ
                            </button>
                            <button type="button" @click="buyPackage(selectedPackage)"
                                class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5 shadow-md shadow-emerald-500/10">
                                <i class="bi bi-wallet2"></i>
                                Thanh toán giả lập
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
