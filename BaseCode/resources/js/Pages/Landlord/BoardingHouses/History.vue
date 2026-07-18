<script setup>
import { Head, Link } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'

defineProps({
    boardingHouses: Array
})

const getStatusBadge = (status) => {
    switch (status) {
        case 'approved': return 'bg-emerald-100 text-emerald-700'
        case 'pending': return 'bg-amber-100 text-amber-700'
        case 'rejected': return 'bg-rose-100 text-rose-700'
        default: return 'bg-slate-100 text-slate-700'
    }
}
const getStatusText = (status) => {
    switch (status) {
        case 'approved': return 'Đã Duyệt'
        case 'pending': return 'Đang Chờ'
        case 'rejected': return 'Từ Chối'
        default: return 'Không rõ'
    }
}
</script>

<template>
    <Head title="Hồ Sơ Xét Duyệt" />
    <LandlordLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">Hồ Sơ Xét Duyệt</h1>
                <p class="text-sm text-slate-500 mt-1">Lịch sử nộp đơn thêm cơ sở mới của bạn</p>
            </div>
            <Link href="/landlord/boarding-houses/create"
                class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> Thêm Cơ Sở Mới
            </Link>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div v-if="!boardingHouses.length" class="px-5 py-8 text-center text-slate-500">
                <i class="bi bi-file-earmark-x text-4xl text-slate-300 mb-3 block"></i>
                Bạn chưa nộp đơn thêm cơ sở nào.
            </div>
            <div v-else>
                <!-- Desktop Table View (hidden on mobile) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-100">
                                <th class="px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider w-16">ID</th>
                                <th class="px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Tên Cơ Sở</th>
                                <th class="px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Địa Chỉ</th>
                                <th class="px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Trạng Thái</th>
                                <th class="px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Ngày Tạo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="house in boardingHouses" :key="house.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-4 text-sm font-bold text-slate-700">#{{ house.id }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-800 flex items-center gap-2">
                                        {{ house.name }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-slate-600">{{ house.address_detail }}, {{ house.district }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span :class="['px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider', getStatusBadge(house.status)]">
                                        {{ getStatusText(house.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right text-sm text-slate-500">
                                    {{ new Date(house.created_at).toLocaleDateString('vi-VN') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards View (hidden on desktop) -->
                <div class="block md:hidden divide-y divide-slate-100">
                    <div v-for="house in boardingHouses" :key="house.id" 
                        class="p-4 space-y-3 hover:bg-slate-50/50 transition-colors">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-400">#{{ house.id }}</span>
                                <h4 class="text-xs font-bold text-slate-800">{{ house.name }}</h4>
                            </div>
                            <span :class="['px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider', getStatusBadge(house.status)]">
                                {{ getStatusText(house.status) }}
                            </span>
                        </div>
                        
                        <div class="text-[11px] text-slate-600 flex items-start gap-1">
                            <i class="bi bi-map text-slate-400 mt-0.5"></i>
                            <span>{{ house.address_detail }}, {{ house.district }}</span>
                        </div>
                        
                        <div class="text-[10px] text-slate-400 font-semibold flex items-center justify-between border-t border-slate-100/50 pt-2">
                            <span>Ngày tạo</span>
                            <span>{{ new Date(house.created_at).toLocaleDateString('vi-VN') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
