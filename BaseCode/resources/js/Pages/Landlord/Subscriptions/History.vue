<script setup>
import { ref, computed } from "vue";
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

const activeCount = computed(() => {
    return props.history?.data?.filter(item => item.status === 'active').length || 0;
});

const pendingCount = computed(() => {
    return props.history?.data?.filter(item => item.status === 'pending').length || 0;
});

const totalPurchases = computed(() => {
    return props.history?.total || props.history?.data?.length || 0;
});
</script>

<template>

    <Head title="Lịch Sử Mua Gói Dịch Vụ" />

    <LandlordLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <Link :href="route('landlord.subscriptions.index')" class="text-slate-400 hover:text-indigo-600 transition-colors p-1 -ml-1">
                            <i class="bi bi-arrow-left text-xl"></i>
                        </Link>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Lịch Sử Mua Gói Dịch Vụ
                        </h1>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 sm:ml-8">
                        Theo dõi danh sách hóa đơn, trạng thái xét duyệt và thời hạn các gói dịch vụ bạn đã mua.
                    </p>
                </div>

                <Link :href="route('landlord.subscriptions.index')"
                    class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-indigo-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <i class="bi bi-cart-plus-fill"></i> Đăng Ký Mua Gói Mới
                </Link>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex items-center gap-4 shadow-2xs">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Tổng Số Đơn Mua</p>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">{{ totalPurchases }} <span class="text-xs font-normal text-slate-400">đơn</span></h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex items-center gap-4 shadow-2xs">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Đã Kích Hoạt</p>
                        <h3 class="text-xl sm:text-2xl font-black text-emerald-700 mt-0.5">{{ activeCount }} <span class="text-xs font-normal text-slate-400">gói</span></h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex items-center gap-4 shadow-2xs">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Đang Chờ Xét Duyệt</p>
                        <h3 class="text-xl sm:text-2xl font-black text-amber-700 mt-0.5">{{ pendingCount }} <span class="text-xs font-normal text-slate-400">đơn</span></h3>
                    </div>
                </div>
            </div>

            <!-- Bảng Lịch Sử -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto min-w-[750px]">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-900 text-slate-200 font-bold uppercase tracking-wider text-[11px]">
                                <th class="py-4 px-5">Gói Dịch Vụ</th>
                                <th class="py-4 px-5">Mã Giao Dịch</th>
                                <th class="py-4 px-5">Giá Tiền</th>
                                <th class="py-4 px-5">Thời Gian Hiệu Lực</th>
                                <th class="py-4 px-5">Ảnh Bill</th>
                                <th class="py-4 px-5 text-right">Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in history.data" :key="item.id"
                                class="hover:bg-slate-50/70 transition-colors">
                                <!-- Gói dịch vụ -->
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm shrink-0">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                        <span class="font-extrabold text-slate-800 text-sm">
                                            {{ item.plan?.name || "Gói dịch vụ" }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Mã giao dịch -->
                                <td class="py-4 px-5">
                                    <span class="font-mono font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-1 rounded-lg text-xs">
                                        {{ item.payment_code || "---" }}
                                    </span>
                                </td>

                                <!-- Giá tiền -->
                                <td class="py-4 px-5 font-black text-slate-900 text-sm">
                                    {{ item.price_at_purchase == 0 ? "Miễn phí" : formatMoney(item.price_at_purchase) }}
                                </td>

                                <!-- Thời gian -->
                                <td class="py-4 px-5 text-slate-600">
                                    <div v-if="item.status === 'active'" class="flex items-center gap-1.5 font-semibold text-xs">
                                        <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded border border-emerald-100">{{ formatDate(item.start_date) }}</span>
                                        <span class="text-slate-400">➔</span>
                                        <span class="bg-rose-50 text-rose-700 px-2 py-0.5 rounded border border-rose-100">{{ formatDate(item.end_date) }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs italic">Chưa kích hoạt</span>
                                </td>

                                <!-- Ảnh bill -->
                                <td class="py-4 px-5">
                                    <button v-if="item.proof_image" @click="selectedImage = item.proof_image"
                                        class="px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200/70 rounded-xl font-bold text-xs flex items-center gap-1.5 transition-all shadow-2xs cursor-pointer">
                                        <i class="bi bi-image text-indigo-500"></i> Xem Bill
                                    </button>
                                    <span v-else class="text-slate-400 italic text-xs">Chưa có ảnh</span>
                                </td>

                                <!-- Trạng thái -->
                                <td class="py-4 px-5 text-right">
                                    <div class="flex flex-col items-end gap-1">
                                        <span v-if="item.status === 'active'"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-xs shadow-2xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Đã Kích Hoạt
                                        </span>
                                        <span v-else-if="item.status === 'pending'"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full font-bold text-xs shadow-2xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Chờ Admin Duyệt
                                        </span>
                                        <div v-else-if="item.status === 'rejected'" class="text-right">
                                            <span
                                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full font-bold text-xs shadow-2xs">
                                                <i class="bi bi-x-circle-fill text-rose-500"></i> Bị Từ Chối
                                            </span>
                                            <p v-if="item.admin_note" class="text-[11px] text-rose-500 font-medium italic mt-1 max-w-[200px]">
                                                Lý do: {{ item.admin_note }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Trạng thái rỗng -->
                            <tr v-if="!history.data || !history.data.length">
                                <td colspan="6" class="text-center p-12 text-slate-400">
                                    <i class="bi bi-inbox text-4xl block mb-2 text-slate-300"></i>
                                    <p class="font-medium text-slate-500">Bạn chưa có lịch sử mua gói dịch vụ nào.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="block md:hidden divide-y divide-slate-100">
                    <div v-for="item in history.data" :key="item.id" 
                        class="p-4 space-y-3 hover:bg-slate-50/50 transition-colors relative"
                        :class="{
                            'border-l-4 border-l-emerald-500': item.status === 'active',
                            'border-l-4 border-l-amber-500': item.status === 'pending',
                            'border-l-4 border-l-rose-500': item.status === 'rejected'
                        }">
                        <div class="flex items-start justify-between gap-2">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-box-seam text-indigo-600 font-bold"></i>
                                    <h3 class="text-sm font-extrabold text-slate-800">{{ item.plan?.name || "Gói dịch vụ" }}</h3>
                                </div>
                                <p class="text-xs font-mono font-bold text-rose-600">
                                    Mã: {{ item.payment_code || "---" }}
                                </p>
                            </div>
                            <span v-if="item.status === 'active'"
                                class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-[10px] flex items-center gap-1 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Đã Kích Hoạt
                            </span>
                            <span v-else-if="item.status === 'pending'"
                                class="px-2.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full font-bold text-[10px] flex items-center gap-1 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Chờ Duyệt
                            </span>
                            <div v-else-if="item.status === 'rejected'" class="shrink-0 text-right">
                                <span class="px-2.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-full font-bold text-[10px] flex items-center gap-1">
                                    <i class="bi bi-x-circle-fill"></i> Từ Chối
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                            <span class="text-slate-500 font-medium">Giá tiền:</span>
                            <span class="font-black text-slate-900 text-sm">{{ item.price_at_purchase == 0 ? "Miễn phí" : formatMoney(item.price_at_purchase) }}</span>
                        </div>

                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Thời gian hiệu lực:</span>
                            <div v-if="item.status === 'active'" class="text-right text-[11px] font-semibold">
                                <span class="text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">{{ formatDate(item.start_date) }}</span>
                                <span class="mx-1 text-slate-400">➔</span>
                                <span class="text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded">{{ formatDate(item.end_date) }}</span>
                            </div>
                            <span v-else class="text-slate-400 text-xs italic">Chưa kích hoạt</span>
                        </div>

                        <div v-if="item.proof_image || (item.status === 'rejected' && item.admin_note)" class="flex items-center justify-between pt-2 border-t border-slate-100">
                            <div>
                                <p v-if="item.status === 'rejected' && item.admin_note" class="text-[11px] text-rose-500 font-medium italic">
                                    Lý do: {{ item.admin_note }}
                                </p>
                            </div>
                            <button v-if="item.proof_image" @click="selectedImage = item.proof_image"
                                class="px-3 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg font-bold text-xs flex items-center gap-1.5 transition-all ml-auto border border-indigo-200/60">
                                <i class="bi bi-image text-indigo-500"></i> Xem Bill
                            </button>
                        </div>
                    </div>

                    <div v-if="!history.data || !history.data.length" class="text-center p-8 text-slate-400 text-xs">
                        <i class="bi bi-inbox text-3xl block mb-2 text-slate-300"></i>
                        <p class="font-medium text-slate-500">Bạn chưa có lịch sử mua gói dịch vụ nào.</p>
                    </div>
                </div>

                <!-- Component Phân Trang (Pagination) -->
                <div v-if="history.links && history.links.length > 3"
                    class="px-4 sm:px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                    
                    <!-- Số lượng bản ghi -->
                    <div class="text-xs text-slate-500 text-center sm:text-left">
                        Hiển thị từ <span class="font-bold text-slate-800">{{ history.from || 0 }}</span> 
                        đến <span class="font-bold text-slate-800">{{ history.to || 0 }}</span> 
                        trong tổng số <span class="font-bold text-slate-800">{{ history.total || 0 }}</span> đơn mua gói
                    </div>

                    <!-- Nút chuyển trang -->
                    <div class="flex items-center justify-center gap-1 flex-wrap">
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
            <div class="bg-white rounded-2xl max-w-lg w-full p-4 sm:p-5 shadow-2xl relative my-auto border border-slate-100">
                <button @click="selectedImage = null"
                    class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 transition-colors">
                    <i class="bi bi-x-lg text-base"></i>
                </button>
                <h3 class="text-sm sm:text-base font-bold text-slate-800 mb-3 flex items-center gap-2">
                    <i class="bi bi-image text-indigo-600"></i> Hóa Đơn Chuyển Khoản
                </h3>
                <img :src="selectedImage" alt="Bill Chuyển Khoản"
                    class="w-full h-auto rounded-xl border border-slate-200 object-contain max-h-[75vh] mx-auto shadow-2xs" />
            </div>
        </div>
    </LandlordLayout>
</template>
