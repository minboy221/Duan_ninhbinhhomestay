<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";

const props = defineProps({
    listings: Object,
});

const activeTab = ref("all"); // 'all' | 'approved' | 'pending'  | 'draft'
const flashSuccess = computed(() => usePage().props.flash?.success);
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
                        <div class="flex items-center gap-4 text-[10px] text-slate-400 font-bold">
                            <span><i class="bi bi-eye mr-1"></i>
                                {{ ls.view_count }} lượt xem</span>
                            <span><i class="bi bi-calendar3 mr-1"></i> Ngày tạo:
                                {{
                                    new Date(ls.created_at).toLocaleDateString(
                                        "vi-VN",
                                    )
                                }}</span>
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
    </LandlordLayout>
</template>
