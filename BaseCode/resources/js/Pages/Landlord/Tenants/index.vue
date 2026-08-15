<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    tenants: {
        type: Array,
        default: () => []
    }
})

const tenants = computed(() => props.tenants)

const showModal  = ref(false)
const selected   = ref(null)
const searchQ    = ref('')
const activeTab  = ref('all') // 'all' | 'active' | 'leaving'

const statusMap = {
    active:  { label: 'Đang ở',      cls: 'bg-emerald-50 text-emerald-600 border-emerald-150', dot: 'bg-emerald-500' },
    leaving: { label: 'Sắp rời đi',  cls: 'bg-amber-50 text-amber-600 border-amber-150', dot: 'bg-amber-500' },
}

const avatarColors = ['#0f766e','#1d4ed8','#7c3aed','#b45309','#dc2626','#0891b2']
const avatarColor  = (i) => avatarColors[i % avatarColors.length]
const formatDate = (d) => {
    if(!d) return 'N/A'
    return new Date(d).toLocaleDateString('vi-VN')
}

const filteredTenants = computed(() => {
    return tenants.value.filter(t => {
        // Tab Filter
        if(activeTab.value === 'active' && t.status !== 'active') return false
        if(activeTab.value === 'leaving' && t.status !== 'leaving') return false

        // Search Filter
        if(searchQ.value) {
            const q = searchQ.value.toLowerCase()
            return (t.name && t.name.toLowerCase().includes(q)) || 
                   (t.room && t.room.toLowerCase().includes(q)) || 
                   (t.phone && t.phone.includes(q)) ||
                   (t.cccd && t.cccd.includes(q))
        }
        return true
    })
})

const openDetail = (t) => { selected.value = t; showModal.value = true }
const closeModal = () => { showModal.value = false }

</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-600">Khách hàng</span>
            </div>

            <!-- Page Title -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-bold text-slate-800">Quản lý Khách hàng</h2>
                    <p class="text-xs text-slate-400">Danh sách khách thuê đang cư trú hoặc chuẩn bị kết thúc thời hạn thuê</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-400">Tổng khách hàng</h4>
                        <p class="text-lg font-extrabold text-slate-800 leading-none">{{ tenants.length }}</p>
                    </div>
                </div>

                <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-400">Đang cư trú</h4>
                        <p class="text-lg font-extrabold text-slate-800 leading-none">{{ tenants.filter(t=>t.status==='active').length }}</p>
                    </div>
                </div>

                <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-base">
                        <i class="bi bi-house-door-fill"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-400">Số phòng thuê</h4>
                        <p class="text-lg font-extrabold text-slate-800 leading-none">{{ new Set(tenants.map(t=>t.room)).size }}</p>
                    </div>
                </div>

                <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-400">Chuẩn bị rời</h4>
                        <p class="text-lg font-extrabold text-slate-800 leading-none">{{ tenants.filter(t=>t.status==='leaving').length }}</p>
                    </div>
                </div>
            </div>

            <!-- Tab Filters -->
            <div class="border-b border-slate-100 flex gap-6 text-xs font-bold text-slate-400">
                <button 
                    @click="activeTab = 'all'"
                    :class="['pb-3 border-b-2 transition-colors', activeTab === 'all' ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-slate-600']"
                >
                    Tất cả
                </button>
                <button 
                    @click="activeTab = 'active'"
                    :class="['pb-3 border-b-2 transition-colors', activeTab === 'active' ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-slate-600']"
                >
                    Đang ở
                </button>
                <button 
                    @click="activeTab = 'leaving'"
                    :class="['pb-3 border-b-2 transition-colors', activeTab === 'leaving' ? 'border-emerald-500 text-emerald-600' : 'border-transparent hover:text-slate-600']"
                >
                    Sắp rời đi
                </button>
            </div>

            <!-- Search input bar -->
            <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm max-w-md">
                <div class="flex items-center bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-slate-400 gap-2">
                    <i class="bi bi-search text-xs"></i>
                    <input v-model="searchQ" class="bg-transparent border-none outline-none text-xs text-slate-700 w-full placeholder-slate-400" placeholder="Tìm theo tên, số điện thoại, số phòng..."/>
                </div>
            </div>

            <!-- Table Tenants -->
            <div class="bg-white border border-slate-100 rounded-3xl shadow-sm overflow-hidden">
                <div v-if="filteredTenants.length === 0" class="p-8 text-center text-slate-400 text-xs font-medium space-y-2">
                    <i class="bi bi-inbox text-3xl text-slate-300 block"></i>
                    <span>Không có khách hàng nào.</span>
                </div>
                <div v-else>
                    <!-- Desktop Table View (hidden on mobile) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="py-3.5 px-6">Họ tên khách</th>
                                    <th class="py-3.5 px-4">Số điện thoại</th>
                                    <th class="py-3.5 px-4">Phòng cư trú</th>
                                    <th class="py-3.5 px-4">Ngày vào ở</th>
                                    <th class="py-3.5 px-4">Tình trạng</th>
                                    <th class="py-3.5 px-6 text-right">Xem</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-xs font-semibold text-slate-600">
                                <tr v-for="(t, i) in filteredTenants" :key="t.id" class="hover:bg-slate-50/40 cursor-pointer" @click="openDetail(t)">
                                    <td class="py-4 px-6 font-bold text-slate-800 flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-white text-xs" :style="{ backgroundColor: avatarColor(i) }">
                                            {{ t.avatar }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span>{{ t.name }}</span>
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">CCCD: {{ t.cccd }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">{{ t.phone }}</td>
                                    <td class="py-4 px-4 font-bold text-emerald-600">{{ t.room }}</td>
                                    <td class="py-4 px-4 text-slate-400">{{ formatDate(t.moveIn) }}</td>
                                    <td class="py-4 px-4">
                                        <span :class="['px-2.5 py-1 rounded-md text-[10px] font-bold border flex items-center gap-1.5 w-fit', statusMap[t.status].cls]">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="statusMap[t.status].dot"></span>
                                            {{ statusMap[t.status].label }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right" @click.stop>
                                        <button @click="openDetail(t)" class="w-8 h-8 bg-slate-50 hover:bg-slate-100 rounded-lg text-slate-500 inline-flex items-center justify-center transition-colors">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards View (hidden on desktop) -->
                    <div class="block md:hidden divide-y divide-slate-100">
                        <div v-for="(t, i) in filteredTenants" :key="t.id" 
                            class="p-4 space-y-3 cursor-pointer hover:bg-slate-50/50" 
                            @click="openDetail(t)">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold text-white text-xs shrink-0" 
                                        :style="{ backgroundColor: avatarColor(i) }">
                                        {{ t.avatar }}
                                    </div>
                                    <div class="space-y-0.5">
                                        <h4 class="text-xs font-bold text-slate-800">{{ t.name }}</h4>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">CCCD: {{ t.cccd }}</p>
                                    </div>
                                </div>
                                <span :class="['px-2.5 py-1 rounded-md text-[10px] font-bold border flex items-center gap-1.5 w-fit', statusMap[t.status].cls]">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="statusMap[t.status].dot"></span>
                                    {{ statusMap[t.status].label }}
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 pt-1">
                                <div class="flex items-center gap-1.5">
                                    <i class="bi bi-telephone text-slate-400"></i>
                                    <span>{{ t.phone }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="bi bi-house text-emerald-500"></i>
                                    <span class="font-bold text-emerald-600">{{ t.room }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between text-[10px] font-semibold text-slate-400 border-t border-slate-100/50 pt-2">
                                <span>Ngày vào ở: {{ formatDate(t.moveIn) }}</span>
                                <button @click.stop="openDetail(t)" class="text-slate-500 hover:text-slate-700 flex items-center gap-0.5 font-bold">
                                    Chi tiết <i class="bi bi-chevron-right text-[8px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Teleport to="body">
            <div v-if="showModal && selected" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="closeModal">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-4 bg-slate-50/70">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center font-black text-white text-base shadow-sm" :style="{ backgroundColor: avatarColor(selected.id - 1) }">
                            {{ selected.avatar }}
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-sm font-bold text-slate-800">{{ selected.name }}</h3>
                            <span :class="['px-2 py-0.5 rounded text-[9px] font-bold border', statusMap[selected.status].cls]">
                                {{ statusMap[selected.status].label }}
                            </span>
                        </div>
                        <button @click="closeModal" class="text-slate-400 hover:text-slate-600 p-1 ml-auto">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Detail Fields -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Số điện thoại</span>
                                <p class="text-xs font-bold text-slate-800">{{ selected.phone }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Căn cước công dân (CCCD)</span>
                                <p class="text-xs font-bold text-slate-800">{{ selected.cccd }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Phòng cư trú</span>
                                <p class="text-xs font-bold text-emerald-600">{{ selected.room }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tầng</span>
                                <p class="text-xs font-bold text-slate-800">Tầng {{ selected.floor }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Ngày dọn vào</span>
                                <p class="text-xs font-bold text-slate-800">{{ formatDate(selected.moveIn) }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Số người ở thực tế</span>
                                <p class="text-xs font-bold text-slate-800">{{ selected.people }} người</p>
                            </div>
                        </div>

                        <!-- AI Verify Badge simulated -->
                        <div class="p-3 bg-emerald-50/50 border border-emerald-100 rounded-2xl flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-[10px] font-bold text-slate-700">Trạng thái xác thực hồ sơ</p>
                                    <span class="text-[9px] text-emerald-600 font-bold">Đã Đối Soát Với KYC Của Hệ Thống</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
                        <button class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors" @click="closeModal">Đóng</button>
                        <button class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors">
                            <i class="bi bi-file-earmark-text mr-1"></i> Xem hợp đồng
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>
    </LandlordLayout>
</template>
