<script setup>
import { defineProps, defineEmits, ref } from "vue";

const props = defineProps({
    form: Object,
});

const emit = defineEmits(["next", "prev"]);

const handleMultipleFiles = (e, field) => {
    const files = Array.from(e.target.files);
    props.form[field] = files;
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
    if (!props.form.contract_images || props.form.contract_images.length === 0) {
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
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div class="text-center space-y-2 mb-6">
            <h1 class="text-lg font-bold text-slate-800">Thông tin Cơ sở lưu trú</h1>
            <p class="text-xs text-slate-400">Cung cấp thông tin thực tế và minh chứng pháp lý để đăng ký đại lý/chủ trọ</p>
        </div>

        <!-- Main Form Grid -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Left Info Panel -->
            <div class="md:col-span-7 space-y-6">
                <!-- Basic Info -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-4">
                    <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-50 pb-2">
                        <i class="bi bi-house-door text-emerald-500"></i>
                        <span>Thông tin cơ bản</span>
                    </h2>

                    <div class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 block">Tên cơ sở kinh doanh / Homestay <span class="text-rose-500">*</span></label>
                            <input
                                v-model="form.property_name"
                                type="text"
                                placeholder="Ví dụ: Ninh Bình Calm Homestay"
                                class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                                :class="{'border-rose-400': errors.property_name}"
                            />
                            <p v-if="errors.property_name" class="text-rose-500 text-[10px] font-bold">{{ errors.property_name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 block">Quận / Huyện <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.district"
                                    type="text"
                                    placeholder="Ví dụ: Hoa Lư"
                                    class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                                    :class="{'border-rose-400': errors.district}"
                                />
                                <p v-if="errors.district" class="text-rose-500 text-[10px] font-bold">{{ errors.district }}</p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 block">Địa chỉ chi tiết <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="form.address_detail"
                                    type="text"
                                    placeholder="Số nhà, tên đường..."
                                    class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-xs font-medium outline-none transition-all"
                                    :class="{'border-rose-400': errors.address_detail}"
                                />
                                <p v-if="errors.address_detail" class="text-rose-500 text-[10px] font-bold">{{ errors.address_detail }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legal documents -->
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-4">
                    <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-50 pb-2">
                        <i class="bi bi-file-earmark-text text-emerald-500"></i>
                        <span>Giấy phép kinh doanh / Hợp đồng sở hữu <span class="text-rose-500">*</span></span>
                    </h2>

                    <label class="relative block border-2 border-dashed border-slate-200 hover:border-emerald-500 bg-slate-50/50 rounded-2xl cursor-pointer transition-all overflow-hidden">
                        <div v-if="!form.contract_images_preview?.length" class="p-8 text-center space-y-1.5">
                            <i class="bi bi-cloud-arrow-up text-slate-400 text-2xl"></i>
                            <div class="text-[10px] font-bold text-slate-600">Tải lên tài liệu sở hữu/kinh doanh</div>
                            <div class="text-[9px] text-slate-400">Cho phép định dạng JPG, PNG, PDF</div>
                        </div>
                        <div v-else class="grid grid-cols-3 gap-3 p-4">
                            <div v-for="(image, index) in form.contract_images_preview" :key="index" class="relative h-24 rounded-xl overflow-hidden">
                                <img :src="image" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 hover:opacity-100 transition-all text-white text-xs">
                                    <i class="bi bi-pencil"></i>
                                </div>
                            </div>
                        </div>
                        <input type="file" multiple accept="image/*,.pdf" class="hidden" @change="(e) => handleMultipleFiles(e, 'contract_images')" />
                    </label>
                    <p v-if="errors.contract_images" class="text-rose-500 text-[10px] font-bold text-center">{{ errors.contract_images }}</p>
                </div>
            </div>

            <!-- Right Space Images Panel -->
            <div class="md:col-span-5 h-full">
                <div class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm space-y-4 h-full flex flex-col justify-between">
                    <div class="space-y-4">
                        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-50 pb-2">
                            <i class="bi bi-images text-emerald-500"></i>
                            <span>Hình ảnh không gian phòng <span class="text-rose-500">*</span></span>
                        </h2>

                        <label class="relative block border-2 border-dashed border-slate-200 hover:border-emerald-500 bg-slate-50/50 rounded-2xl cursor-pointer transition-all overflow-hidden flex-1 min-h-[160px]">
                            <div v-if="!form.room_images_preview?.length" class="p-8 text-center space-y-1.5">
                                <i class="bi bi-image text-slate-400 text-2xl"></i>
                                <div class="text-[10px] font-bold text-slate-600">Tải lên tối thiểu 3 ảnh thực tế</div>
                            </div>
                            <div v-else class="grid grid-cols-2 gap-3 p-4">
                                <div v-for="(image, index) in form.room_images_preview" :key="index" class="relative h-28 rounded-xl overflow-hidden">
                                    <img :src="image" class="w-full h-full object-cover" />
                                </div>
                            </div>
                            <input type="file" multiple accept="image/*" class="hidden" @change="(e) => handleMultipleFiles(e, 'room_images')" />
                        </label>
                        <p v-if="errors.room_images" class="text-rose-500 text-[10px] font-bold text-center">{{ errors.room_images }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation buttons -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-4">
            <button
                type="button"
                @click="emit('prev')"
                class="px-4 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5"
            >
                <i class="bi bi-arrow-left"></i> Quay lại
            </button>

            <button
                type="button"
                @click="nextStep"
                class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/10 transition-colors flex items-center gap-1.5"
            >
                Tiếp theo <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>
</template>
