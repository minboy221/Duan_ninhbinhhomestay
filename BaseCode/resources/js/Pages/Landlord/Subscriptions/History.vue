<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import LandlordLayout from "@/Layouts/LandlordLayout.vue";

const props = defineProps({
    history: Object,
});

const selectedImage = ref(null);

const formatDate = (dateStr) => {
    if (!dateStr) return "---";
    const d = new Date(dateStr);
    return d.toLocaleDateString("vi-VN");
};

const formatMoney = (val) =>
    new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(val);
</script>

<template>

    <Head title="Lịch Sử Mua Gói Dịch Vụ" />

    <LandlordLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2">
                        <i class="bi bi-clock-history text-indigo-600"></i> Lịch Sử Mua Gói Dịch Vụ
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Theo dõi danh sách hóa đơn, trạng thái xét duyệt và thời hạn các gói dịch vụ bạn đã mua.
                    </p>
                </div>

                <Link :href="route('landlord.subscriptions.index')"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center gap-2 w-fit">
                    <i class="bi bi-cart-plus-fill"></i> Đăng Ký Mua Gói Mới
                </Link>
            </div>

            <!-- Bảng Lịch Sử -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr
                                class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider">
                                <th class="p-4">Gói Dịch Vụ</th>
                                <th class="p-4">Mã Giao Dịch</th>
                                <th class="p-4">Giá Tiền</th>
                                <th class="p-4">Thời Gian Hiệu Lực</th>
                                <th class="p-4">Ảnh Bill</th>
                                <th class="p-4">Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in history.data" :key="item.id"
                                class="hover:bg-slate-50/50 transition-colors">
                                <!-- Gói dịch vụ -->
                                <td class="p-4 font-bold text-slate-800">
                                    {{ item.plan?.name || "Gói dịch vụ" }}
                                </td>

                                <!-- Mã giao dịch -->
                                <td class="p-4 font-mono font-bold text-rose-600">
                                    {{ item.payment_code || "---" }}
                                </td>

                                <!-- Giá tiền -->
                                <td class="p-4 font-extrabold text-slate-900">
                                    {{ item.price_at_purchase == 0 ? "Miễn phí" : formatMoney(item.price_at_purchase) }}
                                </td>

                                <!-- Thời gian -->
                                <td class="p-4 text-slate-600">
                                    <div v-if="item.status === 'active'">
                                        <span class="font-semibold text-emerald-600">{{ formatDate(item.start_date)
                                            }}</span>
                                        <span class="mx-1 text-slate-400">➔</span>
                                        <span class="font-semibold text-rose-600">{{ formatDate(item.end_date) }}</span>
                                    </div>
                                    <span v-else class="text-slate-400">Chưa kích hoạt</span>
                                </td>

                                <!-- Ảnh bill -->
                                <td class="p-4">
                                    <button v-if="item.proof_image" @click="selectedImage = item.proof_image"
                                        class="px-2.5 py-1 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg font-bold text-[10px] flex items-center gap-1 transition-all">
                                        <i class="bi bi-image"></i> Xem Bill
                                    </button>
                                    <span v-else class="text-slate-400 italic">Chưa có ảnh</span>
                                </td>

                                <!-- Trạng thái -->
                                <td class="p-4">
                                    <span v-if="item.status === 'active'"
                                        class="px-3 py-1 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-full font-bold text-[10px] flex items-center gap-1 w-fit">
                                        <i class="bi bi-check-circle-fill"></i> Đã Kích Hoạt
                                    </span>
                                    <span v-else-if="item.status === 'pending'"
                                        class="px-3 py-1 bg-amber-50 text-amber-600 border border-amber-200 rounded-full font-bold text-[10px] flex items-center gap-1 w-fit">
                                        <i class="bi bi-clock-history"></i> Chờ Admin Duyệt
                                    </span>
                                    <div v-else-if="item.status === 'rejected'" class="space-y-1">
                                        <span
                                            class="px-3 py-1 bg-rose-50 text-rose-600 border border-rose-200 rounded-full font-bold text-[10px] flex items-center gap-1 w-fit">
                                            <i class="bi bi-x-circle-fill"></i> Bị Từ Chối
                                        </span>
                                        <p v-if="item.admin_note" class="text-[10px] text-rose-500 font-medium italic">
                                            Lý do: {{ item.admin_note }}
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <!-- Trạng thái rỗng -->
                            <tr v-if="!history.data.length">
                                <td colspan="6" class="text-center p-12 text-slate-400">
                                    <i class="bi bi-inbox text-3xl block mb-2"></i>
                                    Bạn chưa có lịch sử mua gói dịch vụ nào.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Component Phân Trang (Pagination) -->
                <div v-if="history.links && history.links.length > 3"
                    class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                    
                    <!-- Số lượng bản ghi -->
                    <div class="text-xs text-slate-500">
                        Hiển thị từ <span class="font-bold text-slate-800">{{ history.from || 0 }}</span> 
                        đến <span class="font-bold text-slate-800">{{ history.to || 0 }}</span> 
                        trong tổng số <span class="font-bold text-slate-800">{{ history.total || 0 }}</span> đơn mua gói
                    </div>

                    <!-- Nút chuyển trang -->
                    <div class="flex items-center gap-1 flex-wrap">
                        <template v-for="(link, key) in history.links" :key="key">
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
        </div>

        <!-- Modal Xem Ảnh Bill -->
        <div v-if="selectedImage"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl max-w-lg w-full p-4 shadow-2xl relative">
                <button @click="selectedImage = null"
                    class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 text-lg">
                    <i class="bi bi-x-lg"></i>
                </button>
                <h3 class="text-sm font-bold text-slate-800 mb-3">Hóa Đơn Chuyển Khoản</h3>
                <img :src="selectedImage" alt="Bill Chuyển Khoản"
                    class="w-full h-auto rounded-xl border border-slate-200 object-contain max-h-[70vh]" />
            </div>
        </div>
    </LandlordLayout>
</template>
