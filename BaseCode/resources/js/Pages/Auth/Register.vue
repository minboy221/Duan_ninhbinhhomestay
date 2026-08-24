<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
})

// Tab hiện tại: 'login' | 'signup' | 'forgot'
const activeTab = ref('signup')

const showLoginPassword = ref(false)
const showSignupPassword = ref(false)
const showSignupPasswordConfirm = ref(false)

const captchaUrl = ref('/captcha/flat?' + Math.random())
const reloadCaptcha = () => {
    captchaUrl.value = '/captcha/flat?' + Math.random()
}

// Form đăng nhập
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
})

// Form đăng ký
const signupForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    captcha: '',
    terms: false,
})

// Form quên mật khẩu
const forgotForm = useForm({
    email: '',
})

const submitLogin = () => {
    loginForm.post(route('login'), {
        onFinish: () => {
            loginForm.reset('password');
        },
    })
}

const submitSignup = () => {
    signupForm.post(route('register'), {
        onFinish: () => {
            signupForm.reset('password', 'password_confirmation', 'captcha');
            reloadCaptcha();
        },
    })
}

const submitForgot = () => {
    forgotForm.post(route('password.email'))
}
</script>

<template>

    <Head title="Đăng Ký Tài Khoản | Ninh Bình StayWord" />

    <!-- Tailwind config + fonts được load qua vite, không cần CDN -->
    <main class="relative min-h-screen flex flex-col items-center justify-center overflow-hidden">

        <!-- Background -->
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover opacity-60" src="/anh/background_login.png" alt="background" />
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
                        <span v-if="activeTab === 'login'">Welcome Back To</span>
                        <span v-else-if="activeTab === 'signup'">Tạo Tài Khoản</span>
                        <span v-else>Quên Mật Khẩu?</span>
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
                <div class="w-full lg:w-1/2 max-w-lg">
                    <div class="rounded-2xl shadow-2xl p-6 md:p-8"
                        style="background: rgba(255,255,255,0.7); backdrop-filter: blur(24px); border: 1.5px solid rgba(255,255,255,0.4)">

                        <!-- Tabs -->
                        <div class="flex p-1.5 bg-[#e5e9eb]/50 backdrop-blur-sm rounded-full mb-6 w-fit mx-auto">
                            <button @click="activeTab = 'login'" :class="activeTab === 'login'
                                ? 'bg-white shadow-sm text-[#00628c]'
                                : 'text-[#595c5e] hover:text-[#2c2f31]'"
                                class="px-8 py-3 rounded-full text-sm font-bold transition-all duration-300">
                                Đăng nhập
                            </button>
                            <button @click="activeTab = 'signup'" :class="activeTab === 'signup'
                                ? 'bg-white shadow-sm text-[#00628c]'
                                : 'text-[#595c5e] hover:text-[#2c2f31]'"
                                class="px-8 py-3 rounded-full text-sm font-bold transition-all duration-300">
                                Đăng ký
                            </button>
                        </div>

                        <!-- Status message -->
                        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                            {{ status }}
                        </div>

                        <!-- ===== FORM ĐĂNG KÝ ===== -->
                        <form v-else-if="activeTab === 'signup'" @submit.prevent="submitSignup" class="space-y-3.5">
                            <div>
                                <div class="relative group">
                                    <span
                                        class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#595c5e] group-focus-within:text-[#00628c] transition-colors">
                                        person
                                    </span>
                                    <input v-model="signupForm.name" type="text" placeholder="Họ và tên"
                                        class="w-full bg-white border-none rounded-xl py-3 pl-12 pr-4 focus:ring-4 focus:ring-[#57baf6]/30 transition-all text-[#2c2f31] outline-none" />
                                </div>
                                <p v-if="signupForm.errors.name" class="text-red-500 text-xs ml-4 mt-1">
                                    {{ signupForm.errors.name }}
                                </p>
                            </div>

                            <div>
                                <div class="relative group">
                                    <span
                                        class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#595c5e] group-focus-within:text-[#00628c] transition-colors">
                                        alternate_email
                                    </span>
                                    <input v-model="signupForm.email" type="email" placeholder="Địa chỉ Email"
                                        class="w-full bg-white border-none rounded-xl py-3 pl-12 pr-4 focus:ring-4 focus:ring-[#57baf6]/30 transition-all text-[#2c2f31] outline-none" />
                                </div>
                                <p v-if="signupForm.errors.email" class="text-red-500 text-xs ml-4 mt-1">
                                    {{ signupForm.errors.email }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                                <div>
                                    <div class="relative group">
                                        <span
                                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#595c5e] group-focus-within:text-[#00628c] transition-colors">
                                            lock
                                        </span>
                                        <input v-model="signupForm.password" :type="showSignupPassword ? 'text' : 'password'" placeholder="Mật khẩu"
                                            class="w-full bg-white border-none rounded-xl py-3 pl-12 pr-12 focus:ring-4 focus:ring-[#57baf6]/30 transition-all text-[#2c2f31] outline-none" />
                                        <button type="button" @click="showSignupPassword = !showSignupPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#747779] hover:text-[#00628c] focus:outline-none">
                                            <span class="material-symbols-outlined text-[20px]">{{ showSignupPassword ? 'visibility_off' : 'visibility' }}</span>
                                        </button>
                                    </div>
                                    <p v-if="signupForm.errors.password" class="text-red-500 text-xs ml-1 mt-1">
                                        {{ signupForm.errors.password }}
                                    </p>
                                </div>
                                <div>
                                    <div class="relative group">
                                        <span
                                            class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#595c5e] group-focus-within:text-[#00628c] transition-colors">
                                            verified_user
                                        </span>
                                        <input v-model="signupForm.password_confirmation" :type="showSignupPasswordConfirm ? 'text' : 'password'"
                                            placeholder="Xác nhận mật khẩu"
                                            class="w-full bg-white border-none rounded-xl py-3 pl-12 pr-12 focus:ring-4 focus:ring-[#57baf6]/30 transition-all text-[#2c2f31] outline-none" />
                                        <button type="button" @click="showSignupPasswordConfirm = !showSignupPasswordConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#747779] hover:text-[#00628c] focus:outline-none">
                                            <span class="material-symbols-outlined text-[20px]">{{ showSignupPasswordConfirm ? 'visibility_off' : 'visibility' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="flex flex-col gap-2">
                                    <div class="relative group w-full">
                                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-[#595c5e] group-focus-within:text-[#00628c] transition-colors">
                                            verified
                                        </span>
                                        <input v-model="signupForm.captcha" type="text" placeholder="Nhập mã xác nhận"
                                            class="w-full bg-white border-none rounded-xl py-3 pl-12 pr-4 focus:ring-4 focus:ring-[#57baf6]/30 transition-all text-[#2c2f31] outline-none" />
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <img :src="captchaUrl" alt="captcha" class="rounded-xl h-[46px] cursor-pointer border border-gray-200" @click="reloadCaptcha" title="Bấm vào để đổi mã" />
                                        <button type="button" @click="reloadCaptcha" class="flex items-center justify-center w-[46px] h-[46px] bg-white rounded-xl shadow-sm text-[#595c5e] hover:text-[#00628c] hover:bg-[#f0f8ff] transition-all focus:outline-none" title="Tải lại mã">
                                            <span class="material-symbols-outlined text-[20px]">refresh</span>
                                        </button>
                                    </div>
                                </div>
                                <p v-if="signupForm.errors.captcha" class="text-red-500 text-xs ml-1 mt-1">
                                    {{ signupForm.errors.captcha }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-start gap-2.5 py-1">
                                    <input v-model="signupForm.terms" type="checkbox"
                                        class="mt-1 w-4 h-4 rounded text-[#00628c] focus:ring-[#57baf6]/20" />
                                    <span class="text-xs text-[#595c5e] leading-relaxed">
                                        Tôi đồng ý với
                                        <a href="#"
                                            class="text-[#00628c] font-semibold underline underline-offset-4">Điều khoản dịch vụ</a>
                                        và
                                        <a href="#"
                                            class="text-[#00628c] font-semibold underline underline-offset-4">Chính sách bảo mật</a>.
                                    </span>
                                </div>
                                <p v-if="signupForm.errors.terms" class="text-red-500 text-xs ml-8">
                                    {{ signupForm.errors.terms }}
                                </p>
                            </div>

                            <button type="submit" :disabled="signupForm.processing"
                                :class="{ 'opacity-50 cursor-not-allowed': signupForm.processing }"
                                class="w-full py-3 rounded-full bg-gradient-to-br from-[#00628c] to-[#57baf6] text-white font-bold text-base shadow-lg hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                                {{ signupForm.processing ? 'Đang xử lý...' : 'Tạo tài khoản' }}
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </button>
                        </form>

                        <!-- ===== FORM ĐĂNG NHẬP ===== -->
                        <form v-if="activeTab === 'login'" @submit.prevent="submitLogin" class="space-y-4">
                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#595c5e] ml-4">
                                    Email
                                </label>
                                <input v-model="loginForm.email" type="email" placeholder="example@email.com"
                                    class="w-full px-6 py-3 bg-white border-none rounded-xl focus:ring-4 focus:ring-[#57baf6]/30 transition-all outline-none text-[#2c2f31] placeholder:text-[#747779]/60" />
                                <p v-if="loginForm.errors.email" class="text-red-500 text-xs mt-1 ml-4">
                                    {{ loginForm.errors.email }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-[#595c5e] ml-4">
                                    Mật khẩu
                                </label>
                                <div class="relative group">
                                    <input v-model="loginForm.password" :type="showLoginPassword ? 'text' : 'password'" placeholder="••••••••"
                                        class="w-full pl-6 pr-12 py-3 bg-white border-none rounded-xl focus:ring-4 focus:ring-[#57baf6]/30 transition-all outline-none text-[#2c2f31]" />
                                    <button type="button" @click="showLoginPassword = !showLoginPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#747779] hover:text-[#00628c] focus:outline-none">
                                        <span class="material-symbols-outlined text-[20px]">{{ showLoginPassword ? 'visibility_off' : 'visibility' }}</span>
                                    </button>
                                </div>
                                <p v-if="loginForm.errors.password" class="text-red-500 text-xs mt-1 ml-4">
                                    {{ loginForm.errors.password }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input v-model="loginForm.remember" type="checkbox"
                                        class="w-4 h-4 rounded text-[#00628c] focus:ring-[#57baf6]" />
                                    <span class="text-[#595c5e] group-hover:text-[#2c2f31] transition-colors">
                                        Ghi nhớ đăng nhập
                                    </span>
                                </label>
                                <button type="button" @click="activeTab = 'forgot'"
                                    class="text-[#00628c] font-semibold hover:underline">
                                    Quên mật khẩu?
                                </button>
                            </div>

                            <button type="submit" :disabled="loginForm.processing"
                                :class="{ 'opacity-50 cursor-not-allowed': loginForm.processing }"
                                class="w-full py-3 bg-gradient-to-r from-[#00628c] to-[#46ace7] text-white font-bold rounded-full shadow-lg hover:scale-[1.02] transition-transform active:scale-95">
                                {{ loginForm.processing ? 'Đang xử lý...' : 'Đăng nhập' }}
                            </button>
                        </form>

                        <!-- ===== FORM QUÊN MẬT KHẨU ===== -->
                        <form v-else-if="activeTab === 'forgot'" @submit.prevent="submitForgot" class="space-y-4">
                            <p class="text-xs text-[#595c5e] text-center">
                                Nhập email của bạn, chúng tôi sẽ gửi link đặt lại mật khẩu.
                            </p>
                            <div class="space-y-1">
                                <label
                                    class="block text-xs font-semibold tracking-widest uppercase text-[#595c5e] ml-1">
                                    Địa chỉ Email
                                </label>
                                <div class="relative group">
                                    <div
                                        class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-[#747779] group-focus-within:text-[#00628c] transition-colors">
                                        <span class="material-symbols-outlined text-xl">mail</span>
                                    </div>
                                    <input v-model="forgotForm.email" type="email" placeholder="example@company.com"
                                        class="w-full pl-12 pr-6 py-3 bg-white border-none rounded-full ring-1 ring-[#abadaf]/30 focus:ring-4 focus:ring-[#57baf6]/30 transition-all outline-none text-[#2c2f31]" />
                                </div>
                                <p v-if="forgotForm.errors.email" class="text-red-500 text-xs ml-4">
                                    {{ forgotForm.errors.email }}
                                </p>
                            </div>

                            <button type="submit" :disabled="forgotForm.processing"
                                :class="{ 'opacity-50 cursor-not-allowed': forgotForm.processing }"
                                class="w-full bg-gradient-to-r from-[#00628c] to-[#57baf6] text-white font-bold py-3 px-6 rounded-full hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 shadow-lg">
                                <span>{{ forgotForm.processing ? 'Đang gửi...' : 'Gửi mã xác minh' }}</span>
                                <span class="material-symbols-outlined text-xl">arrow_forward</span>
                            </button>

                            <button type="button" @click="activeTab = 'login'"
                                class="w-full text-xs text-[#595c5e] hover:text-[#00628c] transition-colors text-center">
                                ← Quay lại đăng nhập
                            </button>
                        </form>

                        <!-- Social Login -->
                        <div class="mt-6">
                            <div class="relative flex items-center mb-4">
                                <div class="flex-grow border-t border-[#abadaf]/30"></div>
                                <span
                                    class="flex-shrink mx-4 text-xs font-bold text-[#abadaf] uppercase tracking-widest">
                                    Hoặc tiếp tục với
                                </span>
                                <div class="flex-grow border-t border-[#abadaf]/30"></div>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <a :href="route('google.login')"
                                    class="flex items-center justify-center gap-2 py-3 px-4 bg-white border border-[#abadaf]/20 rounded-full hover:bg-[#e5e9eb] transition-all cursor-pointer">
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
                                    <span class="text-sm font-semibold">Đăng nhập bằng Google</span>
                                </a>
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