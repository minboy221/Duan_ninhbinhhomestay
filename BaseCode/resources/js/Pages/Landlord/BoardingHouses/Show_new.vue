<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { showSuccess } from '@/Utils/swal'

const props = defineProps({
    house: Object,
    stats: Object,
})

const billingDay = ref(props.house?.invoice_billing_day || 30)
const saving = ref(false)

const saveBillingDay = () => {
    saving.value = true
    router.patch(route('landlord.boarding-houses.billing-day', props.house.id), {
        invoice_billing_day: billingDay.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            saving.value = false
            showSuccess('Cập nhật ngày chốt hóa đơn thành công!')
        },
        onError: () => {
            saving.value = false
        }
    })
}

const getImages = (jsonStr) => {
    try {
        return JSON.parse(jsonStr) || []
    } catch(e) {
        return []
    }
}

const roomImages = getImages(props.house?.room_images)

const getStatusClass = (status) => {
    switch (status) {
        case 'approved': return 'bg-emerald-100 text-emerald-700 border border-emerald-200'
        case 'rejected': return 'bg-rose-100 text-rose-700 border border-rose-200'
        default: return 'bg-amber-100 text-amber-700 border border-amber-200'
    }
}

const getStatusText = (status) => {
    switch (status) {
        case 'approved': return 'Đã Duyệt'
        case 'rejected': return 'Từ Chối'
        default: return 'Chờ Duyệt'
    }
}
</script>

<template>
    <Head :title="house?.name ? `Chi Tiết Cơ Sở - ${house.name}` : 'Chi Tiết Cơ Sở'" />

    <LandlordLayout>
        <div class="p-4 sm:p-6 space-y-6">
            <!-- Header Bar -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <Link :href="route('landlord.boarding-houses.index')" class="text-slate-400 hover:text-emerald-600 transition-colors p-1 -ml-1">
                            <i class="bi bi-arrow-left text-xl"></i>
                        </Link>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight">Chi Tiết Cơ Sở</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold sm:hidden" :class="getStatusClass(house?.status)">
                            {{ getStatusText(house?.status) }}
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 sm:ml-8 font-medium">Xem chi tiết và thống kê về cơ sở của bạn</p>
                </div>
                
                <span class="hidden sm:inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider shadow-2xs" :class="getStatusClass(house?.status)">
                    {{ getStatusText(house?.status) }}
                </span>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
                <!-- Thống kê tin đăng -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex flex-col sm:flex-row items-center sm:items-start gap-3 sm:gap-4 shadow-2xs text-center sm:text-left">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl sm:text-2xl shrink-0">
                        <i class="bi bi-file-earmark-post"></i>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-xs text-slate-500 font-medium">Tổng Tin Đăng</p>
                        <p class="text-lg sm:text-2xl font-black text-slate-800">{{ stats?.post_count || 0 }} <span class="text-xs font-normal text-slate-400">tin</span></p>
                    </div>
                </div>

                <!-- Thống kê phòng -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex flex-col sm:flex-row items-center sm:items-start gap-3 sm:gap-4 shadow-2xs text-center sm:text-left">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl sm:text-2xl shrink-0">
                        <i class="bi bi-door-open"></i>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-xs text-slate-500 font-medium">Tổng Số Phòng</p>
                        <p class="text-lg sm:text-2xl font-black text-slate-800">{{ stats?.room_count || 0 }} <span class="text-xs font-normal text-slate-400">phòng</span></p>
                    </div>
                </div>

                <!-- Thống kê tầng -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex flex-col sm:flex-row items-center sm:items-start gap-3 sm:gap-4 shadow-2xs text-center sm:text-left">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-xl sm:text-2xl shrink-0">
                        <i class="bi bi-layers"></i>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-xs text-slate-500 font-medium">Tổng Số Tầng</p>
                        <p class="text-lg sm:text-2xl font-black text-slate-800">{{ stats?.floor_count || 0 }} <span class="text-xs font-normal text-slate-400">tầng</span></p>
                    </div>
                </div>

                <!-- Thống kê đánh giá -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex flex-col sm:flex-row items-center sm:items-start gap-3 sm:gap-4 shadow-2xs text-center sm:text-left">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-50 text-yellow-600 rounded-xl flex items-center justify-center text-xl sm:text-2xl shrink-0">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-xs text-slate-500 font-medium">Đánh Giá ({{ stats?.review_count || 0 }})</p>
                        <p class="text-lg sm:text-2xl font-black text-slate-800">{{ house?.average_rating > 0 ? house.average_rating : 'N/A' }} <span class="text-xs font-normal text-slate-400">sao</span></p>
                    </div>
                </div>
            </div>

            <!-- Main Content 2-Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Cột trái: Thông tin & Bản đồ -->
                <div class="space-y-6">
                    <!-- Basic Info -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                        <div class="bg-slate-50 p-4 sm:px-6 sm:py-4 border-b border-slate-100">
                            <h3 class="font-bold text-slate-800 text-sm sm:text-base flex items-center gap-2">
                                <i class="bi bi-info-circle text-emerald-600"></i> Thông Tin Cơ Bản
                            </h3>
                        </div>
                        <div class="p-4 sm:p-6 space-y-4 text-xs sm:text-sm">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Tên cơ sở</label>
                                <p class="text-slate-800 font-bold">{{ house?.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Địa chỉ chi tiết</label>
                                <p class="text-slate-700 font-medium">{{ house?.address_detail }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Ngày tạo</label>
                                <p class="text-slate-700 font-medium">{{ house?.created_at ? new Date(house.created_at).toLocaleDateString('vi-VN') : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Billing Config -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                        <div class="bg-slate-50 p-4 sm:px-6 sm:py-4 border-b border-slate-100">
                            <h3 class="font-bold text-slate-800 text-sm sm:text-base flex items-center gap-2">
                                <i class="bi bi-calendar-check text-emerald-600"></i> Cấu hình Hóa Đơn Định Kỳ
                            </h3>
                        </div>
                        <div class="p-4 sm:p-6 space-y-4">
                            <div>
                                <label class="block text-xs sm:text-sm font-bold text-slate-700 mb-2">Ngày chốt hóa đơn hàng tháng</label>
                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                    <select v-model="billingDay" class="rounded-xl border border-slate-300 px-3.5 py-2 text-xs sm:text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none w-full sm:w-36 bg-white font-bold text-slate-800">
                                        <option v-for="d in 31" :key="d" :value="d">Ngày {{ d }}</option>
                                    </select>
                                    <button @click="saveBillingDay" :disabled="saving" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm px-4 py-2.5 sm:py-2 rounded-xl transition-all flex items-center justify-center gap-1.5 disabled:opacity-50 shadow-xs cursor-pointer">
                                        <i v-if="saving" class="bi bi-arrow-clockwise animate-spin"></i>
                                        <span>Lưu cấu hình</span>
                                    </button>
                                </div>
                                <p class="text-[11px] sm:text-xs text-slate-400 mt-2.5 flex items-start gap-1">
                                    <i class="bi bi-info-circle-fill text-indigo-500 mt-0.5 shrink-0"></i> 
                                    <span>Đến ngày này hàng tháng, hệ thống sẽ nhắc nhở chốt số điện nước và tạo hóa đơn.</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Map -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                        <div class="bg-slate-50 p-4 sm:px-6 sm:py-4 border-b border-slate-100">
                            <h3 class="font-bold text-slate-800 text-sm sm:text-base flex items-center gap-2">
                                <i class="bi bi-geo-alt text-emerald-600"></i> Bản Đồ Vị Trí
                            </h3>
                        </div>
                        <div class="p-0">
                            <div v-if="house?.latitude && house?.longitude" class="w-full h-64 sm:h-80 relative">
                                <iframe
                                    width="100%"
                                    height="100%"
                                    frameborder="0"
                                    style="border:0"
                                    :src="`https://maps.google.com/maps?q=${house.latitude},${house.longitude}&hl=vi&z=16&output=embed`"
                                    allowfullscreen
                                ></iframe>
                            </div>
                            <div v-else-if="house?.address_detail" class="w-full h-64 sm:h-80 relative">
                                <iframe
                                    width="100%"
                                    height="100%"
                                    frameborder="0"
                                    style="border:0"
                                    :src="`https://maps.google.com/maps?q=${encodeURIComponent(house.address_detail)}&hl=vi&z=16&output=embed`"
                                    allowfullscreen
                                ></iframe>
                            </div>
                            <div v-else class="h-64 sm:h-80 flex flex-col items-center justify-center text-slate-400 bg-slate-50 p-4 text-center">
                                <i class="bi bi-map text-4xl mb-2 text-slate-300"></i>
                                <p class="text-xs font-semibold">Cơ sở này chưa được cấu hình tọa độ trên bản đồ</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Hình ảnh -->
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                        <div class="bg-slate-50 p-4 sm:px-6 sm:py-4 border-b border-slate-100 flex justify-between items-center">
                            <h3 class="font-bold text-slate-800 text-sm sm:text-base flex items-center gap-2">
                                <i class="bi bi-images text-emerald-600"></i> Hình Ảnh Cơ Sở
                            </h3>
                            <span class="bg-emerald-100 text-emerald-800 text-xs px-2.5 py-0.5 rounded-full font-extrabold border border-emerald-200">
                                {{ roomImages.length }} ảnh
                            </span>
                        </div>
                        <div class="p-4 sm:p-6">
                            <div v-if="roomImages.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div v-for="(img, idx) in roomImages" :key="idx" class="aspect-square rounded-xl overflow-hidden border border-slate-200 shadow-2xs group relative">
                                    <img :src="'/storage/' + img" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" />
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/25 transition-colors duration-300 flex items-center justify-center">
                                        <a :href="'/storage/' + img" target="_blank" class="w-8 h-8 rounded-full bg-white/90 text-slate-800 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity transform translate-y-2 group-hover:translate-y-0 shadow-md">
                                            <i class="bi bi-arrows-fullscreen"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-10 text-slate-400 text-xs font-semibold">
                                <i class="bi bi-image text-4xl block mb-2 text-slate-300"></i>
                                Không có hình ảnh nào được tải lên
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
