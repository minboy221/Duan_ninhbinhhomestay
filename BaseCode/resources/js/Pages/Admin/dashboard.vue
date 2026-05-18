<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object
});
</script>

<template>

    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Trang Quản Trị</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold mb-4">Thống kê hệ thống</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-blue-100 rounded-lg">
                            <p class="text-blue-600">Tổng số người dùng</p>
                            <span class="text-2xl font-bold">{{ stats.totalUsers }}</span>
                        </div>
                        <!-- Thêm các thẻ thống kê khác -->
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>