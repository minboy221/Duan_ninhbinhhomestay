<script setup>
import { router } from "@inertiajs/vue3";
import { defineProps, defineEmits, ref } from "vue";

const props = defineProps({
    form: Object,
});

const emit = defineEmits(["next"]);

// Hàm nén ảnh tự động trên điện thoại trước khi upload (giúp nén ảnh 10-15MB xuống ~500KB)
const compressImage = (file, maxWidth = 1920, maxHeight = 1920, quality = 0.8) => {
    return new Promise((resolve) => {
        if (!file || !file.type.startsWith('image/') || file.size < 500 * 1024) {
            return resolve(file);
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;

                if (width > maxWidth || height > maxHeight) {
                    if (width > height) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    } else {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        if (blob) {
                            const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, ".jpg"), {
                                type: 'image/jpeg',
                                lastModified: Date.now(),
                            });
                            resolve(compressedFile);
                        } else {
                            resolve(file);
                        }
                    },
                    'image/jpeg',
                    quality
                );
            };
            img.onerror = () => resolve(file);
        };
        reader.onerror = () => resolve(file);
    });
};

const handleFile = async (e, field) => {
    const file = e.target.files[0];

    if (file) {
        const compressedFile = await compressImage(file);
        props.form[field] = compressedFile;
        props.form[`${field}_preview`] = URL.createObjectURL(compressedFile);
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
                    Xác minh nhân thân
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
                            Số Căn Cước Công Dân <span class="text-error font-bold">*</span>
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
                        Hình Ảnh CCCD <span class="text-error font-bold">*</span>
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
                                <span class="material-symbols-outlined text-4xl text-primary">add_a_photo</span>
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
                                <span class="material-symbols-outlined text-4xl text-primary">add_a_photo</span>
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
