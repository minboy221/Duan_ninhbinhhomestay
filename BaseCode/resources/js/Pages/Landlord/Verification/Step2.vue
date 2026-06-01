<script setup>
import { defineProps, defineEmits, ref } from "vue";

const props = defineProps({
    form: Object,
});

const emit = defineEmits(["next", "prev"]);

// upload nhiều file
const handleMultipleFiles = (e, field) => {
    const files = Array.from(e.target.files);

    props.form[field] = files;

    // chỉ preview ảnh
    props.form[`${field}_preview`] = files
        .filter((file) => file.type.startsWith("image/"))
        .map((file) => URL.createObjectURL(file));
};

const errors = ref({});

const validate = () => {
    errors.value = {};
    if (!props.form.property_name) {
        errors.value.property_name = "Vui lòng nhập tên nhà trọ";
    }
    if (!props.form.district) {
        errors.value.district = "Vui lòng nhập quận/huyện";
    }
    if (!props.form.address_detail) {
        errors.value.address_detail = "Vui lòng nhập địa chỉ chi tiết";
    }
    if (
        !props.form.contract_images ||
        props.form.contract_images.length === 0
    ) {
        errors.value.contract_images = "Vui lòng tải lên hồ sơ pháp lý";
    }
    if (!props.form.room_images || props.form.room_images.length === 0) {
        errors.value.room_images = "Vui lòng tải lên hình ảnh không gian";
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
    <div class="space-y-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1
                class="text-4xl font-extrabold text-slate-800 tracking-tight mb-3"
            >
                Thông tin Cơ sở lưu trú
            </h1>

            <p class="text-slate-500 text-lg">
                Vui lòng cung cấp chi tiết về homestay của bạn để chúng tôi có
                thể xác minh.
            </p>
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- LEFT -->
            <div class="lg:col-span-7 space-y-8">
                <!-- Basic Info -->
                <section
                    class="bg-white rounded-[28px] shadow-lg border border-slate-100 p-8"
                >
                    <h2
                        class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2"
                    >
                        <span class="material-symbols-outlined text-sky-600">
                            home
                        </span>

                        Thông tin cơ bản
                    </h2>

                    <div class="space-y-6">
                        <!-- Tên nhà trọ -->
                        <div>
                            <label
                                class="block text-sm font-semibold text-slate-700 mb-2 uppercase"
                            >
                                Tên nhà trọ
                            </label>

                            <input
                                v-model="form.property_name"
                                type="text"
                                placeholder="Ví dụ: Ninh Bình Calm Homestay"
                                class="w-full px-6 py-4 rounded-2xl bg-slate-50 border focus:outline-none focus:ring-4 focus:ring-sky-100"
                                :class="
                                    errors.property_name
                                        ? 'border-red-500'
                                        : 'border-slate-200'
                                "
                            />
                            <p
                                v-if="errors.property_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.property_name }}
                            </p>
                        </div>

                        <!-- District + Address -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-semibold text-slate-700 mb-2 uppercase"
                                >
                                    Quận / Huyện
                                </label>

                                <input
                                    v-model="form.district"
                                    type="text"
                                    placeholder="Hoa Lư"
                                    class="w-full px-6 py-4 rounded-2xl bg-slate-50 border focus:outline-none focus:ring-4 focus:ring-sky-100"
                                    :class="
                                        errors.district
                                            ? 'border-red-500'
                                            : 'border-slate-200'
                                    "
                                />
                                <p
                                    v-if="errors.district"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.district }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-slate-700 mb-2 uppercase"
                                >
                                    Địa chỉ chi tiết
                                </label>

                                <input
                                    v-model="form.address_detail"
                                    type="text"
                                    placeholder="Số nhà, tên đường..."
                                    class="w-full px-6 py-4 rounded-2xl bg-slate-50 border focus:outline-none focus:ring-4 focus:ring-sky-100"
                                    :class="
                                        errors.address_detail
                                            ? 'border-red-500'
                                            : 'border-slate-200'
                                    "
                                />
                                <p
                                    v-if="errors.address_detail"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.address_detail }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Hồ sơ pháp lý -->
                <section
                    class="bg-white rounded-[28px] shadow-lg border border-slate-100 p-8"
                >
                    <h2
                        class="text-xl font-bold text-slate-800 mb-3 flex items-center gap-2"
                    >
                        <span class="material-symbols-outlined text-sky-600">
                            description
                        </span>

                        Hồ sơ pháp lý
                    </h2>

                    <p class="text-sm text-slate-500 mb-6">
                        Tải lên ảnh hợp đồng hoặc giấy tờ liên quan.
                    </p>

                    <!-- Upload -->
                    <label
                        class="block border-2 border-dashed border-slate-300 rounded-[28px] overflow-hidden cursor-pointer hover:border-sky-500 transition-all bg-slate-50"
                    >
                        <!-- Chưa upload -->
                        <div
                            v-if="!form.contract_images_preview?.length"
                            class="p-10 text-center"
                        >
                            <span
                                class="material-symbols-outlined text-5xl text-slate-400"
                            >
                                cloud_upload
                            </span>

                            <p class="font-medium text-slate-700 mt-3">
                                Chọn nhiều ảnh hợp đồng
                            </p>

                            <p class="text-xs text-slate-400 mt-1">
                                JPG, PNG, PDF
                            </p>
                        </div>

                        <!-- Đã upload -->
                        <div
                            v-else
                            class="grid grid-cols-2 md:grid-cols-3 gap-3 p-4"
                        >
                            <div
                                v-for="(
                                    image, index
                                ) in form.contract_images_preview"
                                :key="index"
                                class="relative h-36 rounded-2xl overflow-hidden group"
                            >
                                <img
                                    :src="image"
                                    class="w-full h-full object-cover"
                                />

                                <div
                                    class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center"
                                >
                                    <span
                                        class="material-symbols-outlined text-white text-3xl"
                                    >
                                        edit
                                    </span>
                                </div>
                            </div>
                        </div>

                        <input
                            type="file"
                            multiple
                            accept="image/*,.pdf"
                            class="hidden"
                            @change="
                                (e) => handleMultipleFiles(e, 'contract_images')
                            "
                        />
                    </label>
                    <p
                        v-if="errors.contract_images"
                        class="text-red-500 text-sm mt-2 text-center"
                    >
                        {{ errors.contract_images }}
                    </p>
                </section>
            </div>

            <!-- RIGHT -->
            <div class="lg:col-span-5">
                <section
                    class="bg-white rounded-[28px] shadow-lg border border-slate-100 p-8 h-full"
                >
                    <h2
                        class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2"
                    >
                        <span class="material-symbols-outlined text-sky-600">
                            collections
                        </span>

                        Hình ảnh không gian
                    </h2>

                    <!-- Upload -->
                    <label
                        class="block border-2 border-dashed border-slate-300 rounded-[28px] overflow-hidden cursor-pointer hover:border-sky-500 transition-all bg-slate-50"
                    >
                        <!-- chưa upload -->
                        <div
                            v-if="!form.room_images_preview?.length"
                            class="p-8 text-center"
                        >
                            <span
                                class="material-symbols-outlined text-5xl text-slate-400"
                            >
                                add_a_photo
                            </span>

                            <p class="font-medium text-slate-700 mt-3">
                                Tải lên ảnh homestay
                            </p>
                        </div>

                        <!-- đã upload -->
                        <div v-else class="grid grid-cols-2 gap-3 p-4">
                            <div
                                v-for="(
                                    image, index
                                ) in form.room_images_preview"
                                :key="index"
                                class="relative rounded-2xl overflow-hidden group h-40"
                            >
                                <img
                                    :src="image"
                                    class="w-full h-full object-cover"
                                />

                                <div
                                    class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center"
                                >
                                    <span
                                        class="material-symbols-outlined text-white text-3xl"
                                    >
                                        edit
                                    </span>
                                </div>
                            </div>
                        </div>

                        <input
                            type="file"
                            multiple
                            accept="image/*"
                            class="hidden"
                            @change="
                                (e) => handleMultipleFiles(e, 'room_images')
                            "
                        />
                    </label>
                    <p
                        v-if="errors.room_images"
                        class="text-red-500 text-sm mt-2 text-center"
                    >
                        {{ errors.room_images }}
                    </p>
                </section>
            </div>
        </div>

        <!-- Buttons -->
        <div class="mt-12 flex flex-col md:flex-row justify-between gap-4">
            <button
                type="button"
                @click="emit('prev')"
                class="w-full md:w-auto px-10 py-4 rounded-full border-2 border-sky-700 text-sky-700 font-bold hover:bg-sky-50 transition-all flex items-center justify-center gap-2"
            >
                <span class="material-symbols-outlined"> arrow_back </span>
                Quay lại
            </button>

            <button
                type="button"
                @click="nextStep"
                class="w-full md:w-auto px-12 py-4 rounded-full bg-[#005F87] text-white font-bold shadow-lg hover:scale-[1.02] transition-all flex items-center justify-center gap-2"
            >
                Tiếp theo

                <span class="material-symbols-outlined"> arrow_forward </span>
            </button>
        </div>
    </div>
</template>
