<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

defineProps({
    status: { type: String },
})

const form = useForm({
    otp: '',
})

const otpInputs = ref(['', '', '', '', '', ''])

const submitOtp = () => {
    form.otp = otpInputs.value.join('')
    form.post(route('verification.verify.otp'), {
        preserveScroll: true,
        onError: () => {
            otpInputs.value = ['', '', '', '', '', '']
            document.getElementById('otp-0')?.focus()
        }
    })
}

const resendOtp = () => {
    router.post(route('verification.send'), {}, {
        preserveScroll: true,
    })
}

const handleInput = (index, event) => {
    const value = event.target.value;
    if (value && index < 5) {
        document.getElementById(`otp-${index + 1}`).focus()
    }
}

const handleKeydown = (index, event) => {
    if (event.key === 'Backspace' && !otpInputs.value[index] && index > 0) {
        document.getElementById(`otp-${index - 1}`).focus()
    }
}
</script>

<template>

    <Head title="Đăng Nhập | Ninh Bình StayWord" />

    <!-- Tailwind config + fonts được load qua vite, không cần CDN -->
    <main class="relative min-h-screen flex flex-col items-center justify-center overflow-hidden">

        <!-- Background -->
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover opacity-100" src="/anh/background_login.png" alt="background" />
            <div class="absolute inset-0 bg-gradient-to-tr from-[#f5f7f9] via-[#f5f7f9]/40 to-transparent"></div>
        </div>

        <div class="container max-w-6xl px-6 relative z-10">
            <div class="flex flex-col lg:flex-row items-center gap-16">

                <!-- LEFT: Branding -->
                <div class="w-full lg:w-1/2 text-center lg:text-left">
                    <span class="text-xs uppercase tracking-[0.2em] text-[#00628c] font-bold mb-4 block">
                        Ninh Bình HomeStay
                    </span>
                    <h1 class="text-5xl md:text-7xl font-bold tracking-tight text-[#2c2f31] mb-6 leading-[1.1]"
                        style="font-family: Arial, sans-serif">
                        <span class="text-[#00628c]"> Ninh Bình Home stay.</span>
                    </h1>
                    <p class="text-lg text-[#595c5e] max-w-lg mx-auto lg:mx-0 mb-8 leading-relaxed">
                        Nền tảng giúp bạn tìm nơi ở nhanh chóng và minh bạch tại Ninh Bình.
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center lg:justify-start">
                        <div class="flex items-center gap-2 px-4 py-2 bg-[#eef1f3] rounded-full">
                            <span class="material-symbols-outlined text-[#00628c] text-sm"
                                style="font-variation-settings: 'FILL' 1">check_circle</span>
                            <span class="text-sm font-medium">An Toàn</span>
                        </div>
                        <div class="flex items-center gap-2 px-4 py-2 bg-[#eef1f3] rounded-full">
                            <span class="material-symbols-outlined text-[#00628c] text-sm"
                                style="font-variation-settings: 'FILL' 1">check_circle</span>
                            <span class="text-sm font-medium">Minh Bạch</span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Card -->
                <div class="w-full lg:w-1/2 max-w-md">
                    <div class="rounded-2xl shadow-2xl p-8 md:p-10"
                        style="background: rgba(255,255,255,0.7); backdrop-filter: blur(24px); border: 1.5px solid rgba(255,255,255,0.4)">
                        <!-- FORM NHẬP MÃ XÁC MINH -->
                        <div class="space-y-8">
                                    <!-- Header -->
                                    <div class="text-center lg:text-left space-y-2">
                                        <h2 class="font-headline text-3xl font-bold text-on-primary-container">Xác minh
                                            Email
                                        </h2>
                                        <p class="text-on-surface-variant font-body leading-snug">
                                            Vui lòng nhập mã xác minh 6 chữ số đã được gửi đến địa chỉ email của bạn.
                                        </p>
                                    </div>
                                    <!-- Status message -->
                                    <div v-if="status" class="mb-4 text-sm font-medium text-green-600 text-center bg-green-50 p-3 rounded-lg border border-green-200">
                                        {{ status === 'verification-link-sent' ? 'Một mã xác minh mới đã được gửi đến email của bạn.' : status }}
                                    </div>
                                    <div v-if="form.errors.otp" class="mb-4 text-sm font-medium text-red-600 text-center bg-red-50 p-3 rounded-lg border border-red-200">
                                        {{ form.errors.otp }}
                                    </div>

                                    <!-- OTP Inputs -->
                                    <form @submit.prevent="submitOtp" class="space-y-6">
                                        <div class="flex justify-between gap-1">
                                            <input v-for="(val, index) in otpInputs" :key="index"
                                                :id="'otp-' + index"
                                                v-model="otpInputs[index]"
                                                @input="handleInput(index, $event)"
                                                @keydown="handleKeydown(index, $event)"
                                                class="otp-input w-12 h-14 lg:w-14 lg:h-16 text-center text-2xl font-bold rounded-lg border border-gray-300 bg-white text-gray-800 focus:outline-none focus:border-primary focus:ring-2 focus:ring-[#57baf6]/30 focus:scale-110 transition-all duration-200"
                                                maxlength="1" type="text" />
                                        </div>

                                        <!-- Actions -->
                                        <div class="space-y-6">
                                            <button type="submit" :disabled="form.processing"
                                                class="w-full py-4 bg-gradient-to-br from-[#00628c] to-[#57baf6] text-white font-headline font-bold rounded-full text-lg shadow-lg hover:scale-[1.02] transition-transform active:scale-[0.98]">
                                                {{ form.processing ? 'Đang xử lý...' : 'Xác minh' }}
                                            </button>
                                            <div class="text-center">
                                                <p class="text-on-surface-variant text-sm mb-2 text-gray-500">Không nhận được mã?</p>
                                                <button type="button" @click="resendOtp"
                                                    class="text-[#00628c] font-semibold hover:text-[#57baf6] transition-colors inline-flex items-center gap-1 group">
                                                    Gửi lại mã
                                                    <span class="material-symbols-outlined text-sm group-hover:translate-x-1 transition-transform">arrow_forward</span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                <!-- Help link & Logout -->
                                <div class="pt-4 border-t border-gray-200 text-center flex justify-between items-center px-2">
                                    <a class="text-gray-500 text-xs uppercase tracking-widest hover:text-[#00628c] transition-colors"
                                        href="#">Trung tâm hỗ trợ</a>
                                    
                                    <button type="button" @click="router.post(route('logout'))"
                                        class="text-gray-500 text-xs uppercase tracking-widest hover:text-red-600 transition-colors flex items-center gap-1">
                                        <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                    </button>
                                </div>
                        </div>
                        <!-- Social Login -->
                        <div class="mt-10">
                            <div class="relative flex items-center mb-8">
                                <div class="flex-grow border-t border-[#abadaf]/30"></div>
                                <span
                                    class="flex-shrink mx-4 text-xs font-bold text-[#abadaf] uppercase tracking-widest">
                                    Hoặc tiếp tục với
                                </span>
                                <div class="flex-grow border-t border-[#abadaf]/30"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <button type="button"
                                    class="flex items-center justify-center gap-2 py-3 px-4 bg-white border border-[#abadaf]/20 rounded-full hover:bg-[#e5e9eb] transition-all">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                                        <path
                                            d="M12 5.04c1.94 0 3.68.67 5.05 1.97l3.77-3.77C18.53 1.15 15.48 0 12 0 7.31 0 3.25 2.69 1.25 6.63l4.13 3.2C6.35 6.96 8.98 5.04 12 5.04z"
                                            fill="#EA4335" />
                                        <path
                                            d="M23.49 12.27c0-.83-.07-1.63-.2-2.39H12v4.51h6.44c-.28 1.48-1.12 2.74-2.38 3.58l3.7 2.87c2.16-2 3.44-4.94 3.44-8.57z"
                                            fill="#4285F4" />
                                        <path
                                            d="M5.38 14.83c-.23-.69-.36-1.42-.36-2.19 0-.77.13-1.5.36-2.19l-4.13-3.2c-.8 1.58-1.25 3.35-1.25 5.23 0 1.88.45 3.65 1.25 5.23l4.13-3.08z"
                                            fill="#FBBC05" />
                                        <path
                                            d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.7-2.87c-1.1.73-2.5 1.17-4.23 1.17-3.02 0-5.65-1.92-6.62-4.79l-4.13 3.2C3.25 21.31 7.31 24 12 24z"
                                            fill="#34A853" />
                                    </svg>
                                    <span class="text-sm font-semibold">Google</span>
                                </button>
                                <button type="button"
                                    class="flex items-center justify-center gap-2 py-3 px-4 bg-white border border-[#abadaf]/20 rounded-full hover:bg-[#e5e9eb] transition-all">
                                    <svg class="w-5 h-5" fill="#1877F2" viewBox="0 0 24 24">
                                        <path
                                            d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                    </svg>
                                    <span class="text-sm font-semibold">Facebook</span>
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Decorative blobs -->
        <div class="absolute bottom-10 left-10 w-64 h-64 bg-[#57baf6]/20 blur-[100px] rounded-full -z-10"></div>
        <div class="absolute top-20 right-10 w-96 h-96 bg-[#50e1f9]/10 blur-[120px] rounded-full -z-10"></div>
    </main>
</template>