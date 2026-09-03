<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'

const props = defineProps({
    post: Object,
    reviews: Array,
    averageRating: Number,
    uniqueViews: Number,
})

const activeImageIndex = ref(0)
const nextImage = () => {
    if (props.post.image && props.post.image.length > 0) {
        activeImageIndex.value = (activeImageIndex.value + 1) % props.post.image.length
    }
}
const prevImage = () => {
    if (props.post.image && props.post.image.length > 0) {
        activeImageIndex.value = (activeImageIndex.value - 1 + props.post.image.length) % props.post.image.length
    }
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }).format(date)
}
</script>

<template>
    <Head title="Chi tiết tin đăng" />
    <LandlordLayout>
        <div class="p-4 sm:p-6 max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <Link :href="route('landlord.listings.index')" class="text-xs sm:text-sm text-slate-500 hover:text-emerald-600 flex items-center gap-1 mb-1 transition-colors">
                        <i class="bi bi-arrow-left"></i> Quay lại danh sách
                    </Link>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight">Chi tiết tin đăng</h1>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-4 sm:p-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-eye-fill"></i>
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-slate-500">Lượt xem thực tế (Tài khoản/IP)</p>
                        <h3 class="text-xl sm:text-2xl font-bold text-slate-800">{{ uniqueViews }} <span class="text-xs sm:text-sm font-normal text-slate-400">lượt</span></h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-4 sm:p-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm font-medium text-slate-500">Đánh giá trung bình</p>
                        <h3 class="text-xl sm:text-2xl font-bold text-slate-800">{{ averageRating }} <span class="text-xs sm:text-sm font-normal text-slate-400">/ 5 sao</span></h3>
                    </div>
                </div>
            </div>

            <!-- Post Details -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4">Thông tin bài đăng</h3>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="col-span-1">
                        <div v-if="post.image && post.image.length > 0" class="space-y-3">
                            <!-- Main Image -->
                            <div class="relative rounded-xl overflow-hidden aspect-[4/3] bg-slate-900 group">
                                <img :src="post.image[activeImageIndex]" class="w-full h-full object-cover transition-transform duration-500" alt="Ảnh phòng" />
                                
                                <button v-if="post.image.length > 1" @click="prevImage" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/80 transition-colors opacity-80 sm:opacity-0 group-hover:opacity-100 z-10">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <button v-if="post.image.length > 1" @click="nextImage" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/80 transition-colors opacity-80 sm:opacity-0 group-hover:opacity-100 z-10">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                                
                                <div v-if="post.image.length > 1" class="absolute bottom-2 right-3 px-2 py-0.5 bg-black/60 rounded-md text-[11px] text-white font-medium">
                                    {{ activeImageIndex + 1 }} / {{ post.image.length }}
                                </div>
                            </div>
                            <!-- Thumbnails -->
                            <div v-if="post.image.length > 1" class="flex gap-2 overflow-x-auto pb-1 custom-scrollbar">
                                <img v-for="(img, idx) in post.image" :key="idx" 
                                    :src="img" 
                                    @click="activeImageIndex = idx"
                                    :class="['w-16 h-12 object-cover rounded-lg cursor-pointer transition-all border-2 shrink-0', activeImageIndex === idx ? 'border-emerald-500 opacity-100' : 'border-transparent opacity-60 hover:opacity-100']" 
                                    alt="Thumbnail" />
                            </div>
                        </div>
                        <div v-else class="rounded-xl overflow-hidden aspect-[4/3] bg-slate-100 flex items-center justify-center text-slate-400">
                            <i class="bi bi-image text-3xl"></i>
                        </div>
                    </div>
                    <div class="col-span-1 lg:col-span-2 space-y-4">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tiêu đề</span>
                            <p class="text-sm sm:text-base font-bold text-slate-800 mt-1 leading-snug">{{ post.title }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Trạng thái</span>
                            <div class="mt-1">
                                <span v-if="post.status === 'approved'" class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold inline-block">
                                    Đang hoạt động
                                </span>
                                <span v-else-if="post.status === 'hidden'" class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold inline-block">
                                    Đã đóng
                                </span>
                                <span v-else-if="post.status === 'rejected'" class="px-2.5 py-1 bg-rose-100 text-rose-600 rounded-lg text-xs font-bold inline-block">
                                    Từ chối
                                </span>
                                <span v-else class="px-2.5 py-1 bg-amber-100 text-amber-600 rounded-lg text-xs font-bold inline-block">
                                    Chờ duyệt
                                </span>
                            </div>
                        </div>
                        <div v-if="post.room">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Cơ sở / Phòng</span>
                            <p class="text-xs sm:text-sm font-medium text-slate-800 mt-1">
                                <i class="bi bi-building text-emerald-600"></i> {{ post.room.boarding_house?.name }} - 
                                <i class="bi bi-door-open text-emerald-600"></i> Ph. {{ post.room.room_number }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="bg-white rounded-2xl shadow-2xs border border-slate-200/80 p-4 sm:p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                    <h3 class="text-base sm:text-lg font-bold text-slate-800">Bình luận từ người thuê ({{ reviews.length }})</h3>
                </div>
                
                <div v-if="reviews.length > 0" class="space-y-4">
                    <div v-for="review in reviews" :key="review.id" class="p-3.5 sm:p-4 bg-slate-50/80 rounded-xl border border-slate-100">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold overflow-hidden shrink-0">
                                    <img v-if="review.tenant_avatar" :src="'/storage/' + review.tenant_avatar" class="w-full h-full object-cover" />
                                    <span v-else>{{ review.tenant_name.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="text-xs sm:text-sm font-bold text-slate-800">{{ review.tenant_name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ review.created_at }}</p>
                                </div>
                            </div>
                            <div class="flex gap-1 text-amber-400 text-xs sm:text-sm">
                                <i v-for="i in 5" :key="i" :class="i <= review.rating ? 'bi bi-star-fill' : 'bi bi-star text-slate-200'"></i>
                            </div>
                        </div>
                        <p class="mt-3 text-xs sm:text-sm text-slate-700 leading-relaxed bg-white p-3 rounded-lg border border-slate-100">
                            {{ review.comment || 'Không có nội dung bình luận.' }}
                        </p>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-slate-400 text-xs font-medium">
                    <i class="bi bi-chat-square-text text-slate-300 text-4xl mb-3 block"></i>
                    <p class="text-slate-500 font-medium">Chưa có bình luận nào cho phòng này.</p>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
