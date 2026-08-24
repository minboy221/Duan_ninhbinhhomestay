<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    tenants: {
        type: Array,
        default: () => []
    },
    floors: {
        type: Array,
        default: () => []
    }
})

const tenants = computed(() => props.tenants)
const showModal = ref(false)
const selected = ref(null)
const searchQ = ref('')
const floorFilter = ref('') // Bộ lọc tầng
const activeTab = ref('all') // 'all' | 'active' | 'leaving' | 'past'

const statusMap = {
    active: { label: 'Đang ở', cls: 'bg-emerald-100 text-emerald-800 border-emerald-300', dot: 'bg-emerald-600' },
    leaving: { label: 'Sắp rời đi', cls: 'bg-amber-100 text-amber-800 border-amber-300', dot: 'bg-amber-600' },
    past: { label: 'Khách cũ', cls: 'bg-slate-200 text-slate-700 border-slate-300', dot: 'bg-slate-500' },
}

const avatarColors = ['#0f766e', '#1d4ed8', '#7c3aed', '#b45309', '#dc2626', '#0891b2']
const avatarColor = (i) => avatarColors[Math.abs(i) % avatarColors.length]
const formatDate = (d) => {
    if (!d || d === 'N/A') return 'N/A'
    return new Date(d).toLocaleDateString('vi-VN')
}

const filteredTenants = computed(() => {
    return tenants.value.filter(t => {
        // Tab Filter
        if (activeTab.value === 'active' && t.status !== 'active') return false
        if (activeTab.value === 'leaving' && t.status !== 'leaving') return false
        if (activeTab.value === 'past' && t.status !== 'past') return false

        // Floor Filter
        if (floorFilter.value && String(t.floor_id) !== String(floorFilter.value)) return false

        // Search Filter (Tìm theo Tên, Số phòng, SĐT, CCCD, Vai trò)
        if (searchQ.value) {
            const q = searchQ.value.toLowerCase()
            return (t.name && t.name.toLowerCase().includes(q)) ||
                (t.room && t.room.toLowerCase().includes(q)) ||
                (t.phone && t.phone.includes(q)) ||
                (t.cccd && t.cccd.toLowerCase().includes(q)) ||
                (t.role && t.role.toLowerCase().includes(q))
        }
        return true
    })
})

const openDetail = (t) => { selected.value = t; showModal.value = true }
const closeModal = () => { showModal.value = false }

// Hàm xuất danh sách đăng ký Tạm Trú Tạm Vắng cho Công An Phường/Xã
const exportTemporaryResidenceList = () => {
    const activeTenants = tenants.value.filter(t => t.status === 'active' || t.status === 'leaving');

    if (activeTenants.length === 0) {
        alert('Hiện không có cư dân nào đang cư trú để xuất danh sách!');
        return;
    }

    let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
    csvContent += "STT,Họ và Tên,Số CCCD/CMND,Số Điện Thoại,Số Phòng,Tầng/Khu,Ngày Bắt Đầu Tạm Trú,Vai Trò\n";

    activeTenants.forEach((t, index) => {
        const cccdFormatted = t.cccd ? `'${t.cccd}` : 'Chưa cập nhật';
        const phoneFormatted = t.phone ? `'${t.phone}` : 'N/A';
        csvContent += `${index + 1},"${t.name}","${cccdFormatted}","${phoneFormatted}","Phòng ${t.room}","${t.floor}","${t.moveIn}","${t.role}"\n`;
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `DS_Tam_Tru_Cong_An_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<template>
    <LandlordLayout>
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs text-slate-500 font-bold">
                <span>Bảng điều khiển</span>
                <i class="bi bi-chevron-right text-[9px]"></i>
                <span class="text-slate-800">Khách hàng & Cư dân</span>
            </div>

            <!-- Page Title & Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="space-y-1">
                    <h2 class="text-xl font-black text-slate-900">Quản lý Khách hàng & Cư dân</h2>
                    <p class="text-xs text-slate-600 font-semibold">Danh sách khách thuê chính, người ở ghép và lịch sử
                        khách cũ đã từng ở</p>
                </div>

                <!-- Nút Xuất danh sách Tạm Trú Nộp Công An -->
                <button @click="exportTemporaryResidenceList"
                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition flex items-center gap-2 shadow-sm cursor-pointer"
                    title="Xuất file Excel/CSV danh sách cư dân nộp Công an Phường/Xã">
                    <i class="bi bi-file-earmark-excel-fill text-sm"></i> Xuất DS Tạm Trú
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg font-black">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-600">Tổng cư dân</h4>
                        <p class="text-xl font-black text-slate-900 leading-none">{{ tenants.length }}</p>
                    </div>
                </div>

                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-lg font-black">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-600">Đang cư trú</h4>
                        <p class="text-xl font-black text-slate-900 leading-none">{{
                            tenants.filter(t => t.status==='active').length }}</p>
                    </div>
                </div>

                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg font-black">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-600">Sắp rời đi</h4>
                        <p class="text-xl font-black text-slate-900 leading-none">{{
                            tenants.filter(t => t.status==='leaving').length }}</p>
                    </div>
                </div>

                <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg font-black">
                        <i class="bi bi-person-x-fill"></i>
                    </div>
                    <div class="space-y-0.5">
                        <h4 class="text-xs font-bold text-slate-600">Khách cũ đã ở</h4>
                        <p class="text-xl font-black text-slate-900 leading-none">{{
                            tenants.filter(t => t.status==='past').length }}</p>
                    </div>
                </div>
            </div>

            <!-- Tab Filters -->
            <div
                class="border-b border-slate-200 flex gap-6 text-xs sm:text-sm font-bold text-slate-600 overflow-x-auto">
                <button @click="activeTab = 'all'"
                    :class="['pb-3 border-b-2 transition-colors whitespace-nowrap', activeTab === 'all' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent hover:text-slate-900']">
                    Tất cả ({{ tenants.length }})
                </button>
                <button @click="activeTab = 'active'"
                    :class="['pb-3 border-b-2 transition-colors whitespace-nowrap', activeTab === 'active' ? 'border-emerald-600 text-emerald-700 font-black' : 'border-transparent hover:text-slate-900']">
                    Đang ở ({{tenants.filter(t => t.status === 'active').length}})
                </button>
                <button @click="activeTab = 'leaving'"
                    :class="['pb-3 border-b-2 transition-colors whitespace-nowrap', activeTab === 'leaving' ? 'border-amber-600 text-amber-700 font-black' : 'border-transparent hover:text-slate-900']">
                    Sắp rời đi ({{tenants.filter(t => t.status === 'leaving').length}})
                </button>
                <button @click="activeTab = 'past'"
                    :class="['pb-3 border-b-2 transition-colors whitespace-nowrap', activeTab === 'past' ? 'border-slate-600 text-slate-900 font-black' : 'border-transparent hover:text-slate-900']">
                    Khách cũ đã ở ({{tenants.filter(t => t.status === 'past').length}})
                </button>
            </div>

            <!-- Filter Controls Bar: Search & Floor Filter -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <!-- Search input -->
                <div class="bg-white border border-slate-200/90 rounded-2xl p-2.5 shadow-xs flex-1 max-w-md">
                    <div
                        class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-500 gap-2">
                        <i class="bi bi-search text-xs"></i>
                        <input v-model="searchQ"
                            class="bg-transparent border-none outline-none text-xs font-bold text-slate-800 w-full placeholder-slate-400"
                            placeholder="Tìm theo Tên, Số phòng, CCCD, SĐT, Vai trò..." />
                    </div>
                </div>

                <!-- Dropdown Chọn Tầng / Khu -->
                <select v-model="floorFilter"
                    class="px-3.5 py-2.5 bg-white border border-slate-200/90 rounded-2xl text-xs font-bold text-slate-700 outline-none cursor-pointer shadow-xs min-w-[160px]">
                    <option value="">Tất cả Tầng/Khu</option>
                    <option v-for="fl in floors" :key="fl.id" :value="fl.id">
                        {{ fl.name }}
                    </option>
                </select>
            </div>

            <!-- Table Tenants -->
            <div class="bg-white border border-slate-200/90 rounded-3xl shadow-xs overflow-hidden">
                <div v-if="filteredTenants.length === 0"
                    class="p-8 text-center text-slate-500 text-xs font-medium space-y-2">
                    <i class="bi bi-inbox text-3xl text-slate-300 block"></i>
                    <span>Không tìm thấy khách hàng nào phù hợp bộ lọc.</span>
                </div>

                <div v-else>
                    <!-- Desktop Table View -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="bg-slate-100/70 border-b border-slate-200 text-xs font-black text-slate-700 uppercase tracking-wider">
                                    <th class="py-4 px-6">Họ tên & CCCD</th>
                                    <th class="py-4 px-4">Vai trò</th>
                                    <th class="py-4 px-4">Số điện thoại</th>
                                    <th class="py-4 px-4">Phòng / Tầng</th>
                                    <th class="py-4 px-4">Ngày vào ở</th>
                                    <th class="py-4 px-4">Tình trạng</th>
                                    <th class="py-4 px-6 text-right">Chi tiết</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs font-bold text-slate-700">
                                <tr v-for="(t, i) in filteredTenants" :key="t.id"
                                    class="hover:bg-slate-50/70 cursor-pointer" @click="openDetail(t)">
                                    <td class="py-4 px-6 font-black text-slate-900 flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-white text-xs shadow-2xs"
                                            :style="{ backgroundColor: avatarColor(i) }">
                                            {{ t.avatar }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-slate-900">{{ t.name }}</span>
                                            <span
                                                class="text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">CCCD:
                                                {{ t.cccd }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span :class="[
                                            'px-2.5 py-0.5 rounded-md text-[11px] font-black border',
                                            t.role === 'Chủ hợp đồng' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-teal-50 text-teal-700 border-teal-200'
                                        ]">
                                            {{ t.role }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 font-black text-slate-800">{{ t.phone }}</td>
                                    <td class="py-4 px-4 font-black text-emerald-700">
                                        Phòng {{ t.room }} <span class="text-slate-500 font-bold text-[11px]">({{
                                            t.floor }})</span>
                                    </td>
                                    <td class="py-4 px-4 text-slate-700 font-bold">{{ formatDate(t.moveIn) }}</td>
                                    <td class="py-4 px-4">
                                        <span
                                            :class="['px-2.5 py-1 rounded-md text-xs font-black border flex items-center gap-1.5 w-fit shadow-2xs', statusMap[t.status]?.cls]">
                                            <span class="w-1.5 h-1.5 rounded-full"
                                                :class="statusMap[t.status]?.dot"></span>
                                            {{ statusMap[t.status]?.label }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right" @click.stop>
                                        <button @click="openDetail(t)"
                                            class="w-8 h-8 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl inline-flex items-center justify-center transition-colors">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Cards View -->
                    <div class="block md:hidden divide-y divide-slate-100">
                        <div v-for="(t, i) in filteredTenants" :key="t.id"
                            class="p-4 space-y-3 cursor-pointer hover:bg-slate-50/50" @click="openDetail(t)">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-white text-xs shrink-0 shadow-2xs"
                                        :style="{ backgroundColor: avatarColor(i) }">
                                        {{ t.avatar }}
                                    </div>
                                    <div class="space-y-0.5">
                                        <h4 class="text-sm font-black text-slate-900">{{ t.name }}</h4>
                                        <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">CCCD: {{
                                            t.cccd }}</p>
                                    </div>
                                </div>
                                <span
                                    :class="['px-2.5 py-1 rounded-md text-xs font-black border flex items-center gap-1.5 w-fit shadow-2xs', statusMap[t.status]?.cls]">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="statusMap[t.status]?.dot"></span>
                                    {{ statusMap[t.status]?.label }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-xs font-bold text-slate-700 pt-1">
                                <div class="flex items-center gap-1.5">
                                    <i class="bi bi-telephone-fill text-slate-500"></i>
                                    <span>{{ t.phone }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <i class="bi bi-house-door-fill text-emerald-600"></i>
                                    <span class="font-black text-emerald-700">Phòng {{ t.room }}</span>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between text-xs font-bold text-slate-600 border-t border-slate-100 pt-2">
                                <span>Ngày vào: {{ formatDate(t.moveIn) }}</span>
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-black">{{
                                    t.role }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Teleport to="body">
            <div v-if="showModal && selected"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center z-50 p-4"
                @click.self="closeModal">
                <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-4 bg-slate-50/70">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-white text-base shadow-xs"
                            :style="{ backgroundColor: avatarColor(selected.id) }">
                            {{ selected.avatar }}
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-base font-black text-slate-900">{{ selected.name }}</h3>
                            <span
                                :class="['px-2.5 py-0.5 rounded-md text-xs font-black border', statusMap[selected.status]?.cls]">
                                {{ statusMap[selected.status]?.label }}
                            </span>
                        </div>
                        <button @click="closeModal" class="text-slate-500 hover:text-slate-900 p-1 ml-auto">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Detail Fields -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Số điện
                                    thoại</span>
                                <p class="text-xs font-black text-slate-900">{{ selected.phone }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Căn cước
                                    (CCCD)</span>
                                <p class="text-xs font-black text-slate-900">{{ selected.cccd }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Phòng cư
                                    trú</span>
                                <p class="text-xs font-black text-emerald-700">Phòng {{ selected.room }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tầng /
                                    Khu</span>
                                <p class="text-xs font-black text-slate-900">{{ selected.floor }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ngày dọn
                                    vào</span>
                                <p class="text-xs font-black text-slate-900">{{ formatDate(selected.moveIn) }}</p>
                            </div>
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Vai trò</span>
                                <p class="text-xs font-black text-indigo-700">{{ selected.role }}</p>
                            </div>
                        </div>

                        <!-- KYС Status Badge -->
                        <div
                            class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-black">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-xs font-black text-slate-900">Trạng thái xác thực hồ sơ</p>
                                    <span class="text-xs text-emerald-800 font-black">Đã Đối Soát Với KYC Của Hệ
                                        Thống</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-2 bg-slate-50/50">
                        <button
                            class="px-4 py-2 border border-slate-300 hover:bg-slate-100 text-slate-800 font-bold text-xs rounded-xl transition-colors"
                            @click="closeModal">Đóng</button>
                        
                        <a 
                            v-if="selected.contract_pdf" 
                            :href="selected.contract_pdf" 
                            target="_blank"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5"
                        >
                            <i class="bi bi-file-earmark-pdf-fill text-sm"></i> Xem Hợp Đồng (PDF)
                        </a>
                    </div>
                </div>
            </div>
        </Teleport>
    </LandlordLayout>
</template>
