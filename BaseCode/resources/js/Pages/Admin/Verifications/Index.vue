<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    users: Object, //biến users này là dữ liệu phân trang
});
</script>

<template>
    <Head title="Admin - Quản lý phê duyệt Chủ trọ" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="text-xl font-bold text-gray-900">Quản lý Phê duyệt Chủ trọ</h1>
                <p class="text-sm text-gray-500 mt-1">Danh sách các tài khoản đang chờ kiểm duyệt KYC và hồ sơ nhà trọ.</p>
            </div>
        </template>

        <div class="mt-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Toolbar -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-semibold text-gray-700">Tất cả hồ sơ</span>
                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                            {{ users?.data?.length || 0 }}
                        </span>
                    </div>
                </div>

                <!-- Bảng Dữ Liệu -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Chủ trọ (Người dùng)
                                </th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Liên hệ
                                </th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Trạng thái AI
                                </th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    Thao tác
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="user in users?.data" :key="user.id" class="hover:bg-blue-50/50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900">{{ user.name }}</div>
                                            <div class="text-sm text-gray-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium flex items-center gap-1.5">
                                        <i class="bi bi-telephone-fill text-gray-400 text-xs"></i>
                                        {{ user.phone || "Chưa cập nhật" }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="user.verification?.kyc_status === 'approved'"
                                          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200 shadow-sm">
                                        <i class="bi bi-check-circle-fill text-[10px]"></i> AI Đã Khớp
                                    </span>
                                    <span v-else 
                                          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200 shadow-sm">
                                        <i class="bi bi-x-circle-fill text-[10px]"></i> AI Từ Chối
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('admin.verifications.show', user.id)"
                                          class="inline-flex items-center gap-1 px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white rounded-lg font-semibold transition-all shadow-sm group-hover:shadow group-hover:-translate-y-0.5">
                                        <i class="bi bi-eye-fill"></i>
                                        Kiểm duyệt
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Trạng thái trống -->
                    <div class="p-12 text-center flex flex-col items-center justify-center" v-if="!users?.data || users.data.length === 0">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="bi bi-inbox-fill text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900">Không có hồ sơ nào</h3>
                        <p class="mt-1 text-sm text-gray-500">Hiện tại chưa có hồ sơ chủ trọ nào đang chờ duyệt.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
