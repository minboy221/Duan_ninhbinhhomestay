<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { showSuccess } from '@/Utils/swal'

const props = defineProps({
    house: Object,
    stats: Object,
})

const billingDay = ref(props.house.invoice_billing_day || 30)
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

const roomImages = getImages(props.house.room_images)

const getStatusClass = (status) => {
    switch (status) {
        case 'approved': return 'bg-emerald-100 text-emerald-700'
        case 'rejected': return 'bg-red-100 text-red-700'
        default: return 'bg-amber-100 text-amber-700'
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
    <Head :title="'Chi Tiết Cơ Sở - ' + house.name" />

    <LandlordLayout>
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('landlord.boarding-houses.index')" class="text-slate-400 hover:text-emerald-500 transition-colors">
                            <i class="bi bi-arrow-left text-xl"></i>
                        </Link>
                        <h1 class="text-2xl font-bold text-slate-800">Chi Tiết Cơ Sở</h1>
                    </div>
                    <p class="text-slate-500 mt-1 ml-8">Xem chi tiết và thống kê về cơ sở của bạn</p>
                </div>
                
                <span class="px-3 py-1 rounded-full text-sm font-semibold" :class="getStatusClass(house.status)">
                    {{ getStatusText(house.status) }}
                </span>
            </div>

            <!-- Stats grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Thống kê tin đăng -->
                <div class="bg-white rounded-xl border border-slate-100 p-6 flex items-center gap-4 shadow-sm">
                    <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center text-2xl">
                        <i class="bi bi-file-earmark-post"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Tổng Tin Đăng</p>
                        <p class="text-2xl font-bold text-slate-800">{{ stats.post_count }} <span class="text-sm font-normal text-slate-400">tin</span></p>
                    </div>
                </div>

                <!-- Thống kê phòng -->
                <div class="bg-white rounded-xl border border-slate-100 p-6 flex items-center gap-4 shadow-sm">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center text-2xl">
                        <i class="bi bi-door-open"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Tổng Số Phòng</p>
                        <p class="text-2xl font-bold text-slate-800">{{ stats.room_count }} <span class="text-sm font-normal text-slate-400">phòng</span></p>
                    </div>
                </div>

                <!-- Thống kê tầng -->
                <div class="bg-white rounded-xl border border-slate-100 p-6 flex items-center gap-4 shadow-sm">
                    <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center text-2xl">
                        <i class="bi bi-layers"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Tổng Số Tầng</p>
                        <p class="text-2xl font-bold text-slate-800">{{ stats.floor_count }} <span class="text-sm font-normal text-slate-400">tầng</span></p>
                    </div>
                </div>

                <!-- Thống kê đánh giá -->
                <div class="bg-white rounded-xl border border-slate-100 p-6 flex items-center gap-4 shadow-sm">
                    <div class="w-12 h-12 bg-yellow-50 text-yellow-500 rounded-full flex items-center justify-center text-2xl">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-medium">Đánh Giá ({{ stats.review_count || 0 }})</p>
                        <p class="text-2xl font-bold text-slate-800">{{ house.average_rating > 0 ? house.average_rating : 'N/A' }} <span class="text-sm font-normal text-slate-400">sao</span></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Cột trái: Thông tin & Bản đồ -->
                <div class="space-y-8">
                    <!-- Basic Info -->
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
                            <h3 class="font-bold text-slate-700 flex items-center gap-2">
                                <i class="bi bi-info-circle text-emerald-500"></i> Thông Tin Cơ Bản
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Tên cơ sở</label>
                                <p class="text-slate-800 font-medium">{{ house.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Địa chỉ chi tiết</label>
                                <p class="text-slate-800">{{ house.address_detail }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Ngày tạo</label>
                                <p class="text-slate-800">{{ new Date(house.created_at).toLocaleDateString('vi-VN') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Billing Config -->
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
                            <h3 class="font-bold text-slate-700 flex items-center gap-2">
                                <i class="bi bi-calendar-check text-emerald-500"></i> Cấu hình Hóa Đơn Định Kỳ
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Ngày chốt hóa đơn hàng tháng</label>
                                <div class="flex items-center gap-3">
                                    <select v-model="billingDay" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none w-32">
                                        <option v-for="d in 31" :key="d" :value="d">Ngày {{ d }}</option>
                                    </select>
                                    <button @click="saveBillingDay" :disabled="saving" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm px-4 py-2 rounded-lg transition-colors flex items-center gap-1.5 disabled:opacity-50">
                                        <i v-if="saving" class="bi bi-arrow-clockwise animate-spin"></i>
                                        <span>Lưu cấu hình</span>
                                    </button>
                                </div>
                                <p class="text-xs text-slate-400 mt-2">
                                    <i class="bi bi-info-circle-fill"></i> Đến ngày này hàng tháng, hệ thống sẽ nhắc nhở chốt số điện nước và tạo hóa đơn.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Map -->
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
                            <h3 class="font-bold text-slate-700 flex items-center gap-2">
                                <i class="bi bi-geo-alt text-emerald-500"></i> Bản Đồ Vị Trí
                            </h3>
                        </div>
                        <div class="p-0">
                            <div v-if="house.latitude && house.longitude" class="w-full h-80 relative">
                                <iframe
                                    width="100%"
                                    height="100%"
                                    frameborder="0"
                                    style="border:0"
                                    :src="`https://maps.google.com/maps?q=${house.latitude},${house.longitude}&hl=vi&z=16&output=embed`"
                                    allowfullscreen
                                ></iframe>
                            </div>
                            <div v-else-if="house.address_detail" class="w-full h-80 relative">
                                <iframe
                                    width="100%"
                                    height="100%"
                                    frameborder="0"
                                    style="border:0"
                                    :src="`https://maps.google.com/maps?q=${encodeURIComponent(house.address_detail)}&hl=vi&z=16&output=embed`"
                                    allowfullscreen
                                ></iframe>
                            </div>
                            <div v-else class="h-80 flex flex-col items-center justify-center text-slate-400 bg-slate-50">
                                <i class="bi bi-map text-4xl mb-2 text-slate-300"></i>
                                <p>Cơ sở này chưa được cấu hình tọa độ trên bản đồ</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Hình ảnh -->
                <div class="space-y-8">
                    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                            <h3 class="font-bold text-slate-700 flex items-center gap-2">
                                <i class="bi bi-images text-emerald-500"></i> Hình Ảnh Cơ Sở
                            </h3>
                            <span class="bg-emerald-100 text-emerald-700 text-xs px-2 py-0.5 rounded-full font-bold">
                                {{ roomImages.length }} ảnh
                            </span>
                        </div>
                        <div class="p-6">
                            <div v-if="roomImages.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div v-for="(img, idx) in roomImages" :key="idx" class="aspect-square rounded-lg overflow-hidden border border-slate-200 shadow-sm group relative">
                                    <img :src="'/storage/' + img" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" />
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300 flex items-center justify-center">
                                        <a :href="'/storage/' + img" target="_blank" class="w-8 h-8 rounded-full bg-white/90 text-slate-800 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity transform translate-y-2 group-hover:translate-y-0">
                                            <i class="bi bi-arrows-fullscreen"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-slate-400">
                                <i class="bi bi-image text-3xl block mb-2 text-slate-300"></i>
                                Không có hình ảnh nào được tải lên
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
