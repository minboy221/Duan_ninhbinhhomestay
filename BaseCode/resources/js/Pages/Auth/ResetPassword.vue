<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const showPassword = ref(false)
const showPasswordConfirm = ref(false)

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <Head title="Đặt Lại Mật Khẩu | Ninh Bình StayWord" />

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
                        style="font-family: 'Plus Jakarta Sans', sans-serif">
                        <span class="text-[#00628c]"> Khôi phục truy cập.</span>
                    </h1>
                    <p class="text-lg text-[#595c5e] max-w-lg mx-auto lg:mx-0 mb-8 leading-relaxed">
                        Tạo mật khẩu mới để tiếp tục trải nghiệm tìm kiếm nơi ở tuyệt vời tại Ninh Bình.
                    </p>
                </div>

                <!-- RIGHT: Card -->
                <div class="w-full lg:w-1/2 max-w-md">
                    <div class="rounded-2xl shadow-2xl p-8 md:p-10"
                        style="background: rgba(255,255,255,0.7); backdrop-filter: blur(24px); border: 1.5px solid rgba(255,255,255,0.4)">
                        <div class="space-y-8">
                            <!-- Header -->
                            <div class="text-center lg:text-left space-y-2">
                                <h2 class="font-headline text-3xl font-bold text-[#2c2f31]">Đặt lại mật khẩu</h2>
                                <p class="text-[#595c5e] font-body leading-snug">
                                    Vui lòng nhập mật khẩu mới cho tài khoản <span class="font-semibold text-[#00628c]">{{ email }}</span>.
                                </p>
                            </div>

                            <form @submit.prevent="submit" class="space-y-6">
                                <!-- Email (Hidden) -->
                                <input type="hidden" v-model="form.email" />

                                <div class="space-y-2">
                                    <label class="text-xs font-bold uppercase tracking-wider text-[#595c5e] px-1">
                                        Mật khẩu mới
                                    </label>
                                    <div class="relative group">
                                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#595c5e] group-focus-within:text-[#00628c] transition-colors">
                                            lock
                                        </span>
                                        <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••"
                                            class="w-full bg-white border-none rounded-xl py-4 pl-12 pr-12 focus:ring-4 focus:ring-[#57baf6]/30 transition-all text-[#2c2f31] outline-none" required />
                                        <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#747779] hover:text-[#00628c] focus:outline-none">
                                            <span class="material-symbols-outlined text-[20px]">{{ showPassword ? 'visibility_off' : 'visibility' }}</span>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.password" class="text-red-500 text-xs ml-1">
                                        {{ form.errors.password }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-bold uppercase tracking-wider text-[#595c5e] px-1">
                                        Xác nhận mật khẩu
                                    </label>
                                    <div class="relative group">
                                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#595c5e] group-focus-within:text-[#00628c] transition-colors">
                                            verified_user
                                        </span>
                                        <input v-model="form.password_confirmation" :type="showPasswordConfirm ? 'text' : 'password'" placeholder="••••••••"
                                            class="w-full bg-white border-none rounded-xl py-4 pl-12 pr-12 focus:ring-4 focus:ring-[#57baf6]/30 transition-all text-[#2c2f31] outline-none" required />
                                        <button type="button" @click="showPasswordConfirm = !showPasswordConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#747779] hover:text-[#00628c] focus:outline-none">
                                            <span class="material-symbols-outlined text-[20px]">{{ showPasswordConfirm ? 'visibility_off' : 'visibility' }}</span>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.password_confirmation" class="text-red-500 text-xs ml-1">
                                        {{ form.errors.password_confirmation }}
                                    </p>
                                </div>

                                <button type="submit" :disabled="form.processing"
                                    class="w-full py-4 bg-gradient-to-br from-[#00628c] to-[#57baf6] text-white font-headline font-bold rounded-full text-lg shadow-lg hover:scale-[1.02] transition-transform active:scale-[0.98]">
                                    {{ form.processing ? 'Đang xử lý...' : 'Lưu mật khẩu mới' }}
                                </button>
                            </form>
                            
                            <!-- Help link -->
                            <div class="pt-4 border-t border-[#abadaf]/30 text-center">
                                <Link :href="route('login')" class="text-[#595c5e] text-sm font-semibold hover:text-[#00628c] transition-colors inline-flex items-center gap-1 group">
                                    <span class="material-symbols-outlined text-sm group-hover:-translate-x-1 transition-transform">arrow_back</span>
                                    Quay lại đăng nhập
                                </Link>
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
