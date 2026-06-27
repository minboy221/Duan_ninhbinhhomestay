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
        <!-- KYC Form Card -->
        <div class="glass-panel rounded-xl p-8 md:p-12 shadow-2xl shadow-primary/5">
            <header class="mb-10 text-center">
                <h1 class="text-3xl md:text-4xl font-extrabold text-on-surface tracking-tight mb-2">
                    Xác minh nhân thân (KYC)
                </h1>
                <p class="text-on-surface-variant font-light text-lg">
                    Để đảm bảo an toàn cho cộng đồng, vui lòng cung cấp thông tin chính xác theo CCCD.
                </p>
            </header>

            <form class="space-y-10" @submit.prevent="nextStep">
                <!-- Text Inputs Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label
                            class="label-md font-semibold text-on-surface-variant tracking-wider block uppercase text-xs"
                            for="phone"
                        >
                            Số điện thoại liên hệ <span class="text-error font-bold">*</span>
                        </label>
                        <input
                            v-model="form.phone"
                            class="w-full h-14 px-6 rounded-lg bg-surface-container-lowest border-none focus:ring-4 focus:ring-primary-container/30 transition-all text-on-surface placeholder:text-outline-variant font-medium"
                            :class="{'ring-2 ring-error/50': errors.phone}"
                            id="phone"
                            placeholder="0987xxxxxx"
                            type="text"
                        />
                        <p v-if="errors.phone" class="text-error text-xs font-bold">{{ errors.phone }}</p>
                    </div>
                    <div class="space-y-2">
                        <label
                            class="label-md font-semibold text-on-surface-variant tracking-wider block uppercase text-xs"
                            for="id_card"
                        >
                            ID Card Number (CCCD) <span class="text-error font-bold">*</span>
                        </label>
                        <input
                            v-model="form.id_card_number"
                            class="w-full h-14 px-6 rounded-lg bg-surface-container-lowest border-none focus:ring-4 focus:ring-primary-container/30 transition-all text-on-surface placeholder:text-outline-variant font-medium appearance-none"
                            :class="{'ring-2 ring-error/50': errors.id_card_number}"
                            id="id_card"
                            placeholder="12 chữ số"
                            type="text"
                        />
                        <p v-if="errors.id_card_number" class="text-error text-xs font-bold">{{ errors.id_card_number }}</p>
                    </div>
                </div>

                <!-- Upload Zones -->
                <div class="space-y-6">
                    <h3 class="text-xl font-bold text-on-surface flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">cloud_upload</span>
                        Tài liệu định danh <span class="text-error font-bold">*</span>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Upload Item 1 -->
                        <label
                            class="group relative aspect-[4/3] rounded-lg border-2 border-dashed border-outline-variant/30 hover:border-primary/50 transition-all cursor-pointer flex flex-col items-center justify-center gap-3 bg-surface-container-low/50 overflow-hidden"
                        >
                            <template v-if="form.id_card_front_preview">
                                <img :src="form.id_card_front_preview" class="absolute inset-0 w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/10"></div>
                                <span class="absolute bottom-3 left-3 bg-white/90 px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-700">Mặt trước CCCD</span>
                            </template>
                            <template v-else>
                                <img alt="Mặt trước CCCD"
                                    class="absolute inset-0 w-full h-full object-cover opacity-20 grayscale group-hover:opacity-40 transition-opacity"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuDuZ7RH4Turfqy8ynC3jD4q142yyHzpl-hNo0eMUiPEeV3alukUnN2Ne3V_rPn-JL8h2nRXHUgrPlIlo3IrzxulnJYCJfywuGdaPlnz0wLgi2ypIc0tUkPppY29qnVQ3RzVCi8zNt7XgBOMDOjinkRySdC7Nnzs-rRfQGb4RYMR3MGrAQPsA1h2icBL7ZxvfxcupGsFnIBe67Om-gxyOHrQbScoA1s958L4As9MNbSiXRRPRD2SRlqBnjcYsMM9kvHU74H061j6UIjO" />
                                <span class="material-symbols-outlined text-3xl text-primary">add_a_photo</span>
                                <span class="text-sm font-semibold text-on-surface-variant">Mặt trước CCCD</span>
                            </template>
                            <input type="file" accept="image/*" class="hidden" @change="(e) => handleFile(e, 'id_card_front')" />
                        </label>

                        <!-- Upload Item 2 -->
                        <label
                            class="group relative aspect-[4/3] rounded-lg border-2 border-dashed border-outline-variant/30 hover:border-primary/50 transition-all cursor-pointer flex flex-col items-center justify-center gap-3 bg-surface-container-low/50 overflow-hidden"
                        >
                            <template v-if="form.id_card_back_preview">
                                <img :src="form.id_card_back_preview" class="absolute inset-0 w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/10"></div>
                                <span class="absolute bottom-3 left-3 bg-white/90 px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-700">Mặt sau CCCD</span>
                            </template>
                            <template v-else>
                                <img alt="Mặt sau CCCD"
                                    class="absolute inset-0 w-full h-full object-cover opacity-20 grayscale group-hover:opacity-40 transition-opacity"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuAZdP_qQf6ViGw1Un7VN9Er_efiEjrv6CkifLXyGSiDPn6_YonFJvA0wGD-qBowzr0SiAtJze-FyrIMQxzdGHe_JoN9T86C6SNPpzpULiHgRK4pZosur8pT5O4Ht_Vm7vs0_iIxtHQrPhNjimgPJV760nLZYr_Y6nbOLjHcBgOjX8TcOGd0-yN30EByM3HEqkOC5uP9WcP5zt4eYDeg9vV6ZCbo-ajCFOuiCUCGxab4MSVjiM3vAwl0iuizCQRsYQApZnws6vWJoaBc" />
                                <span class="material-symbols-outlined text-3xl text-primary">add_a_photo</span>
                                <span class="text-sm font-semibold text-on-surface-variant">Mặt sau CCCD</span>
                            </template>
                            <input type="file" accept="image/*" class="hidden" @change="(e) => handleFile(e, 'id_card_back')" />
                        </label>
                    </div>
                    <p v-if="errors.id_card_front" class="text-error text-xs font-bold text-center mt-2">{{ errors.id_card_front }}</p>
                    <p v-if="errors.id_card_back && !errors.id_card_front" class="text-error text-xs font-bold text-center mt-2">{{ errors.id_card_back }}</p>
                </div>

                <!-- Footer Info & Action -->
                <div
                    class="pt-8 border-t border-outline-variant/10 flex flex-col md:flex-row items-center justify-between gap-6"
                >
                    <div
                        class="flex items-center gap-3 text-on-surface-variant bg-surface-container-low px-5 py-3 rounded-full w-full md:w-auto"
                    >
                        <span class="material-symbols-outlined text-primary text-xl">verified_user</span>
                        <p class="text-sm font-medium">Thông tin của bạn sẽ được Admin kiểm duyệt trong vòng 24h</p>
                    </div>
                    <div class="flex flex-col-reverse md:flex-row items-center gap-4 w-full md:w-auto justify-end">
                        <button
                            type="button"
                            @click="router.visit('/')"
                            class="w-full md:w-44 h-14 rounded-full border-2 border-primary text-primary font-bold hover:bg-primary-container/10 transition-all flex items-center justify-center gap-2 whitespace-nowrap flex-shrink-0"
                        >
                            <span class="material-symbols-outlined">arrow_back</span>
                            Quay lại
                        </button>
                        <button
                            class="w-full md:w-44 h-14 rounded-full bg-gradient-to-r from-primary to-primary-container text-on-primary font-bold text-lg hover:scale-[1.02] transition-all shadow-xl shadow-primary/20 flex items-center justify-center gap-2 whitespace-nowrap flex-shrink-0"
                            type="submit"
                        >
                            Tiếp theo
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.glass-panel {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    border: 1.5px solid rgba(217, 221, 224, 0.3);
}
</style>
