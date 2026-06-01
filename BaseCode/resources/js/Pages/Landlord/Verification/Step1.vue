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

        // preview ảnh
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
    } else if (!/^\d{10,11}$/.test(props.form.phone)) {
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
    <div class="max-w-5xl mx-auto px-4 py-10">
        <!-- STEP PROGRESS -->
        <StepProgress :step="1" />

        <!-- FORM CARD -->
        <div
            class="glass-panel rounded-[28px] p-8 md:p-12 shadow-2xl shadow-sky-100 bg-white mt32"
        >
            <!-- Header -->
            <header class="mb-10 text-center">
                <h1
                    class="text-3xl md:text-4xl font-extrabold text-slate-800 tracking-tight mb-2"
                >
                    Xác minh nhân thân (KYC)
                </h1>

                <p class="text-slate-500 font-light text-lg">
                    Để đảm bảo an toàn cho cộng đồng, vui lòng cung cấp thông
                    tin chính xác theo CCCD.
                </p>
            </header>

            <form class="space-y-10" @submit.prevent="nextStep">
                <!-- Input CCCD -->
                <div class="space-y-2">
                    <label
                        class="font-semibold text-slate-500 tracking-wider block uppercase text-xs"
                    >
                        Số CCCD
                    </label>

                    <input
                        v-model="form.id_card_number"
                        type="text"
                        placeholder="Nhập 12 số CCCD"
                        class="w-full h-14 px-6 rounded-2xl border border-slate-200 bg-slate-50 focus:ring-4 focus:ring-sky-100 focus:border-sky-500 focus:outline-none transition-all text-slate-700 font-medium"
                        :class="{'border-red-500': errors.id_card_number}"
                    />
                    <p v-if="errors.id_card_number" class="text-red-500 text-sm mt-1">{{ errors.id_card_number }}</p>
                </div>
                <div class="space-y-2">
                    <label
                        class="font-semibold text-slate-500 tracking-wider block uppercase text-xs"
                    >
                        Số Điện Thoại
                    </label>

                    <input
                        v-model="form.phone"
                        type="text"
                        placeholder="Nhập số điện thoại"
                        class="w-full h-14 px-6 rounded-2xl border border-slate-200 bg-slate-50 focus:ring-4 focus:ring-sky-100 focus:border-sky-500 focus:outline-none transition-all text-slate-700 font-medium"
                        :class="{'border-red-500': errors.phone}"
                    />
                    <p v-if="errors.phone" class="text-red-500 text-sm mt-1">{{ errors.phone }}</p>
                </div>

                <!-- Upload -->
                <div class="space-y-6">
                    <h3
                        class="text-xl font-bold text-slate-800 flex items-center gap-3"
                    >
                        <span class="material-symbols-outlined text-sky-600">
                            cloud_upload
                        </span>

                        Tài liệu định danh
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- FRONT -->
                        <label
                            class="group relative aspect-[4/3] rounded-[24px] border-2 border-dashed border-slate-300 hover:border-sky-500 transition-all cursor-pointer flex flex-col items-center justify-center gap-3 bg-slate-50 overflow-hidden"
                        >
                            <!-- preview -->
                            <template v-if="form.id_card_front_preview">
                                <img
                                    :src="form.id_card_front_preview"
                                    class="absolute inset-0 w-full h-full object-cover"
                                />

                                <div class="absolute inset-0 bg-black/20"></div>

                                <span
                                    class="absolute bottom-4 left-4 bg-white/90 px-4 py-2 rounded-xl text-sm font-semibold"
                                >
                                    Mặt trước CCCD
                                </span>
                            </template>

                            <!-- placeholder -->
                            <template v-else>
                                <span
                                    class="material-symbols-outlined text-5xl text-sky-600"
                                >
                                    add_a_photo
                                </span>

                                <span
                                    class="text-sm font-semibold text-slate-500"
                                >
                                    Mặt trước CCCD
                                </span>

                                <span class="text-xs text-slate-400">
                                    Click để tải ảnh lên
                                </span>
                            </template>

                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="(e) => handleFile(e, 'id_card_front')"
                            />
                        </label>

                        <!-- BACK -->
                        <label
                            class="group relative aspect-[4/3] rounded-[24px] border-2 border-dashed border-slate-300 hover:border-sky-500 transition-all cursor-pointer flex flex-col items-center justify-center gap-3 bg-slate-50 overflow-hidden"
                        >
                            <!-- preview -->
                            <template v-if="form.id_card_back_preview">
                                <img
                                    :src="form.id_card_back_preview"
                                    class="absolute inset-0 w-full h-full object-cover"
                                />

                                <div class="absolute inset-0 bg-black/20"></div>

                                <span
                                    class="absolute bottom-4 left-4 bg-white/90 px-4 py-2 rounded-xl text-sm font-semibold"
                                >
                                    Mặt sau CCCD
                                </span>
                            </template>

                            <!-- placeholder -->
                            <template v-else>
                                <span
                                    class="material-symbols-outlined text-5xl text-sky-600"
                                >
                                    add_a_photo
                                </span>

                                <span
                                    class="text-sm font-semibold text-slate-500"
                                >
                                    Mặt sau CCCD
                                </span>

                                <span class="text-xs text-slate-400">
                                    Click để tải ảnh lên
                                </span>
                            </template>

                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="(e) => handleFile(e, 'id_card_back')"
                            />
                        </label>
                    </div>
                    <p v-if="errors.id_card_front" class="text-red-500 text-sm text-center">{{ errors.id_card_front }}</p>
                    <p v-if="errors.id_card_back && !errors.id_card_front" class="text-red-500 text-sm text-center">{{ errors.id_card_back }}</p>
                </div>

                <!-- Footer -->
                <div
                    class="mt-10 pt-8 border-t border-slate-200 flex flex-col-reverse md:flex-row items-center justify-between gap-4"
                >
                    <!-- Left note -->
                    <div
                        class="flex items-center gap-3 bg-slate-100 px-5 py-4 rounded-2xl text-slate-600"
                    >
                        <span class="material-symbols-outlined text-sky-600">
                            shield
                        </span>

                        <p class="text-sm font-medium">
                            Thông tin của bạn sẽ được Admin kiểm duyệt trong
                            vòng 24h
                        </p>
                    </div>

                    <!-- Right buttons -->
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <!-- Back Home -->
                        <button
                            type="button"
                            @click="router.visit('/')"
                            class="h-14 px-7 rounded-full border-2 border-sky-700 text-sky-700 font-semibold hover:bg-sky-50 transition-all flex items-center justify-center gap-2 whitespace-nowrap"
                        >
                            <span class="material-symbols-outlined text-[20px]">
                                arrow_back
                            </span>
                            Quay lại trang chủ
                        </button>

                        <!-- Next -->
                        <button
                            type="submit"
                            class="h-14 px-9 rounded-full bg-[#005F87] text-white font-bold text-lg hover:scale-[1.02] transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2 whitespace-nowrap"
                        >
                            Tiếp theo
                            <span class="material-symbols-outlined">
                                arrow_forward
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
