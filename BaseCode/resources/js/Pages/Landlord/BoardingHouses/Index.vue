<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'

defineProps({
    boardingHouses: Array
})

const selectHouse = (id) => {
    router.post(route('landlord.select-boarding-house'), { id }, { preserveScroll: true })
}

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
    <Head title="Quản Lý Cơ Sở" />
    <LandlordLayout>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">Quản Lý Cơ Sở</h1>
                <p class="text-sm text-slate-500 mt-1">Quản lý danh sách các khu trọ/homestay của bạn</p>
            </div>
            <Link href="/landlord/boarding-houses/create"
                class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> Thêm Cơ Sở Mới
            </Link>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider w-16">ID</th>
                            <th class="px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Tên Cơ Sở</th>
                            <th class="px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Địa Chỉ</th>
                            <th class="px-5 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="house in boardingHouses" :key="house.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-4 text-sm font-bold text-slate-700">#{{ house.id }}</td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-800 flex items-center gap-2">
                                    {{ house.name }}
                                    <span v-if="$page.props.auth.selected_boarding_house_id === house.id" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] rounded uppercase font-black">
                                        <i class="bi bi-geo-alt-fill"></i> Đang Chọn
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm text-slate-600">{{ house.address_detail }}, {{ house.district }}</div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button v-if="$page.props.auth.selected_boarding_house_id !== house.id" 
                                    @click="selectHouse(house.id)"
                                    class="text-xs font-bold bg-white border border-emerald-200 text-emerald-600 hover:bg-emerald-50 px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1.5 mr-2">
                                    <i class="bi bi-box-arrow-in-right"></i> Chọn cơ sở
                                </button>
                                <!-- Future features: Edit, Delete etc. -->
                                <Link :href="route('landlord.boarding-houses.show', house.id)" class="text-slate-400 hover:text-emerald-500 p-2 transition-colors" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!boardingHouses.length">
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">
                                <i class="bi bi-buildings text-4xl text-slate-300 mb-3 block"></i>
                                Bạn chưa có cơ sở nào. Hãy thêm cơ sở mới.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </LandlordLayout>
</template>
