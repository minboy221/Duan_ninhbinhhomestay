<script setup>
import { router } from "@inertiajs/vue3";
import { defineProps, defineEmits, ref } from "vue";

const props = defineProps({
    form: Object,
});

const emit = defineEmits(["next"]);

const handleFile = (e, field) => {
    const file = e.target.files[0];

    if (file) {
        props.form[field] = file;
        props.form[`${field}_preview`] = URL.createObjectURL(file);
    }
};

const errors = ref({});

const validate = () => {
    errors.value = {};
    if (!props.form.id_card_number) {
        errors.value.id_card_number = "Vui lòng nhập số CCCD";
    } else if (!/^\d{12}$/.test(props.form.id_card_number)) {
        errors.value.id_card_number = "Số CCCD phải gồm 12 chữ số";
    }

    if (!props.form.phone) {
        errors.value.phone = "Vui lòng nhập số điện thoại";
    } else if (!/^\d{10}$/.test(props.form.phone)) {
        errors.value.phone = "Số điện thoại không hợp lệ";
    }

    if (!props.form.id_card_front) {
        errors.value.id_card_front = "Vui lòng tải lên mặt trước CCCD";
    }
    if (!props.form.id_card_back) {
        errors.value.id_card_back = "Vui lòng tải lên mặt sau CCCD";
    }

    return Object.keys(errors.value).length === 0;
};

const nextStep = () => {
    if (validate()) {
        emit("next");
    }
};
</script>

<template>
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-3xl p-8 border border-slate-100 shadow-sm space-y-8">
            <!-- Header -->
            <header class="text-center space-y-2">
                <h1 class="text-lg font-bold text-slate-800">Xác minh nhân thân (KYC)</h1>
                <p class="text-xs text-slate-400">Vui lòng tải lên hình ảnh căn cước công dân chính xác để xác thực tài khoản chủ trọ</p>
            </header>

            <form class="space-y-6" @submit.prevent="nextStep">
                <!-- Input CCCD -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Số căn cước công dân (CCCD) <span class="text-rose-500">*</span></label>
                    <input
                        v-model="form.id_card_number"
                        type="text"
                        placeholder="Nhập 12 số CCCD của bạn"
                        class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                        :class="{'border-rose-400': errors.id_card_number}"
                    />
                    <p v-if="errors.id_card_number" class="text-rose-500 text-[10px] font-bold">{{ errors.id_card_number }}</p>
                </div>

                <!-- Phone number -->
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Số điện thoại liên hệ <span class="text-rose-500">*</span></label>
                    <input
                        v-model="form.phone"
                        type="text"
                        placeholder="Nhập số điện thoại đăng ký"
                        class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                        :class="{'border-rose-400': errors.phone}"
                    />
                    <p v-if="errors.phone" class="text-rose-500 text-[10px] font-bold">{{ errors.phone }}</p>
                </div>

                <!-- ID Card Images -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="bi bi-cloud-arrow-up text-emerald-500"></i>
                        <span>Hình ảnh tài liệu định danh</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Front Face -->
                        <label class="relative aspect-[4/3] rounded-2xl border-2 border-dashed border-slate-200 hover:border-emerald-500 bg-slate-50/50 flex flex-col items-center justify-center gap-1.5 cursor-pointer overflow-hidden transition-all">
                            <template v-if="form.id_card_front_preview">
                                <img :src="form.id_card_front_preview" class="absolute inset-0 w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/25"></div>
                                <span class="absolute bottom-3 left-3 bg-white/95 px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-600">Mặt trước CCCD</span>
                            </template>
                            <template v-else>
                                <i class="bi bi-camera text-slate-400 text-2xl"></i>
                                <span class="text-[10px] font-bold text-slate-500">Mặt trước CCCD</span>
                                <span class="text-[9px] text-slate-400">Click để chọn ảnh</span>
                            </template>
                            <input type="file" accept="image/*" class="hidden" @change="(e) => handleFile(e, 'id_card_front')" />
                        </label>

                        <!-- Back Face -->
                        <label class="relative aspect-[4/3] rounded-2xl border-2 border-dashed border-slate-200 hover:border-emerald-500 bg-slate-50/50 flex flex-col items-center justify-center gap-1.5 cursor-pointer overflow-hidden transition-all">
                            <template v-if="form.id_card_back_preview">
                                <img :src="form.id_card_back_preview" class="absolute inset-0 w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/25"></div>
                                <span class="absolute bottom-3 left-3 bg-white/95 px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-600">Mặt sau CCCD</span>
                            </template>
                            <template v-else>
                                <i class="bi bi-camera text-slate-400 text-2xl"></i>
                                <span class="text-[10px] font-bold text-slate-500">Mặt sau CCCD</span>
                                <span class="text-[9px] text-slate-400">Click để chọn ảnh</span>
                            </template>
                            <input type="file" accept="image/*" class="hidden" @change="(e) => handleFile(e, 'id_card_back')" />
                        </label>
                    </div>
                    <p v-if="errors.id_card_front" class="text-rose-500 text-[10px] font-bold text-center">{{ errors.id_card_front }}</p>
                    <p v-if="errors.id_card_back && !errors.id_card_front" class="text-rose-500 text-[10px] font-bold text-center">{{ errors.id_card_back }}</p>
                </div>

                <!-- Footer Audit log info -->
                <div class="p-3.5 bg-slate-50 border border-slate-100 rounded-2xl flex items-center gap-3 text-xs text-slate-600 font-semibold">
                    <i class="bi bi-shield-check text-emerald-500 text-base"></i>
                    <p>Thông tin KYC sẽ được mã hóa và bảo mật 100%. Ban quản trị sẽ kiểm duyệt trong vòng 24h.</p>
                </div>

                <!-- Buttons navigation -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-4">
                    <button
                        type="button"
                        @click="router.visit('/')"
                        class="px-4 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5"
                    >
                        <i class="bi bi-arrow-left"></i> Quay lại trang chủ
                    </button>

                    <button
                        type="submit"
                        class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors flex items-center gap-1.5"
                    >
                        Tiếp theo <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
