<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref } from 'vue'

const listings = ref([
    { id: 1, title: 'Phòng trọ sạch sẽ trung tâm Ninh Bình', room: 'Phòng 103', price: 3000000, area: 22, address: '15 Trần Hưng Đạo, P. Đông Thành, Ninh Bình', status: 'active', views: 128, img: null, createdAt: '2026-04-01', aiPrice: 3200000 },
    { id: 2, title: 'Phòng tầng 2 thoáng mát, ban công riêng',  room: 'Phòng 204', price: 3500000, area: 28, address: '15 Trần Hưng Đạo, P. Đông Thành, Ninh Bình', status: 'pending', views: 45, img: null, createdAt: '2026-05-10', aiPrice: 3400000 },
])

const activeTab = ref('all') // 'all' | 'active' | 'pending' | 'hidden'

const statusMap = {
    active:   { label: 'Đang Hiển Thị', cls: 'bg-emerald-50 text-emerald-600 border-emerald-100', dot: 'bg-emerald-500' },
    pending:  { label: 'Chờ Duyệt',     cls: 'bg-amber-50 text-amber-600 border-amber-100', dot: 'bg-amber-500' },
    rejected: { label: 'Bị Từ Chối',    cls: 'bg-rose-50 text-rose-600 border-rose-100', dot: 'bg-rose-500' },
    hidden:   { label: 'Đã Ẩn',         cls: 'bg-slate-50 text-slate-500 border-slate-100', dot: 'bg-slate-500' },
}

const formatMoney = (n) => new Intl.NumberFormat('vi-VN').format(n) + 'đ'
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Tin đăng</span>
            </div>

            <!-- Page Title -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">Quản lý Tin đăng</h2>
                    <p class="text-xs text-slate-400">Đăng và cập nhật thông tin phòng trống lên sàn Ninh Bình Homestay</p>
                </div>
                <a href="/landlord/listings/create" class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-emerald-500/10 flex items-center gap-1.5">
                    <i class="bi bi-plus-lg"></i> Đăng tin mới
                </a>
            </div>

            <!-- Filter Tabs -->
            <div class="border-b border-slate-100 flex gap-6 text-xs font-bold text-slate-400">
                <button 
                    @click="activeTab = 'all'"
                    :class="['pb-3 border-b-2 transition-colors', activeTab === 'all' ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-slate-600']"
                >
                    Tất cả ({{ listings.length }})
                </button>
                <button 
                    @click="activeTab = 'active'"
                    :class="['pb-3 border-b-2 transition-colors', activeTab === 'active' ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-slate-600']"
                >
                    Đang hiển thị ({{ listings.filter(l => l.status === 'active').length }})
                </button>
                <button 
                    @click="activeTab = 'pending'"
                    :class="['pb-3 border-b-2 transition-colors', activeTab === 'pending' ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-slate-600']"
                >
                    Chờ duyệt ({{ listings.filter(l => l.status === 'pending').length }})
                </button>
            </div>

            <!-- Listings Cards Deck -->
            <div v-if="listings.length === 0" class="p-8 text-center text-slate-400 text-xs font-medium space-y-2 bg-white rounded-3xl border border-slate-100">
                <i class="bi bi-megaphone text-3xl text-slate-300 block"></i>
                <span>Chưa có tin đăng nào. Hãy tạo tin đăng đầu tiên của bạn!</span>
            </div>
            <div v-else class="space-y-4">
                <div 
                    v-for="ls in listings" 
                    :key="ls.id"
                    v-show="activeTab === 'all' || ls.status === activeTab"
                    class="bg-white border border-slate-100 rounded-3xl overflow-hidden flex flex-col md:flex-row hover:shadow-md transition-all duration-200"
                >
                    <!-- Left Image -->
                    <div class="w-full md:w-56 bg-slate-50 flex items-center justify-center text-slate-300 min-h-[140px] md:min-h-0 relative">
                        <i class="bi bi-image text-3xl"></i>
                        <span class="absolute top-3 left-3 px-2 py-1 bg-white/95 backdrop-blur-sm shadow-sm rounded-lg text-[9px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusMap[ls.status].dot"></span>
                            {{ statusMap[ls.status].label }}
                        </span>
                    </div>

                    <!-- Middle Info Body -->
                    <div class="p-6 flex-1 flex flex-col justify-between gap-4">
                        <div class="space-y-2">
                            <h3 class="text-sm font-bold text-slate-800 leading-snug">{{ ls.title }}</h3>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-400 font-semibold">
                                <span class="flex items-center gap-1"><i class="bi bi-house-door text-emerald-600"></i> {{ ls.room }}</span>
                                <span class="flex items-center gap-1"><i class="bi bi-aspect-ratio text-emerald-600"></i> {{ ls.area }} m²</span>
                                <span class="flex items-center gap-1"><i class="bi bi-geo-alt text-emerald-600"></i> {{ ls.address }}</span>
                            </div>
                        </div>

                        <!-- Price & AI suggestions -->
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="text-slate-800 font-black text-base">
                                {{ formatMoney(ls.price) }}<span class="text-[10px] text-slate-400 font-bold">/tháng</span>
                            </div>
                            
                            <!-- AI Pricing Suggestion -->
                            <div class="px-2.5 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-xl text-[10px] font-bold flex items-center gap-1.5">
                                <i class="bi bi-stars text-emerald-500"></i>
                                <span>Giá AI gợi ý: <strong>{{ formatMoney(ls.aiPrice) }}</strong></span>
                                <span :class="ls.aiPrice > ls.price ? 'text-blue-600' : 'text-emerald-700'">
                                    {{ ls.aiPrice > ls.price ? '(Có thể tăng giá)' : '(Giá tốt)' }}
                                </span>
                            </div>
                        </div>

                        <!-- View Stats -->
                        <div class="flex items-center gap-4 text-[10px] text-slate-400 font-bold">
                            <span><i class="bi bi-eye mr-1"></i> {{ ls.views }} lượt xem</span>
                            <span><i class="bi bi-calendar3 mr-1"></i> Ngày tạo: {{ new Date(ls.createdAt).toLocaleDateString('vi-VN') }}</span>
                        </div>
                    </div>

                    <!-- Right Action Bar -->
                    <div class="p-6 md:border-l border-slate-50 flex flex-row md:flex-col justify-center gap-2 bg-slate-50/35">
                        <button class="flex-1 md:flex-none px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors flex items-center justify-center gap-1">
                            <i class="bi bi-pencil-square"></i> Chỉnh sửa
                        </button>
                        <button class="px-3.5 py-2 hover:bg-slate-100 text-slate-500 rounded-xl transition-colors"><i class="bi bi-eye-slash"></i></button>
                        <button class="px-3.5 py-2 hover:bg-rose-50 text-rose-500 rounded-xl transition-colors"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
