<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import { showConfirm } from "@/Utils/swal";
import { getStatusLabel, getStatusClass } from "@/Utils/statusHelper";

const props = defineProps({
    post: Object,
});

const rejectForm = useForm({
    reject_reason: props.post.reject_reason || "",
});

const selectedImage = ref(null);

async function approvePost() {
    const confirmed = await showConfirm(
        "Phê duyệt bài đăng",
        "Bạn có chắc chắn muốn phê duyệt xuất bản bài đăng này không?",
        "Phê duyệt",
        "Hủy"
    );
    if (confirmed) {
        router.post(route("admin.listings.approve", props.post.id));
    }
}

function rejectPost() {
    rejectForm.post(route("admin.listings.reject", props.post.id));
}

const formatDate = (dateStr) =>
    dateStr ? new Date(dateStr).toLocaleDateString("vi-VN") : "—";
const formatMoney = (n) =>
    n ? new Intl.NumberFormat("vi-VN").format(n) + "đ" : "—";
</script>

<template>
    <Head :title="'Kiểm duyệt bài viết #' + post.id" />
    <AdminLayout>
        <template #header-title>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.listings.index')"
                    class="flex items-center gap-1.5 px-3 py-2 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 text-slate-600 text-xs font-bold transition-all shadow-sm hover:shadow-md">
                    <i class="bi bi-arrow-left-short text-base"></i> Quay lại
                </Link>
                <div>
                    <h1 class="page-title">Chi Tiết Kiểm Duyệt Tin Đăng</h1>
                    <p class="page-sub text-slate-400">
                        Mã bài đăng: <span class="font-bold text-slate-700">#{{ post.id }}</span>
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 pb-10 space-y-6">

            <!-- Status Banner -->
            <div v-if="post.status !== 'pending'" :class="[
                'flex items-center gap-3 px-5 py-3.5 rounded-2xl font-bold text-sm border',
                post.status === 'approved'
                    ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                    : 'bg-rose-50 border-rose-200 text-rose-800'
            ]">
                <i :class="post.status === 'approved' ? 'bi bi-check-circle-fill text-emerald-500 text-lg' : 'bi bi-x-circle-fill text-rose-500 text-lg'"></i>
                <div>
                    <div>{{ post.status === 'approved' ? '🎉 Bài đăng đã được phê duyệt và xuất bản thành công!' : '❌ Bài đăng đã bị từ chối duyệt.' }}</div>
                    <div v-if="post.reject_reason" class="text-xs font-normal mt-0.5 opacity-80">Lý do: {{ post.reject_reason }}</div>
                    <div class="text-xs font-normal opacity-60 mt-0.5">Xử lý vào: {{ formatDate(post.updated_at) }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Main Content -->
                <div class="lg:col-span-2 space-y-5">

                    <!-- Image Gallery -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-7 h-7 bg-violet-100 rounded-lg flex items-center justify-center">
                                <i class="bi bi-images text-violet-600 text-sm"></i>
                            </span>
                            <span class="text-sm font-bold text-slate-700">Album ảnh thực tế căn phòng</span>
                            <span v-if="post.image?.length" class="ml-auto text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ post.image.length }} ảnh</span>
                        </div>
                        <div class="p-4">
                            <div v-if="post.image && post.image.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                <div v-for="(img, idx) in post.image" :key="idx"
                                    class="relative group cursor-pointer"
                                    @click="selectedImage = img">
                                    <img :src="img"
                                        class="w-full aspect-[4/3] object-cover rounded-xl border border-slate-100 group-hover:opacity-80 transition-all group-hover:scale-[0.98] shadow-sm"
                                        alt="Ảnh phòng trọ" />
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 rounded-xl transition-all flex items-center justify-center">
                                        <i class="bi bi-zoom-in text-white opacity-0 group-hover:opacity-100 text-xl transition-opacity"></i>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-14 text-center text-slate-400 text-xs italic bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                <i class="bi bi-image text-4xl block mb-2 text-slate-300"></i>
                                Chủ trọ chưa cập nhật hình ảnh cho bài viết này.
                            </div>
                        </div>
                    </div>

                    <!-- Post Details -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="bi bi-file-earmark-text text-blue-600 text-sm"></i>
                            </span>
                            <span class="text-sm font-bold text-slate-700">Thông tin chi tiết bài đăng</span>
                        </div>
                        <div class="p-5 space-y-5">
                            <!-- Title & Type -->
                            <div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-violet-50 text-violet-700 text-[10px] font-bold uppercase rounded-lg border border-violet-100 mb-2">
                                    <i class="bi bi-house-fill"></i>
                                    {{ post.room?.room_type || "Phòng trọ" }}
                                </span>
                                <h2 class="text-xl font-black text-slate-900 leading-snug">{{ post.title }}</h2>
                            </div>

                            <!-- Key Stats Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div class="bg-rose-50 border border-rose-100 rounded-xl p-3 text-center">
                                    <div class="text-[10px] text-rose-500 font-bold uppercase tracking-wide mb-1">Giá thuê</div>
                                    <div class="text-base font-black text-rose-700">{{ formatMoney(post.room?.price) }}</div>
                                    <div class="text-[10px] text-rose-400">/tháng</div>
                                </div>
                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-center">
                                    <div class="text-[10px] text-blue-500 font-bold uppercase tracking-wide mb-1">Diện tích</div>
                                    <div class="text-base font-black text-blue-700">{{ post.room?.area || "—" }} m²</div>
                                </div>
                                <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3 text-center">
                                    <div class="text-[10px] text-emerald-500 font-bold uppercase tracking-wide mb-1">Sức chứa</div>
                                    <div class="text-base font-black text-emerald-700">{{ post.room?.capacity || "2" }} người</div>
                                </div>
                            </div>

                            <!-- Room Info Grid -->
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div class="flex items-start gap-2.5 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <i class="bi bi-building text-slate-400 mt-0.5"></i>
                                    <div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase">Khu trọ</div>
                                        <div class="font-bold text-slate-800 mt-0.5">{{ post.room?.boarding_house?.name || "—" }}</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2.5 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <i class="bi bi-door-closed text-slate-400 mt-0.5"></i>
                                    <div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase">Số phòng</div>
                                        <div class="font-bold text-slate-800 mt-0.5">Phòng {{ post.room?.room_number }}</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2.5 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <i class="bi bi-layers text-slate-400 mt-0.5"></i>
                                    <div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase">Tầng</div>
                                        <div class="font-bold text-slate-800 mt-0.5">{{ post.room?.floor?.name || "—" }}</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2.5 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <i class="bi bi-calendar3 text-slate-400 mt-0.5"></i>
                                    <div>
                                        <div class="text-[10px] text-slate-400 font-bold uppercase">Gửi bài</div>
                                        <div class="font-bold text-slate-800 mt-0.5">{{ formatDate(post.created_at) }}</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2.5 p-3 bg-slate-50 rounded-xl border border-slate-100 col-span-2">
                                    <i class="bi bi-lightning text-slate-400 mt-0.5"></i>
                                    <div class="flex items-center gap-2 w-full">
                                        <div class="text-[10px] text-slate-400 font-bold uppercase mr-auto">Trạng thái phòng</div>
                                        <span :class="['px-2 py-0.5 rounded-md border text-[10px] font-bold', getStatusClass(post.room?.status)]">
                                            {{ getStatusLabel(post.room?.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Services -->
                            <div v-if="post.room?.services && post.room.services.length > 0">
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <i class="bi bi-tools"></i> Dịch vụ & Tiện ích đi kèm
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div v-for="service in post.room.services" :key="service.id"
                                        class="flex items-center gap-2 text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                        <i :class="['bi', service.icon || 'bi-check-circle-fill', 'text-base']"
                                            :style="'color:' + (service.color || '#3b82f6')"></i>
                                        <span>{{ service.name }}: <strong class="text-rose-500">{{ formatMoney(service.price) }}</strong></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <i class="bi bi-file-text"></i> Mô tả chi tiết từ chủ trọ
                                </div>
                                <div v-html="post.description"
                                    class="p-4 bg-slate-50 border border-slate-100 rounded-xl text-xs text-slate-700 leading-relaxed max-h-80 overflow-y-auto detail-content-view">
                                </div>
                            </div>

                            <!-- Map -->
                            <div>
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                    <i class="bi bi-geo-alt-fill text-rose-500"></i> Vị trí & Bản đồ
                                </div>
                                <div class="text-xs text-slate-600 bg-blue-50 border border-blue-100 px-3 py-2 rounded-xl mb-2 font-medium">
                                    📍 {{ post.address || post.room?.boarding_house?.address_detail || "Chủ trọ chưa thiết lập địa chỉ." }}
                                </div>
                                <div class="rounded-2xl overflow-hidden border border-slate-200 shadow-sm" style="height: 260px">
                                    <iframe v-if="post.latitude && post.longitude"
                                        :src="'https://maps.google.com/maps?q=' + post.latitude + ',' + post.longitude + '&hl=vi&z=16&output=embed'"
                                        width="100%" height="100%" style="border: 0" loading="lazy"></iframe>
                                    <iframe v-else-if="post.address"
                                        :src="'https://maps.google.com/maps?q=' + encodeURIComponent(post.address) + '&hl=vi&z=16&output=embed'"
                                        width="100%" height="100%" style="border: 0" loading="lazy"></iframe>
                                    <iframe v-else-if="post.room?.boarding_house?.latitude && post.room?.boarding_house?.longitude"
                                        :src="'https://maps.google.com/maps?q=' + post.room.boarding_house.latitude + ',' + post.room.boarding_house.longitude + '&hl=vi&z=16&output=embed'"
                                        width="100%" height="100%" style="border: 0" loading="lazy"></iframe>
                                    <iframe v-else-if="post.room?.boarding_house?.address_detail"
                                        :src="'https://maps.google.com/maps?q=' + encodeURIComponent(post.room.boarding_house.address_detail) + '&hl=vi&z=16&output=embed'"
                                        width="100%" height="100%" style="border: 0" loading="lazy"></iframe>
                                    <div v-else class="p-6 text-center text-slate-400 text-xs bg-slate-50 flex flex-col items-center justify-center h-full gap-2">
                                        <i class="bi bi-map text-3xl text-slate-300"></i>
                                        Chưa có tọa độ hoặc địa chỉ để hiển thị bản đồ.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Sidebar -->
                <div class="space-y-5">

                    <!-- Landlord Info -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center">
                                <i class="bi bi-person-badge text-amber-600 text-sm"></i>
                            </span>
                            <span class="text-sm font-bold text-slate-700">Thông tin người đăng tin</span>
                        </div>
                        <div class="p-5 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white font-black text-base flex-shrink-0 shadow">
                                    {{ post.landlord?.name?.[0] || 'A' }}
                                </div>
                                <div>
                                    <div class="font-black text-slate-900 text-sm">{{ post.landlord?.name }}</div>
                                    <div class="text-[10px] text-slate-400 font-medium">Chủ trọ hệ thống</div>
                                </div>
                            </div>
                            <div class="space-y-2 text-xs">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="bi bi-envelope text-slate-400 w-4 text-center"></i>
                                    <span class="font-medium">{{ post.landlord?.email || "Chưa cập nhật" }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-600">
                                    <i class="bi bi-telephone text-slate-400 w-4 text-center"></i>
                                    <span class="font-bold text-slate-800">{{ post.landlord?.phone || "Chưa cập nhật" }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Panel (Pending) -->
                    <div v-if="post.status === 'pending'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                            <span class="w-7 h-7 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="bi bi-shield-check text-orange-600 text-sm"></i>
                            </span>
                            <span class="text-sm font-bold text-slate-700">Tác vụ kiểm duyệt</span>
                        </div>
                        <div class="p-5 space-y-4">
                            <!-- Pending status badge -->
                            <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-xl text-xs font-bold text-amber-800">
                                <i class="bi bi-clock-history text-amber-500"></i>
                                Bài đăng đang chờ kiểm duyệt
                            </div>

                            <!-- Approve Button -->
                            <button @click="approvePost"
                                class="w-full py-3 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-200 transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="bi bi-check-circle-fill text-base"></i>
                                PHÊ DUYỆT & XUẤT BẢN
                            </button>

                            <!-- Divider -->
                            <div class="flex items-center gap-3">
                                <div class="flex-1 h-px bg-slate-100"></div>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">hoặc từ chối</span>
                                <div class="flex-1 h-px bg-slate-100"></div>
                            </div>

                            <!-- Reject -->
                            <div>
                                <label class="block text-xs font-bold text-rose-600 mb-1.5 flex items-center gap-1">
                                    <i class="bi bi-patch-exclamation-fill"></i>
                                    Lý do từ chối <span class="text-rose-500">(*)</span>
                                </label>
                                <textarea v-model="rejectForm.reject_reason" rows="4"
                                    placeholder="Ví dụ: Ảnh phòng bị mờ, thông tin giá chưa khớp thực tế..."
                                    class="w-full text-xs rounded-xl border-slate-300 focus:ring-rose-500 focus:border-rose-500 placeholder:text-slate-300 shadow-sm resize-none"></textarea>
                                <p v-if="rejectForm.errors.reject_reason" class="text-rose-500 text-[11px] mt-1 font-semibold flex items-center gap-1">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    {{ rejectForm.errors.reject_reason }}
                                </p>
                                <button @click="rejectPost" :disabled="rejectForm.processing"
                                    class="w-full mt-2.5 py-2.5 bg-rose-50 border border-rose-200 text-rose-600 hover:bg-rose-100 font-bold text-xs rounded-xl transition-colors disabled:opacity-50 flex items-center justify-center gap-1.5">
                                    <i class="bi bi-x-circle-fill"></i>
                                    TỪ CHỐI DUYỆT TIN ĐĂNG
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Already Processed -->
                    <div v-else :class="[
                        'rounded-2xl border shadow-sm overflow-hidden',
                        post.status === 'approved' ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50'
                    ]">
                        <div :class="[
                            'px-5 py-4 border-b text-sm font-black flex items-center gap-2',
                            post.status === 'approved' ? 'border-emerald-200 text-emerald-800' : 'border-rose-200 text-rose-800'
                        ]">
                            <i :class="post.status === 'approved' ? 'bi bi-check-circle-fill text-emerald-500' : 'bi bi-x-circle-fill text-rose-500'"></i>
                            {{ post.status === 'approved' ? 'Đã phê duyệt thành công' : 'Đã từ chối xuất bản' }}
                        </div>
                        <div class="p-4 space-y-2 text-xs text-slate-500">
                            <div>Xử lý vào: <span class="font-bold text-slate-700">{{ formatDate(post.updated_at) }}</span></div>
                            <div v-if="post.status === 'rejected' && post.reject_reason" class="bg-white border border-rose-100 rounded-xl p-3">
                                <div class="text-[10px] font-bold text-rose-600 uppercase tracking-wider mb-1">Lý do từ chối:</div>
                                <p class="text-rose-700 italic">"{{ post.reject_reason }}"</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Lightbox -->
        <div v-if="selectedImage" @click="selectedImage = null"
            class="fixed inset-0 bg-black/80 z-[9999] flex items-center justify-center p-4 cursor-pointer backdrop-blur-sm">
            <div class="relative max-w-4xl w-full" @click.stop>
                <img :src="selectedImage" class="w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl" alt="Ảnh phóng to" />
                <button @click="selectedImage = null"
                    class="absolute top-3 right-3 w-9 h-9 bg-white/20 hover:bg-white/40 rounded-xl flex items-center justify-center text-white font-black text-lg transition-all">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </AdminLayout>
</template>

<style>
.detail-content-view ul {
    list-style-type: disc;
    padding-left: 1.3rem;
    margin-bottom: 0.4rem;
}
.detail-content-view ol {
    list-style-type: decimal;
    padding-left: 1.3rem;
    margin-bottom: 0.4rem;
}
</style>
