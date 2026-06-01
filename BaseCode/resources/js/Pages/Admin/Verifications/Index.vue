<script setup>
import { Link } from "@inertiajs/vue3";
defineProps({
    users: Object, //biến users này là dữ liệu phân trang
});
</script>
<template>
    <div class="max-w-7xl mx-auto p-6 bg_gray-50 min-h-screen">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">
            Quản lý Phê duyệt Chủ trọ
        </h2>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">
                            ID
                        </th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">
                            Chủ trọ
                        </th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">
                            Liên hệ
                        </th>
                        <th class="px-6 py-3 text-left text-sm font-semibold uppercase">
                            Trạng thái AI
                        </th>
                        <th class="px-6 py-3 text-center text-sm font-semibold uppercase">
                            Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="user in users?.data" :key="user.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">
                                {{ user.name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ user.email }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ user.phone || "Chưa cập nhật" }}
                        </td>
                        <td class="px-6 py-4">
                            <span v-if="
                                user.verification?.kyc_status === 'approved'
                            " class="px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                AI Đã Khớp
                            </span>
                            <span v-else class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                AI Từ Chối
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <Link :href="route('admin.verifications.show', user.id)
                                "
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium transition">
                                Xem chi tiết & Duyệt
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="p-4 border-t text-sm text-gray-500 text-center" v-if="!users?.data || users.data.length === 0">
                Không có hồ sơ nào đang chờ duyệt.
            </div>
        </div>
    </div>
</template>
