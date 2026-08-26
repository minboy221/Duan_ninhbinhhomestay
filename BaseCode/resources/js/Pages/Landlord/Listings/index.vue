<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";

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
        router.delete(route("landlord.listings.destroy", id));
    }
};

// Hàm đóng tin đăng
const closeListing = (id, title) => {
    if (
        confirm(
            `xác nhận xoá tin đăng: "${title}"?\nSau khi đóng khách thuê sẽ không tìm thấy tin đăng này nữa`,
        )
    ) {
        router.post(route("landlord.listings.close", id));
    }
};

//Hàm xoá tin đăng
const handleDeletePost = (id) => {
    if (
        confirm(
            "Bạn có chắc chắn muốn xoá vĩnh viễn bài đăng này không? Hành động này không thể hoàn tác ",
        )
    ) {
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
                    </div>

                    <!-- Middle Info Body -->
                    <div class="p-6 flex-1 flex flex-col justify-between gap-4">
                        <div class="space-y-2">
                            <h3 class="text-sm font-bold text-slate-800 leading-snug">
                                {{ ls.title }}
                            </h3>
                            <div
                                class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-400 font-semibold">
                                <span class="flex items-center gap-1"><i class="bi bi-house-door text-emerald-600"></i>
                                    {{ ls.room?.room_number }}</span>
                                <span class="flex items-center gap-1"><i
                                        class="bi bi-aspect-ratio text-emerald-600"></i>
                                    {{ ls.room?.area }} m²</span>
                                <span class="flex items-center gap-1"><i class="bi bi-geo-alt text-emerald-600"></i>
                                    {{ ls.room?.boarding_house?.name }}</span>
                            </div>
                        </div>

                        <!-- Price & AI suggestions -->
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="text-slate-800 font-black text-base">
                                {{ formatMoney(ls.room?.price || 0)
                                }}<span class="text-[10px] text-slate-400 font-bold">/tháng</span>
                            </div>

                            <!-- AI Pricing Suggestion -->
                            <!-- <div
                                class="px-2.5 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-xl text-[10px] font-bold flex items-center gap-1.5">
                                <i class="bi bi-stars text-emerald-500"></i>
                                <span>Giá AI gợi ý:
                                    <strong>{{
                                        formatMoney(ls.aiPrice)
                                    }}</strong></span>
                                <span :class="ls.aiPrice > ls.price
                                    ? 'text-blue-600'
                                    : 'text-emerald-700'
                                    ">
                                    {{
                                        ls.aiPrice > ls.price
                                            ? "(Có thể tăng giá)"
                                            : "(Giá tốt)"
                                    }}
                                </span>
                            </div> -->
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
                        <!-- 1. Nút Chỉnh sửa: Luôn luôn hiển thị -->
                        <Link :href="route('landlord.listings.edit', ls.id)"
                            class="flex-1 md:flex-none px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1">
                            <i class="bi bi-pencil-square"></i>
                            Chỉnh sửa
                        </Link>

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
                :href="link.url" v-html="link.label" :class="[
                    'px-3 py-2 rounded-lg text-xs border',
                    link.active
                        ? 'bg-emerald-500 text-white border-emerald-500'
                        : 'bg-white text-slate-500 border-slate-200',
                    !link.url && 'opacity-50',
                ]" />
        </div>

        <!-- Modal Mua Gói Đẩy Tin -->
        <div v-if="showPackageModal" class="fixed inset-0 z-50 overflow-y-auto animate-fade-in" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showPackageModal = false"></div>

                <!-- Center elements -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-[32px] text-left overflow-visible shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-100">
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
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 py-4">
                            <!-- Gói Cơ bản -->
                            <div class="package-card-wrapper" 
                                :class="{ 'active': selectedPackage === 'standard' }"
                                style="--neon-c1: #e2e8f0; --neon-c2: #0ea5e9; --neon-c3: #ffffff; --wing-glow-shadow: 0 0 15px rgba(255, 255, 255, 0.85);">
                                <!-- Wings behind -->
                                <div class="wings-container">
                                    <svg class="angel-wing left-wing" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                        <defs>
                                            <linearGradient id="white-wing-grad" x1="100%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#ffffff" />
                                                <stop offset="60%" stop-color="#f8fafc" />
                                                <stop offset="100%" stop-color="#cbd5e1" />
                                            </linearGradient>
                                        </defs>
                                        <path d="M 180,90 C 150,50 110,30 70,40 C 50,45 30,60 15,80 C 5,90 2,105 7,120 C 12,130 25,140 45,140 C 30,148 20,158 20,168 C 20,178 35,182 60,175 C 45,185 40,195 45,202 C 50,208 65,205 85,190 C 75,200 75,208 80,210 C 85,212 105,195 125,175 C 145,155 170,125 180,90 Z" fill="url(#white-wing-grad)" />
                                        <path d="M 150,85 C 120,65 85,55 65,60 C 55,62 45,70 35,80" stroke="rgba(255,255,255,0.7)" stroke-width="2" fill="none" />
                                        <path d="M 130,105 C 100,90 75,85 60,90" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" fill="none" />
                                    </svg>
                                    <svg class="angel-wing right-wing" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M 180,90 C 150,50 110,30 70,40 C 50,45 30,60 15,80 C 5,90 2,105 7,120 C 12,130 25,140 45,140 C 30,148 20,158 20,168 C 20,178 35,182 60,175 C 45,185 40,195 45,202 C 50,208 65,205 85,190 C 75,200 75,208 80,210 C 85,212 105,195 125,175 C 145,155 170,125 180,90 Z" fill="url(#white-wing-grad)" />
                                        <path d="M 150,85 C 120,65 85,55 65,60 C 55,62 45,70 35,80" stroke="rgba(255,255,255,0.7)" stroke-width="2" fill="none" />
                                        <path d="M 130,105 C 100,90 75,85 60,90" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" fill="none" />
                                    </svg>
                                </div>
                                <!-- Glow -->
                                <div class="glow-bg"></div>
                                <!-- Neon border -->
                                <div class="neon-border"></div>
                                <!-- Feathers falling down -->
                                <div class="feathers-container">
                                    <div class="feather f-1">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-2">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-3">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-4">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-5">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </div>
                                <!-- Particles -->
                                <div class="particles-container">
                                    <span class="particle p-1"></span>
                                    <span class="particle p-2"></span>
                                    <span class="particle p-3"></span>
                                    <span class="particle p-4"></span>
                                    <span class="particle p-5"></span>
                                </div>
                                <!-- Inner card -->
                                <div @click="selectedPackage = 'standard'" 
                                    :class="[
                                        'package-card-inner p-5 rounded-2xl border-2 cursor-pointer flex flex-col justify-between gap-4 h-full relative overflow-hidden bg-white',
                                        selectedPackage === 'standard' 
                                            ? 'border-transparent shadow-lg shadow-sky-500/5' 
                                            : 'border-slate-100 hover:border-slate-200'
                                    ]">
                                    <div class="space-y-1 relative z-10">
                                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cơ bản</h4>
                                        <div class="text-2xl font-black text-slate-800">10 <span class="text-xs font-semibold text-slate-500">lượt</span></div>
                                        <p class="text-[10px] text-slate-400 leading-relaxed font-medium">Phù hợp cho chủ trọ ít phòng.</p>
                                    </div>
                                    <div class="text-sm font-extrabold text-emerald-600 mt-2 relative z-10">50.000đ</div>
                                    <span v-if="selectedPackage === 'standard'" class="absolute top-2 right-2 text-emerald-500 z-20"><i class="bi bi-check-circle-fill"></i></span>
                                </div>
                            </div>

                            <!-- Gói Phổ thông -->
                            <div class="package-card-wrapper" 
                                :class="{ 'active': selectedPackage === 'premium' }"
                                style="--neon-c1: #c084fc; --neon-c2: #3b82f6; --neon-c3: #22d3ee; --wing-glow-shadow: 0 0 18px rgba(255, 255, 255, 0.95);">
                                <!-- Wings behind -->
                                <div class="wings-container">
                                    <svg class="angel-wing left-wing" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M 180,90 C 150,50 110,30 70,40 C 50,45 30,60 15,80 C 5,90 2,105 7,120 C 12,130 25,140 45,140 C 30,148 20,158 20,168 C 20,178 35,182 60,175 C 45,185 40,195 45,202 C 50,208 65,205 85,190 C 75,200 75,208 80,210 C 85,212 105,195 125,175 C 145,155 170,125 180,90 Z" fill="url(#white-wing-grad)" />
                                        <path d="M 150,85 C 120,65 85,55 65,60 C 55,62 45,70 35,80" stroke="rgba(255,255,255,0.7)" stroke-width="2" fill="none" />
                                        <path d="M 130,105 C 100,90 75,85 60,90" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" fill="none" />
                                    </svg>
                                    <svg class="angel-wing right-wing" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M 180,90 C 150,50 110,30 70,40 C 50,45 30,60 15,80 C 5,90 2,105 7,120 C 12,130 25,140 45,140 C 30,148 20,158 20,168 C 20,178 35,182 60,175 C 45,185 40,195 45,202 C 50,208 65,205 85,190 C 75,200 75,208 80,210 C 85,212 105,195 125,175 C 145,155 170,125 180,90 Z" fill="url(#white-wing-grad)" />
                                        <path d="M 150,85 C 120,65 85,55 65,60 C 55,62 45,70 35,80" stroke="rgba(255,255,255,0.7)" stroke-width="2" fill="none" />
                                        <path d="M 130,105 C 100,90 75,85 60,90" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" fill="none" />
                                    </svg>
                                </div>
                                <!-- Glow -->
                                <div class="glow-bg"></div>
                                <!-- Neon border -->
                                <div class="neon-border"></div>
                                <!-- Feathers falling down -->
                                <div class="feathers-container">
                                    <div class="feather f-1">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-2">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-3">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-4">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-5">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </div>
                                <!-- Particles -->
                                <div class="particles-container">
                                    <span class="particle p-1"></span>
                                    <span class="particle p-2"></span>
                                    <span class="particle p-3"></span>
                                    <span class="particle p-4"></span>
                                    <span class="particle p-5"></span>
                                </div>
                                <!-- Inner card -->
                                <div @click="selectedPackage = 'premium'" 
                                    :class="[
                                        'package-card-inner p-5 rounded-2xl border-2 cursor-pointer flex flex-col justify-between gap-4 h-full relative overflow-hidden bg-white',
                                        selectedPackage === 'premium' 
                                            ? 'border-transparent shadow-lg shadow-purple-500/5' 
                                            : 'border-slate-100 hover:border-slate-200'
                                    ]">
                                    <div class="absolute top-0 right-0 bg-amber-500 text-white text-[8px] font-black uppercase px-2 py-0.5 rounded-bl-lg tracking-wider z-20">Bán chạy</div>
                                    <div class="space-y-1 relative z-10">
                                        <h4 class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">Phổ thông</h4>
                                        <div class="text-2xl font-black text-slate-800">30 <span class="text-xs font-semibold text-slate-500">lượt</span></div>
                                        <p class="text-[10px] text-slate-400 leading-relaxed font-medium">Lựa chọn tối ưu chi phí.</p>
                                    </div>
                                    <div class="text-sm font-extrabold text-emerald-600 mt-2 relative z-10">120.000đ</div>
                                    <span v-if="selectedPackage === 'premium'" class="absolute top-2 right-2 text-emerald-500 z-20"><i class="bi bi-check-circle-fill"></i></span>
                                </div>
                            </div>

                            <!-- Gói Đặc quyền -->
                            <div class="package-card-wrapper" 
                                :class="{ 'active': selectedPackage === 'vip' }"
                                style="--neon-c1: #fde047; --neon-c2: #f97316; --neon-c3: #ef4444; --wing-glow-shadow: 0 0 22px rgba(255, 255, 255, 0.98);">
                                <!-- Wings behind -->
                                <div class="wings-container">
                                    <svg class="angel-wing left-wing" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M 180,90 C 150,50 110,30 70,40 C 50,45 30,60 15,80 C 5,90 2,105 7,120 C 12,130 25,140 45,140 C 30,148 20,158 20,168 C 20,178 35,182 60,175 C 45,185 40,195 45,202 C 50,208 65,205 85,190 C 75,200 75,208 80,210 C 85,212 105,195 125,175 C 145,155 170,125 180,90 Z" fill="url(#white-wing-grad)" />
                                        <path d="M 150,85 C 120,65 85,55 65,60 C 55,62 45,70 35,80" stroke="rgba(255,255,255,0.7)" stroke-width="2" fill="none" />
                                        <path d="M 130,105 C 100,90 75,85 60,90" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" fill="none" />
                                    </svg>
                                    <svg class="angel-wing right-wing" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M 180,90 C 150,50 110,30 70,40 C 50,45 30,60 15,80 C 5,90 2,105 7,120 C 12,130 25,140 45,140 C 30,148 20,158 20,168 C 20,178 35,182 60,175 C 45,185 40,195 45,202 C 50,208 65,205 85,190 C 75,200 75,208 80,210 C 85,212 105,195 125,175 C 145,155 170,125 180,90 Z" fill="url(#white-wing-grad)" />
                                        <path d="M 150,85 C 120,65 85,55 65,60 C 55,62 45,70 35,80" stroke="rgba(255,255,255,0.7)" stroke-width="2" fill="none" />
                                        <path d="M 130,105 C 100,90 75,85 60,90" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" fill="none" />
                                    </svg>
                                </div>
                                <!-- Glow -->
                                <div class="glow-bg"></div>
                                <!-- Neon border -->
                                <div class="neon-border"></div>
                                <!-- Feathers falling down -->
                                <div class="feathers-container">
                                    <div class="feather f-1">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-2">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-3">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-4">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="feather f-5">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2C12 2 10 5 8 8C6 11 5 14 7 17C9 20 12 21 14 20C17 19 18 16 16 13C14 10 12 2 12 2Z" fill="rgba(255, 255, 255, 0.85)" />
                                            <path d="M12 2C11.5 5 10 8 9.5 11" stroke="rgba(226, 232, 240, 0.5)" stroke-width="1.2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                </div>
                                <!-- Particles -->
                                <div class="particles-container">
                                    <span class="particle p-1"></span>
                                    <span class="particle p-2"></span>
                                    <span class="particle p-3"></span>
                                    <span class="particle p-4"></span>
                                    <span class="particle p-5"></span>
                                </div>
                                <!-- Inner card -->
                                <div @click="selectedPackage = 'vip'" 
                                    :class="[
                                        'package-card-inner p-5 rounded-2xl border-2 cursor-pointer flex flex-col justify-between gap-4 h-full relative overflow-hidden bg-white',
                                        selectedPackage === 'vip' 
                                            ? 'border-transparent shadow-lg shadow-orange-500/5' 
                                            : 'border-slate-100 hover:border-slate-200'
                                    ]">
                                    <div class="space-y-1 relative z-10">
                                        <h4 class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Đặc quyền</h4>
                                        <div class="text-2xl font-black text-slate-800">100 <span class="text-xs font-semibold text-slate-500">lượt</span></div>
                                        <p class="text-[10px] text-slate-400 leading-relaxed font-medium">Dành cho hệ thống nhiều phòng.</p>
                                    </div>
                                    <div class="text-sm font-extrabold text-emerald-600 mt-2 relative z-10">300.000đ</div>
                                    <span v-if="selectedPackage === 'vip'" class="absolute top-2 right-2 text-emerald-500 z-20"><i class="bi bi-check-circle-fill"></i></span>
                                </div>
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

<style scoped>
/* Card Container Wrapper */
.package-card-wrapper {
  position: relative;
  height: 100%;
  perspective: 1000px;
  z-index: 1;
}

/* 3D Hover & Lift */
.package-card-inner {
  position: relative;
  height: 100%;
  z-index: 10;
  transition: transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.5s ease, border-color 0.3s ease;
  transform-style: preserve-3d;
}

.package-card-wrapper:hover .package-card-inner {
  transform: translateY(-8px) rotateX(4deg) rotateY(-4deg);
}

.package-card-wrapper.active .package-card-inner {
  transform: translateY(-4px) scale(1.01);
}

/* Wing Styling */
.wings-container {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  z-index: 2;
  pointer-events: none;
}

.angel-wing {
  position: absolute;
  top: -15px;
  width: 130px;
  height: 130px;
  opacity: 0;
  transition: transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.6s ease, filter 0.6s ease;
}

.left-wing {
  right: 52%;
  transform: translateX(30px) scale(0.4) rotate(-35deg);
  transform-origin: right center;
}

.right-wing {
  left: 52%;
  transform: translateX(-30px) scale(0.4) rotate(35deg) scaleX(-1);
  transform-origin: left center;
}

/* Hover/Active states for wings */
.package-card-wrapper:hover .left-wing,
.package-card-wrapper.active .left-wing {
  opacity: 0.95;
  transform: translateX(-105px) scale(1.2) rotate(5deg);
  filter: drop-shadow(var(--wing-glow-shadow));
  animation: wingFlopLeft 3s ease-in-out infinite alternate;
}

.package-card-wrapper:hover .right-wing,
.package-card-wrapper.active .right-wing {
  opacity: 0.95;
  transform: translateX(105px) scale(1.2) rotate(-5deg) scaleX(-1);
  filter: drop-shadow(var(--wing-glow-shadow));
  animation: wingFlopRight 3s ease-in-out infinite alternate;
}

@keyframes wingFlopLeft {
  0% { transform: translateX(-105px) scale(1.2) rotate(5deg); }
  100% { transform: translateX(-108px) scale(1.23) rotate(12deg); }
}

@keyframes wingFlopRight {
  0% { transform: translateX(105px) scale(1.2) rotate(-5deg) scaleX(-1); }
  100% { transform: translateX(108px) scale(1.23) rotate(-12deg) scaleX(-1); }
}

/* Feathers falling down */
.feathers-container {
  position: absolute;
  inset: 0;
  z-index: 11;
  pointer-events: none;
  overflow: hidden;
  border-radius: 16px;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.package-card-wrapper:hover .feathers-container,
.package-card-wrapper.active .feathers-container {
  opacity: 1;
}

.feather {
  position: absolute;
  top: -20px;
  width: 16px;
  height: 16px;
  opacity: 0;
}

.package-card-wrapper:hover .feather,
.package-card-wrapper.active .feather {
  animation: featherFall var(--fall-dur) infinite linear;
  animation-delay: var(--fall-delay);
}

.f-1 { left: 15%; --fall-dur: 3.5s; --fall-delay: 0.2s; transform: rotate(15deg); }
.f-2 { left: 35%; --fall-dur: 4.2s; --fall-delay: 1.0s; transform: rotate(-20deg); }
.f-3 { left: 55%; --fall-dur: 3.8s; --fall-delay: 0.5s; transform: rotate(10deg); }
.f-4 { left: 75%; --fall-dur: 4.5s; --fall-delay: 1.8s; transform: rotate(-15deg); }
.f-5 { left: 90%; --fall-dur: 3.9s; --fall-delay: 0.8s; transform: rotate(25deg); }

@keyframes featherFall {
  0% {
    transform: translateY(-20px) translateX(0) rotate(0deg) scale(0.6);
    opacity: 0;
  }
  10% {
    opacity: 0.9;
  }
  50% {
    transform: translateY(80px) translateX(15px) rotate(45deg) scale(0.85);
  }
  80% {
    opacity: 0.9;
  }
  100% {
    transform: translateY(220px) translateX(-15px) rotate(90deg) scale(0.7);
    opacity: 0;
  }
}

/* Neon Border Glow */
.neon-border {
  position: absolute;
  inset: -2px;
  border-radius: 18px;
  background: linear-gradient(90deg, var(--neon-c1), var(--neon-c2), var(--neon-c3), var(--neon-c1));
  background-size: 300% 300%;
  z-index: 1;
  opacity: 0;
  transition: opacity 0.5s ease;
  pointer-events: none;
}

.package-card-wrapper:hover .neon-border {
  opacity: 0.45;
  animation: neonFlow 4s linear infinite;
}

.package-card-wrapper.active .neon-border {
  opacity: 1;
  animation: neonFlow 3s linear infinite;
}

@keyframes neonFlow {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Glow Effect Backing */
.glow-bg {
  position: absolute;
  inset: -5px;
  border-radius: 20px;
  background: radial-gradient(circle, var(--neon-c2) 0%, transparent 70%);
  z-index: 0;
  opacity: 0;
  filter: blur(15px);
  transition: opacity 0.5s ease, transform 0.5s ease;
  pointer-events: none;
}

.package-card-wrapper:hover .glow-bg {
  opacity: 0.3;
  transform: scale(1.05);
}

.package-card-wrapper.active .glow-bg {
  opacity: 0.7;
  transform: scale(1.1);
  animation: glowPulse 2s ease-in-out infinite alternate;
}

@keyframes glowPulse {
  0% { filter: blur(12px); opacity: 0.5; }
  100% { filter: blur(18px); opacity: 0.8; }
}

/* Particles Container & Styling */
.particles-container {
  position: absolute;
  inset: 0;
  z-index: 12;
  overflow: hidden;
  border-radius: 16px;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.4s ease;
}

.package-card-wrapper:hover .particles-container,
.package-card-wrapper.active .particles-container {
  opacity: 1;
}

.particle {
  position: absolute;
  bottom: -10px;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--neon-c2);
  box-shadow: 0 0 8px var(--neon-c1);
  opacity: 0;
}

.p-1 { left: 15%; width: 4px; height: 4px; animation: floatUp 2.2s infinite ease-in; animation-delay: 0.1s; }
.p-2 { left: 35%; width: 3px; height: 3px; animation: floatUp 2.8s infinite ease-in; animation-delay: 0.7s; }
.p-3 { left: 55%; width: 5px; height: 5px; animation: floatUp 2.0s infinite ease-in; animation-delay: 0.3s; }
.p-4 { left: 75%; width: 3px; height: 3px; animation: floatUp 2.5s infinite ease-in; animation-delay: 1.1s; }
.p-5 { left: 85%; width: 4px; height: 4px; animation: floatUp 1.8s infinite ease-in; animation-delay: 0.5s; }

@keyframes floatUp {
  0% {
    transform: translateY(0) scale(0.5);
    opacity: 0;
  }
  30% {
    opacity: 0.8;
  }
  100% {
    transform: translateY(-130px) scale(1.3);
    opacity: 0;
  }
}
</style>
