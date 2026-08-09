<script setup>
import { useForm, Head, usePage } from "@inertiajs/vue3";
import { onMounted, watch } from "vue";
import Swal from "sweetalert2";

// Khởi tạo form đồng bộ dữ liệu với Backend qua Inertia
const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const page = usePage();

const showLockedAlert = () => {
    if (page.props.flash && page.props.flash.error) {
        Swal.fire({
            icon: 'error',
            title: 'Tài khoản bị khóa',
            text: page.props.flash.error,
            confirmButtonText: 'Đã hiểu',
            confirmButtonColor: '#f97316'
        });
    }
};

onMounted(() => {
    showLockedAlert();
});

watch(() => page.props.flash?.error, () => {
    showLockedAlert();
});

// Hàm xử lý submit form
const submit = () => {
    form.post(route("admin.login.store"), {
        onFinish: () => form.reset("password"), // Xóa trống ô password nếu đăng nhập lỗi
    });
};
</script>

<template>
    <Head title="Đăng nhập quản trị viên" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="login-container w-full max-w-md bg-white p-8 rounded-xl shadow-lg border border-gray-100">
            <div class="text-center mb-8">
                <!-- Thêm Logo -->
                <div class="flex justify-center mb-4">
                    <img src="/anh/logo.png" alt="Logo" class="h-20 w-auto object-contain" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">HỆ THỐNG QUẢN TRỊ NinhBinhHomeStay</h2>
                <p class="text-sm text-gray-500 mt-2">Dành riêng cho quản trị viên hệ thống</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Nhập Email -->
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input
                        type="email"
                        v-model="form.email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        placeholder="Nhập email admin..."
                        required
                    />
                    <!-- Hiển thị lỗi từ Laravel validate trả về -->
                    <span class="error-msg text-red-500 text-xs mt-1 block font-medium" v-if="form.errors.email">
                        {{ form.errors.email }}
                    </span>
                </div>

                <!-- Nhập Mật khẩu -->
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                    <input
                        type="password"
                        v-model="form.password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        placeholder="Nhập mật khẩu..."
                        required
                    />
                    <span class="error-msg text-red-500 text-xs mt-1 block font-medium" v-if="form.errors.password">
                        {{ form.errors.password }}
                    </span>
                </div>

                <!-- Ghi nhớ đăng nhập -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember"
                            type="checkbox"
                            v-model="form.remember"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>
                </div>

                <!-- Nút bấm -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="form.processing" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Đang xử lý...
                    </span>
                    <span v-else>Đăng nhập</span>
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
/* Không cần custom css phức tạp vì đã dùng TailwindCSS */
</style>
