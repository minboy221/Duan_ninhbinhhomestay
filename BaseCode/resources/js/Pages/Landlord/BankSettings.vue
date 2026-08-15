<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    userData: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    bank_name: props.userData?.bank_name || '',
    bank_account_no: props.userData?.bank_account_no || '',
    bank_account_name: props.userData?.bank_account_name || '',
})

// Danh sách các ngân hàng phổ biến tại Việt Nam
const popularBanks = [
    { code: 'MBBank', name: 'MBBank (Quân Đội)', logo: 'https://api.vietqr.io/img/MB.png' },
    { code: 'Vietcombank', name: 'Vietcombank', logo: 'https://api.vietqr.io/img/VCB.png' },
    { code: 'Techcombank', name: 'Techcombank', logo: 'https://api.vietqr.io/img/TCB.png' },
    { code: 'BIDV', name: 'BIDV', logo: 'https://api.vietqr.io/img/BIDV.png' },
    { code: 'Agribank', name: 'Agribank', logo: 'https://api.vietqr.io/img/VBA.png' },
    { code: 'VietinBank', name: 'VietinBank', logo: 'https://api.vietqr.io/img/CTG.png' },
    { code: 'TPBank', name: 'TPBank', logo: 'https://api.vietqr.io/img/TPB.png' },
    { code: 'VPBank', name: 'VPBank', logo: 'https://api.vietqr.io/img/VPB.png' },
    { code: 'ACB', name: 'ACB', logo: 'https://api.vietqr.io/img/ACB.png' },
]

const selectBank = (bank) => {
    form.bank_name = bank.code
}

// Chuẩn hóa tên chủ tài khoản viết hoa không dấu
const handleAccountNameInput = (e) => {
    let val = e.target.value;
    val = val.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D');
    form.bank_account_name = val.toUpperCase();
}

// Ảnh QR VietQR trực tiếp xem trước
const qrPreviewUrl = computed(() => {
    if (!form.bank_name || !form.bank_account_no) return null;
    const bank = encodeURIComponent(form.bank_name.trim());
    const acc = encodeURIComponent(form.bank_account_no.trim());
    const name = encodeURIComponent(form.bank_account_name.trim() || 'MA HOA DON');
    return `https://img.vietqr.io/image/${bank}-${acc}-compact2.png?amount=1000000&addInfo=DEMO%20THANH%20TOAN&accountName=${name}`;
});

const submit = () => {
    form.post(route('landlord.bank-settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Toast thông báo đã xử lý trong Layout
        }
    })
}
</script>

<template>
    <LandlordLayout>
        <template #header-title>
            <h1 class="ll-header-title">Tài Khoản Ngân Hàng</h1>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-emerald-800 to-teal-700 rounded-2xl p-6 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="space-y-2 text-center md:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-emerald-200 text-xs font-semibold backdrop-blur-md">
                        <i class="bi bi-qr-code-scan"></i> VietQR Động Miễn Phí
                    </div>
                    <h2 class="text-2xl font-black tracking-tight">Cấu Hình Ngân Hàng Nhận Tiền Phòng</h2>
                    <p class="text-emerald-100/90 text-sm max-w-xl">
                        Thông tin tài khoản ngân hàng này sẽ được dùng để tự động sinh mã VietQR trên Hóa đơn thanh toán hàng tháng cho khách thuê trọ.
                    </p>
                </div>
                <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center text-3xl text-emerald-300 flex-shrink-0 border border-white/20 backdrop-blur-md">
                    <i class="bi bi-bank"></i>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left Column: Form -->
                <div class="lg:col-span-7 bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-slate-100 space-y-6">
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-4">
                        <i class="bi bi-credit-card-2-front text-emerald-600 text-xl"></i>
                        Thông Tin Ngân Hàng
                    </h3>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Ngân hàng chọn nhanh -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Chọn nhanh Ngân Hàng
                            </label>
                            <div class="grid grid-cols-3 sm:grid-cols-3 gap-2">
                                <button v-for="b in popularBanks" :key="b.code" type="button"
                                    @click="selectBank(b)"
                                    :class="[
                                        'flex items-center gap-2 p-2 rounded-xl border text-xs font-bold transition-all text-left',
                                        form.bank_name === b.code
                                            ? 'border-emerald-500 bg-emerald-50 text-emerald-700 ring-2 ring-emerald-500/20'
                                            : 'border-slate-200 hover:border-slate-300 bg-slate-50/50 text-slate-600'
                                    ]">
                                    <span class="w-2 h-2 rounded-full" :class="form.bank_name === b.code ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                    <span class="truncate">{{ b.code }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Tên ngân hàng nhập trực tiếp -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Tên Ngân Hàng <span class="text-rose-500">*</span>
                            </label>
                            <input v-model="form.bank_name" type="text"
                                placeholder="Ví dụ: MBBank, Vietcombank, Techcombank..."
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm font-semibold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all outline-none"
                                :class="{ 'border-rose-400': form.errors.bank_name }" />
                            <p v-if="form.errors.bank_name" class="text-xs text-rose-500 font-medium">{{ form.errors.bank_name }}</p>
                        </div>

                        <!-- Số tài khoản -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Số Tài Khoản Ngân Hàng <span class="text-rose-500">*</span>
                            </label>
                            <input v-model="form.bank_account_no" type="text"
                                placeholder="Ví dụ: 0912345678"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm font-semibold tracking-wide focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all outline-none"
                                :class="{ 'border-rose-400': form.errors.bank_account_no }" />
                            <p v-if="form.errors.bank_account_no" class="text-xs text-rose-500 font-medium">{{ form.errors.bank_account_no }}</p>
                        </div>

                        <!-- Tên chủ tài khoản -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">
                                Tên Chủ Tài Khoản (Viết hoa không dấu) <span class="text-rose-500">*</span>
                            </label>
                            <input :value="form.bank_account_name" @input="handleAccountNameInput" type="text"
                                placeholder="Ví dụ: NGUYEN VAN A"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm font-bold uppercase tracking-wider focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all outline-none"
                                :class="{ 'border-rose-400': form.errors.bank_account_name }" />
                            <p v-if="form.errors.bank_account_name" class="text-xs text-rose-500 font-medium">{{ form.errors.bank_account_name }}</p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-3">
                            <button type="submit" :disabled="form.processing"
                                class="w-full py-3.5 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                                <i v-if="form.processing" class="bi bi-arrow-repeat animate-spin text-lg"></i>
                                <i v-else class="bi bi-check-circle-fill text-lg"></i>
                                <span>{{ form.processing ? 'Đang Lưu...' : 'Lưu Thông Tin Ngân Hàng' }}</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Live VietQR Preview -->
                <div class="lg:col-span-5 bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-5">
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-4">
                        <i class="bi bi-eye text-teal-600 text-xl"></i>
                        Mẫu Mã VietQR Khách Xem
                    </h3>

                    <div v-if="qrPreviewUrl" class="flex flex-col items-center justify-center p-6 bg-slate-50/70 border border-slate-100 rounded-2xl space-y-4">
                        <div class="bg-white p-3 rounded-xl shadow-md border border-slate-100 text-center">
                            <img :src="qrPreviewUrl" alt="VietQR Preview" class="w-56 h-56 object-contain rounded-lg mx-auto" />
                            <p class="text-[10px] text-slate-400 mt-2 font-medium">Mã QR hiển thị trực quan cho khách quét</p>
                        </div>

                        <div class="w-full space-y-2 text-xs">
                            <div class="flex justify-between items-center py-1.5 border-b border-slate-200/60">
                                <span class="text-slate-500 font-medium">Ngân hàng:</span>
                                <span class="font-bold text-slate-800">{{ form.bank_name }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1.5 border-b border-slate-200/60">
                                <span class="text-slate-500 font-medium">Số tài khoản:</span>
                                <span class="font-mono font-bold text-emerald-700">{{ form.bank_account_no }}</span>
                            </div>
                            <div class="flex justify-between items-center py-1.5">
                                <span class="text-slate-500 font-medium">Chủ tài khoản:</span>
                                <span class="font-bold text-slate-800 uppercase">{{ form.bank_account_name || 'CHƯA NHẬP' }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center p-8 bg-slate-50/50 border border-dashed border-slate-200 rounded-2xl text-center space-y-3 text-slate-400">
                        <i class="bi bi-qr-code text-4xl text-slate-300"></i>
                        <p class="text-xs font-semibold">Vui lòng điền đầy đủ Tên ngân hàng và Số tài khoản để xem trước mã VietQR.</p>
                    </div>

                    <div class="bg-emerald-50/60 border border-emerald-100 rounded-xl p-4 text-xs space-y-1 text-emerald-800">
                        <p class="font-bold flex items-center gap-1.5">
                            <i class="bi bi-shield-check text-emerald-600 text-sm"></i>
                            Tự động hoá 100%
                        </p>
                        <p class="text-emerald-700/90 leading-relaxed">
                            Khi khách thuê mở hóa đơn và quét mã QR này, ứng dụng Ngân hàng sẽ tự điền sẵn **Số tiền** & **Nội dung mã hóa đơn**, giúp giảm 100% sai sót chuyển khoản nhầm.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>
