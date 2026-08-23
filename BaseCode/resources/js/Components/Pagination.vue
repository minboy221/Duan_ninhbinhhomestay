<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    // Mảng liên kết phân trang trả về từ Laravel Paginate
    links: {
        type: Array,
        default: () => [],
    },
    // Giữ nguyên vị trí cuộn trang khi bấm chuyển trang
    preserveScroll: {
        type: Boolean,
        default: true,
    },
});

// Chuyển đổi nhãn tiếng Anh mặc định sang ký tự gọn đẹp « »
const cleanLabel = (label) => {
    if (!label) return "";
    return label
        .replace("&laquo; Previous", "«")
        .replace("Next &raquo;", "»")
        .replace("&laquo;", "«")
        .replace("&raquo;", "»");
};
</script>

<template>
    <!-- Chỉ hiển thị cụm phân trang nếu có nhiều hơn 1 trang -->
    <div
        v-if="links && links.length > 3"
        class="flex justify-center items-center gap-1.5 my-6 select-none"
    >
        <template v-for="(link, index) in links" :key="index">
            <!-- Nút bấm trang có đường dẫn (Link hoạt động) -->
            <Link
                v-if="link.url"
                :href="link.url"
                :preserve-scroll="preserveScroll"
                :class="[
                    'px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 flex items-center justify-center min-w-[36px] h-9',
                    link.active
                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                        : 'bg-white text-slate-700 hover:bg-slate-100 hover:text-blue-600 border border-slate-200/80',
                ]"
            >
                {{ cleanLabel(link.label) }}
            </Link>

            <!-- Nút bấm bị vô hiệu hóa (Trang đầu / Trang cuối) -->
            <span
                v-else
                class="px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center justify-center min-w-[36px] h-9 border border-slate-100 text-slate-300 bg-slate-50 cursor-not-allowed"
            >
                {{ cleanLabel(link.label) }}
            </span>
        </template>
    </div>
</template>
