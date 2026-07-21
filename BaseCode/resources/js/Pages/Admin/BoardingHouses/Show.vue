<script setup>
import { Head, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref } from 'vue'

const props = defineProps({
    house: Object
})

const getImages = (jsonStr) => {
    try {
        return JSON.parse(jsonStr) || []
    } catch(e) {
        return []
    }
}

const contractImages = getImages(props.house.contract_images)
const roomImages = getImages(props.house.room_images)

const showApproveModal = ref(false)
const rejectReason = ref('')
const showRejectModal = ref(false)

const confirmApprove = () => {
    router.post(route('admin.boarding-houses.approve', props.house.id), {}, { 
        preserveScroll: true,
        onSuccess: () => {
            showApproveModal.value = false
        }
    })
}

const confirmReject = () => {
    if (!rejectReason.value) return alert('Vui lòng nhập lý do từ chối!')
    router.post(route('admin.boarding-houses.reject', props.house.id), {
        reason: rejectReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false
        }
    })
}
</script>

<template>
    <Head title="Chi Tiết Cơ Sở" />
    <AdminLayout>
        <template #header-title>
            <div class="flex items-center gap-3">
                <Link href="/admin/boarding-houses" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="bi bi-arrow-left text-xl"></i>
                </Link>
                <div>
                    <h1 class="header-title">Chi Tiết Cơ Sở Mới</h1>
                    <p class="text-sm text-gray-500 mt-1">Đánh giá và xét duyệt thông tin cơ sở của chủ trọ</p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Cột trái: Thông tin -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Thông tin cơ bản -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4">Thông tin Cơ Sở</h2>
                    <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Tên Cơ Sở</span>
                            <span class="block mt-1 text-gray-900 font-medium">{{ house.name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Trạng Thái</span>
                            <span v-if="house.status === 'pending'" class="inline-block mt-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-amber-100 text-amber-700 uppercase tracking-wider">Chờ Duyệt</span>
                            <span v-else-if="house.status === 'approved'" class="inline-block mt-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-emerald-100 text-emerald-700 uppercase tracking-wider">Đã Duyệt</span>
                            <span v-else class="inline-block mt-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-rose-100 text-rose-700 uppercase tracking-wider">Từ Chối</span>
                        </div>
                        <div class="col-span-2">
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Địa Chỉ</span>
                            <span class="block mt-1 text-gray-900">{{ house.address_detail }}, {{ house.district }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="block text-xs font-semibold text-gray-500 uppercase">Tọa Độ Bản Đồ (GPS)</span>
                            <span class="block mt-1 text-gray-900 font-mono text-sm">{{ house.latitude }}, {{ house.longitude }}</span>
                            <div class="mt-3 rounded-lg overflow-hidden border border-gray-200 h-64">
                                <iframe 
                                    :src="`https://www.google.com/maps?q=${house.latitude},${house.longitude}&hl=vi&output=embed`" 
                                    width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy">
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ảnh mặt tiền -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4">Ảnh Chụp Cơ Sở</h2>
                    <div v-if="roomImages.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div v-for="(img, idx) in roomImages" :key="idx" class="aspect-square rounded-lg border border-gray-200 overflow-hidden bg-gray-50">
                            <img :src="`/storage/${img}`" class="w-full h-full object-cover" />
                        </div>
                    </div>
                    <div v-else class="text-gray-500 text-sm italic">Không có ảnh nào.</div>
                </div>

            </div>

            <!-- Cột phải: Thông tin Chủ trọ & Thao tác -->
            <div class="space-y-6">
                <!-- Thông tin Chủ trọ -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4">Thông tin Chủ Trọ</h2>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center font-bold text-lg">
                            {{ house.user?.name?.charAt(0) || 'U' }}
                        </div>
                        <div>
                            <div class="font-bold text-gray-900">{{ house.user?.name }}</div>
                            <div class="text-xs text-gray-500">Chủ Trọ</div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500"><i class="bi bi-envelope"></i> Email</span>
                            <span class="font-medium text-gray-900">{{ house.user?.email }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500"><i class="bi bi-telephone"></i> SĐT</span>
                            <span class="font-medium text-gray-900">{{ house.user?.phone || 'Chưa cập nhật' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Thao tác duyệt -->
                <div v-if="house.status === 'pending'" class="bg-white rounded-xl shadow-sm border border-emerald-200 p-6">
                    <h2 class="text-lg font-bold text-emerald-800 mb-4">Quyết Định Duyệt</h2>
                    <p class="text-sm text-gray-600 mb-6">Hãy kiểm tra kỹ thông tin bản đồ và hình ảnh trước khi duyệt cơ sở này.</p>
                    <div class="flex flex-col gap-3">
                        <button @click="showApproveModal = true" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-emerald-500/20 flex items-center justify-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Phê Duyệt Cơ Sở Này
                        </button>
                        <button @click="showRejectModal = true" class="w-full bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                            <i class="bi bi-x-circle-fill"></i> Từ Chối Đơn Này
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Từ chối -->
        <div v-if="showRejectModal" class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-xl">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Từ chối cơ sở</h3>
                    <button @click="showRejectModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">Vui lòng cung cấp lý do từ chối cơ sở <strong>{{ house.name }}</strong>. Lý do này sẽ được gửi thông báo đến chủ trọ.</p>
                    <textarea v-model="rejectReason" rows="4" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none resize-none" placeholder="Ví dụ: Hình ảnh không rõ ràng, thông tin địa chỉ chưa chính xác..."></textarea>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button @click="showRejectModal = false" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-medium text-sm transition-colors">Hủy bỏ</button>
                    <button @click="confirmReject" class="px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-xl font-medium text-sm transition-colors shadow-sm shadow-rose-500/20">Xác nhận Từ Chối</button>
                </div>
            </div>
        </div>

        <!-- Modal Duyệt -->
        <div v-if="showApproveModal" class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-sm overflow-hidden shadow-xl transform transition-all">
                <div class="p-6 text-center">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-check2-circle text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Duyệt Cơ Sở Này?</h3>
                    <p class="text-sm text-gray-500">Chủ trọ sẽ nhận được thông báo và có thể bắt đầu đăng phòng trên cơ sở này.</p>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-center gap-3">
                    <button @click="showApproveModal = false" class="px-6 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-medium text-sm transition-colors">Hủy</button>
                    <button @click="confirmApprove" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-medium text-sm transition-colors shadow-sm shadow-emerald-500/20">Xác nhận Duyệt</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
