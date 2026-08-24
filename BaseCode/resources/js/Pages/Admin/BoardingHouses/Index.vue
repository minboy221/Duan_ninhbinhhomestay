<script setup>
import { Head, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref } from 'vue'

const props = defineProps({
    pendingHouses: Array
})
</script>

<template>
    <Head title="Duyệt Cơ Sở Mới" />
    <AdminLayout>
        <template #header-title>
            <h1 class="header-title">Duyệt Cơ Sở Mới</h1>
            <p class="text-sm text-gray-500 mt-1">Quản lý và xét duyệt các cơ sở/khu trọ mới do chủ trọ tạo thêm.</p>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 border-b border-gray-100 text-gray-700">
                        <tr>
                            <th class="px-6 py-4 font-semibold w-16">ID</th>
                            <th class="px-6 py-4 font-semibold">Thông tin Cơ Sở</th>
                            <th class="px-6 py-4 font-semibold">Thông tin Chủ Trọ</th>
                            <th class="px-6 py-4 font-semibold">Tài Liệu Đính Kèm</th>
                            <th class="px-6 py-4 font-semibold">Ngày tạo</th>
                            <th class="px-6 py-4 font-semibold text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="house in pendingHouses" :key="house.id" class="hover:bg-gray-50/50">
                            <td class="px-6 py-4 font-medium text-gray-900">#{{ house.id }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">{{ house.name }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ house.address_detail }}, {{ house.district }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-800">{{ house.user?.name }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ house.user?.email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <!-- Hiển thị ảnh tài liệu (nếu có) -->
                                <div class="flex gap-2">
                                    <div v-if="house.contract_images" class="text-xs text-blue-600 font-medium">
                                        <i class="bi bi-file-image"></i> Có ảnh hợp đồng
                                    </div>
                                    <div v-if="house.room_images" class="text-xs text-emerald-600 font-medium">
                                        <i class="bi bi-image"></i> Có ảnh mặt tiền
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                {{ new Date(house.created_at).toLocaleDateString('vi-VN') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/admin/boarding-houses/${house.id}`" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl font-semibold transition-colors text-sm">
                                    <i class="bi bi-eye"></i> Xem Chi Tiết
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="pendingHouses.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="bi bi-inbox text-4xl text-gray-300 block mb-3"></i>
                                Hiện tại không có cơ sở nào cần xét duyệt
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </AdminLayout>
</template>
