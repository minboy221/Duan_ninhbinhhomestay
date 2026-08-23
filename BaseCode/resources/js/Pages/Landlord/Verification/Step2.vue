<script setup>
import { defineProps, defineEmits, ref, onMounted, onUnmounted } from "vue";
import heic2any from "heic2any";
import { HA_NAM_COMMUNES } from "@/constants/locations.js";

const props = defineProps({
    form: Object,
});

const emit = defineEmits(["next", "prev"]);

const errors = ref({});

const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

const selectCommune = (commune) => {
    props.form.ward = commune;
    isDropdownOpen.value = false;
};

const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
};

onMounted(() => {
    const handleClickOutside = (event) => {
        if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
            isDropdownOpen.value = false;
        }
    };
    window.addEventListener("click", handleClickOutside, true);
    onUnmounted(() => {
        window.removeEventListener("click", handleClickOutside, true);
    });
});

// Hàm chuyển đổi ảnh HEIC sang JPEG (nạp động để tránh đơ di động)
const convertHeicToJpeg = async (file) => {
    try {
        const heic2anyModule = await import("heic2any");
        const heic2any = heic2anyModule.default || heic2anyModule;
        const convertedBlob = await heic2any({
            blob: file,
            toType: "image/jpeg",
        });
        return new File(
            [convertedBlob],
            file.name.replace(/\.[^/.]+$/, ".jpg"),
            { type: "image/jpeg" },
        );
    } catch (error) {
        console.error("Lỗi khi chuyển đổi file HEIC:", error);
        return file;
    }
};

<<<<<<< HEAD
// Hàm trích xuất tọa độ GPS từ Ảnh hoặc Video bằng exifr (có fallback Geolocation trình duyệt)
const extractGPSMetadata = async (file) => {
    try {
        const exifrModule = await import("exifr");
        const exifr = exifrModule.default || exifrModule;
        const gps = await exifr.gps(file);
        if (gps && gps.latitude && gps.longitude) {
            props.form.latitude = gps.latitude;
            props.form.longitude = gps.longitude;
            console.log("Đã trích xuất GPS từ file:", gps.latitude, gps.longitude);
            return;
        }
    } catch (error) {
        console.warn("Không đọc được EXIF GPS từ file:", error);
    }

    // Nếu file ảnh/video không có sẵn dữ liệu GPS EXIF, dùng Geolocation API của trình duyệt làm fallback
    if (!props.form.latitude && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                props.form.latitude = pos.coords.latitude;
                props.form.longitude = pos.coords.longitude;
                console.log("Đã lấy GPS vị trí hiện tại:", pos.coords.latitude, pos.coords.longitude);
            },
            (err) => {
                console.warn("Không lấy được Geolocation trình duyệt:", err);
            },
            { enableHighAccuracy: true, timeout: 5000 }
        );
    }
};

// Xử lý tải ảnh/video cho hồ sơ pháp lý (contract_images) và không gian (room_images)
=======
// Xử lý tải ảnh/file cho hồ sơ pháp lý (contract_images) và không gian (room_images)
// Không lấy GPS ở frontend nữa: GPS sẽ được trích xuất từ ảnh ở backend/admin để đảm bảo dữ liệu chuẩn nhất.
>>>>>>> a5d242909cbdb77076c294474466cef862d7a2c2
const handleMultipleFiles = async (e, field) => {
    const files = Array.from(e.target.files);
    if (!files.length) return;

    if (!props.form[field]) {
        props.form[field] = [];
    }
    if (!props.form[`${field}_preview`]) {
        props.form[`${field}_preview`] = [];
    }

    for (let file of files) {
        // Nếu file là HEIC, thực hiện chuyển đổi
        if (file.name.toLowerCase().endsWith(".heic")) {
            file = await convertHeicToJpeg(file);
        }

<<<<<<< HEAD
        // Nếu tải ảnh/video phòng và chưa có tọa độ GPS, trích xuất GPS từ file này
        if (field === "room_images" && !props.form.latitude) {
            await extractGPSMetadata(file);
        }

=======
>>>>>>> a5d242909cbdb77076c294474466cef862d7a2c2
        props.form[field].push(file);

        //tạo preview hiển thị
        const reader = new FileReader();
        if (compressedFile.type === "application/pdf") {
            props.form[`${field}_preview`].push({
                name: compressedFile.name,
                type: compressedFile.type,
                size: (compressedFile.size / (1024 * 1024)).toFixed(1) + " MB",
                url: null,
            });
        } else {
            reader.onload = (event) => {
                props.form[`${field}_preview`].push({
                    name: compressedFile.name,
                    type: compressedFile.type,
                    sizeL:
                        (compressedFile.size / (1024 * 1024)).toFixed(1) +
                        " MB",
                    url: event.target.result,
                });
            };
            reader.readAsDataURL(compressedFile);
        }
    }
};

const removeFile = (index, field) => {
    if (props.form[field]) {
        props.form[field].splice(index, 1);
    }
    if (props.form[`${field}_preview`]) {
        props.form[`${field}_preview`].splice(index, 1);
    }
};

const validate = () => {
    errors.value = {};
    if (!props.form.property_name) {
        errors.value.property_name = "Vui lòng nhập tên Homestay";
    }
    if (!props.form.ward) {
        errors.value.ward = "Vui lòng chọn Phường/Xã";
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

//tạo computed URL bản đồ google tự động lấy toạ độ từ ảnh
const googleMapUrlFromPhoto = computed(() => {
    //nếu ảnh có toạ độ GPS
    if (props.form.latitude && props.form.longitude) {
        return `https://maps.google.com/maps?q=${props.form.latitude},${props.form.longitude}&t=&z=16&ie=UTF8&iwloc=&output=embed`;
    }
    //nếu ảnh không có GPS hiển thị theo tên địa chỉ nhà trọ đã nhập
    if (props.form.address_detail || props.form.ward) {
        const fullAddress = `${props.form.address_detail || ""}
        ${props.form.ward || ""}`;
        return `https://maps.google.com/maps?q=${encodeURIComponent(fullAddress)}&t=&z=15&ie=UTF8&iwloc=&output=embed`;
    }
    return null;
});

//nếu ảnh không có GPS, tự động lấy toạ độ từ địa chỉ người dùng chọn
const fetchGpsFromAddress = async () => {
    if (!props.form.ward) return;
    const fullAddress = `${props.form.address_detail || ""}, ${props.form.ward}`;
    try {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}`,
        );
        const data = await res.json();
        if (data && data.length > 0) {
            props.form.latitude = parseFloat(data[0].lat);
            props.form.longitude = parseFloat(data[0].lon);
        }
    } catch (e) {
        console.error(e);
    }
};

//lấy vị trí GPS thực tế từ thiết bị điện thoại của chủ trọ
const getCurrentDeviceLocation = () => {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                props.form.latitude = position.coords.latitude;
                props.form.longitude = position.coords.longitude;
                console.log(
                    "Đã lấy định vị GPS điện thoại thành công:",
                    position.coords.latitude,
                    position.coords.longitude,
                );
            },
            (error) => {
                console.log(
                    "Không thể lấy vị trí thiết bị, chuyển sang dùng GPS địa chỉ.",
                );
                fetchGpsFromAddress();
            },
            { enableHighAccuracy: true, timeout: 10000 },
        );
    } else {
        fetchGpsFromAddress();
    }
};
onMounted(() => {
    getCurrentDeviceLocation();
});

// Hàm chuyển Tọa độ GPS đọc từ Ảnh thành Tên Địa chỉ chữ tự động
const reverseGeocodeFromPhotoGps = async (lat, lng) => {
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=vi`,
        );
        const data = await response.json();

        if (data && data.display_name) {
            console.log("Đã đọc được Địa chỉ từ GPS ảnh:", data.display_name);

            // Nếu chưa có địa chỉ chi tiết, tự động điền địa chỉ đọc được từ ảnh vào Form!
            if (!props.form.address_detail) {
                props.form.address_detail = data.display_name;
            }
        }
    } catch (error) {
        console.error("Không thể chuyển đổi GPS ảnh thành địa chỉ chữ:", error);
    }
};
</script>

<template>
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-on-surface tracking-tight mb-3">
                Thông tin Cơ sở lưu trú
            </h1>
            <p class="text-on-surface-variant text-lg">
                Vui lòng cung cấp chi tiết về homestay của bạn để chúng tôi có
                thể xác minh.
            </p>
        </div>

        <!-- Multi-step Form Content -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-7 space-y-8">
                <!-- Basic Info Card -->
                <section class="glass-card p-8 rounded-xl shadow-sm relative z-20">
                    <h2 class="text-xl font-bold text-on-surface mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">home</span>
                        Thông tin cơ bản
                    </h2>
                    <div class="space-y-6">
                        <!-- Property Name -->
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-on-surface mb-2 tracking-wide uppercase">Tên
                                Homestay
                                <span class="text-error font-bold">*</span></label>
                            <input v-model="form.property_name" type="text" placeholder="Ví dụ: Ninh Bình Calm Homestay"
                                class="w-full h-14 px-6 rounded-lg bg-surface-container-lowest border-none ring-1 ring-outline-variant/30 focus:ring-4 focus:ring-primary-container/30 transition-all text-on-surface"
                                :class="{
                                    'ring-error/50': errors.property_name,
                                }" />
                            <p v-if="errors.property_name" class="text-error text-xs font-bold">
                                {{ errors.property_name }}
                            </p>
                        </div>

                        <!-- Ward & Address detail -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                            <div class="space-y-1" ref="dropdownRef">
                                <label
                                    class="block text-sm font-semibold text-on-surface mb-2 tracking-wide uppercase">Phường
                                    / Xã
                                    <span class="text-error font-bold">*</span></label>
                                <div class="relative w-full">
                                    <!-- Custom Trigger Button (matching standard form styles) -->
                                    <button type="button" @click="toggleDropdown"
                                        class="w-full h-14 px-6 pr-10 rounded-lg bg-surface-container-lowest border-none ring-1 ring-outline-variant/30 focus:ring-4 focus:ring-primary-container/30 transition-all text-on-surface flex items-center justify-between text-left cursor-pointer select-none"
                                        :class="{
                                            'ring-error/50': errors.ward,
                                        }">
                                        <span :class="{
                                            'text-on-surface-variant/60':
                                                !form.ward,
                                        }">
                                            {{
                                                form.ward ||
                                                "-- Chọn Phường/Xã --"
                                            }}
                                        </span>
                                        <span
                                            class="material-symbols-outlined text-on-surface-variant transition-transform duration-300"
                                            :class="{
                                                'rotate-180': isDropdownOpen,
                                            }">
                                            keyboard_arrow_down
                                        </span>
                                    </button>

                                    <!-- Custom Dropdown Options Menu -->
                                    <transition name="dropdown-fade">
                                        <div v-show="isDropdownOpen"
                                            class="absolute top-full left-0 right-0 mt-2 bg-surface-container-lowest rounded-xl shadow-2xl border border-outline-variant/20 z-[999] p-2 max-h-60 overflow-y-auto custom-scrollbar">
                                            <button v-for="commune in HA_NAM_COMMUNES" :key="commune" type="button"
                                                @click="selectCommune(commune)"
                                                class="w-full px-4 py-3 rounded-lg text-left text-sm font-medium transition-all duration-150 flex items-center justify-between"
                                                :class="form.ward === commune
                                                        ? 'bg-primary/10 text-primary font-bold shadow-sm'
                                                        : 'hover:bg-surface-container-low text-on-surface-variant hover:text-on-surface'
                                                    ">
                                                <span>{{ commune }}</span>
                                                <span v-if="form.ward === commune"
                                                    class="material-symbols-outlined text-sm text-primary font-extrabold">check</span>
                                            </button>
                                        </div>
                                    </transition>
                                </div>
                                <p v-if="errors.ward" class="text-error text-xs font-bold">
                                    {{ errors.ward }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <label
                                    class="block text-sm font-semibold text-on-surface mb-2 tracking-wide uppercase">Địa
                                    chỉ chi tiết
                                    <span class="text-error font-bold">*</span></label>
                                <div class="w-full">
                                    <input v-model="form.address_detail" type="text" placeholder="Số nhà, tên đường..."
                                        class="w-full h-14 px-6 rounded-lg bg-surface-container-lowest border-none ring-1 ring-outline-variant/30 focus:ring-4 focus:ring-primary-container/30 transition-all text-on-surface"
                                        :class="{
                                            'ring-error/50':
                                                errors.address_detail,
                                        }" />
                                </div>
                                <p v-if="errors.address_detail" class="text-error text-xs font-bold">
                                    {{ errors.address_detail }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Document Upload Section -->
                <section class="glass-card p-8 rounded-xl shadow-sm space-y-4 relative z-10">
                    <h2 class="text-xl font-bold text-on-surface mb-2 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">description</span>
                        Hồ sơ pháp lý
                        <span class="text-error font-bold">*</span>
                    </h2>
                    <p class="text-sm text-on-surface-variant mb-6">
                        Tải lên bản hợp đồng thuê trọ (Định dạng: JPG, PNG,
                        PDF).
                    </p>

                    <!-- Drag Drop Area -->
                    <label class="relative block group">
                        <div
                            class="border-2 border-dashed border-outline-variant/40 rounded-xl p-10 text-center hover:border-primary transition-colors cursor-pointer bg-surface-container-low/30">
                            <span class="material-symbols-outlined text-4xl text-outline mb-4">cloud_upload</span>
                            <p class="text-on-surface font-medium">
                                Nhấn để tải lên hoặc kéo thả tệp
                            </p>
                            <p class="text-xs text-on-surface-variant mt-2">
                                Dung lượng tối đa 10MB
                            </p>
                        </div>
                        <input type="file" multiple accept="image/*,.pdf" class="hidden" @change="
                            (e) => handleMultipleFiles(e, 'contract_images')
                        " />
                    </label>

                    <p v-if="errors.contract_images" class="text-error text-xs font-bold mt-2">
                        {{ errors.contract_images }}
                    </p>

                    <!-- File Previews List -->
                    <div v-if="form.contract_images_preview?.length" class="space-y-3 mt-4">
                        <div v-for="(
file, index
                            ) in form.contract_images_preview" :key="'contract-' + index"
                            class="flex items-center gap-4 p-3 rounded-lg bg-surface-container-lowest border border-outline-variant/20 transition-all hover:shadow-sm">
                            <div
                                class="w-12 h-12 bg-primary-container rounded flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-on-primary-container">
                                    {{
                                        file.type === "application/pdf"
                                            ? "picture_as_pdf"
                                            : "image"
                                    }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-on-surface truncate" :title="file.name">
                                    {{ file.name }}
                                </p>
                                <p class="text-xs text-on-surface-variant">
                                    {{ file.size || "Kích thước ẩn" }}
                                </p>
                            </div>
                            <button type="button" @click="removeFile(index, 'contract_images')"
                                class="text-error-dim hover:bg-error-container/10 p-2 rounded-full transition-colors flex items-center justify-center">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <div class="lg:col-span-5 space-y-8">
                <!-- Gallery Section -->
                <section class="glass-card p-8 rounded-xl h-full flex flex-col justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-on-surface mb-6 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">collections</span>
                            Hình ảnh & Video không gian <span class="text-error font-bold">*</span>
                        </h2>

                        <!-- Drag Drop Area for Room Images & Videos -->
                        <label class="relative block group mb-6">
                            <div
                                class="border-2 border-dashed border-outline-variant/40 rounded-xl p-6 text-center hover:border-primary transition-colors cursor-pointer bg-surface-container-low/30">
                                <span class="material-symbols-outlined text-2xl text-outline mb-2">video_camera_back</span>
                                <p class="text-sm font-medium">Tải lên Ảnh / Video Homestay</p>
                                <p class="text-xs text-outline font-normal mt-1">(Hỗ trợ JPG, PNG, HEIC, MP4, MOV - Tối đa 20MB)</p>
                            </div>
                            <input type="file" multiple accept="image/*,video/*" class="hidden"
                                @change="(e) => handleMultipleFiles(e, 'room_images')" />
                        </label>

                        <p v-if="errors.room_images" class="text-error text-xs font-bold mb-4">
                            {{ errors.room_images }}
                        </p>

                        <!-- Bento Image Grid Preview -->
                        <div v-if="form.room_images_preview?.length" class="grid grid-cols-2 gap-3 h-64">
                            <!-- Image 1 (Left / Large) -->
                            <div v-if="form.room_images_preview[0]"
                                class="relative rounded-lg overflow-hidden group border border-outline-variant/20 bg-slate-950">
                                <img v-if="
                                    form.room_images_preview[0].type.startsWith(
                                        'image/',
                                    )
                                " :src="form.room_images_preview[0].url" class="w-full h-full object-cover" />
                                <video v-else :src="form.room_images_preview[0].url" class="w-full h-full object-cover"
                                    autoplay muted loop playsinline></video>
                                <div
                                    class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <button type="button" @click="removeFile(0, 'room_images')"
                                        class="p-2 bg-white/20 backdrop-blur rounded-full text-white hover:bg-error transition-all">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Right Grid (Two rows) -->
                            <div class="grid grid-rows-2 gap-3">
                                <!-- Image 2 -->
                                <div v-if="form.room_images_preview[1]"
                                    class="relative rounded-lg overflow-hidden group border border-outline-variant/20 bg-slate-950">
                                    <img v-if="
                                        form.room_images_preview[1].type.startsWith(
                                            'image/',
                                        )
                                    " :src="form.room_images_preview[1].url" class="w-full h-full object-cover" />
                                    <video v-else :src="form.room_images_preview[1].url"
                                        class="w-full h-full object-cover" autoplay muted loop playsinline></video>
                                    <div
                                        class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <button type="button" @click="
                                            removeFile(1, 'room_images')
                                            "
                                            class="p-2 bg-white/20 backdrop-blur rounded-full text-white hover:bg-error transition-all">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Image 3 / Overflow indicator -->
                                <div v-if="form.room_images_preview[2]"
                                    class="relative rounded-lg overflow-hidden group border border-outline-variant/20 bg-slate-950">
                                    <img v-if="
                                        form.room_images_preview[2].type.startsWith(
                                            'image/',
                                        )
                                    " :src="form.room_images_preview[2].url" class="w-full h-full object-cover" />
                                    <video v-else :src="form.room_images_preview[2].url"
                                        class="w-full h-full object-cover" autoplay muted loop playsinline></video>

                                    <div
                                        class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center text-white">
                                        <span v-if="
                                            form.room_images_preview
                                                .length > 3
                                        " class="text-xl font-bold">+{{
                                                form.room_images_preview
                                                    .length - 2
                                            }}
                                            Ảnh</span>
                                        <span v-else class="text-sm font-bold">Ảnh 3</span>
                                        <button type="button" @click="
                                            removeFile(2, 'room_images')
                                            "
                                            class="mt-2 p-1.5 bg-white/20 backdrop-blur rounded-full text-white hover:bg-error transition-all flex items-center justify-center">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- If overflow images exist (>3), provide a small listing for users to delete them too -->
                        <div v-if="form.room_images_preview?.length > 3"
                            class="max-h-28 overflow-y-auto border border-outline-variant/20 rounded-lg p-2 space-y-1.5 bg-surface-container-lowest mt-4">
                            <div v-for="(
file, index
                                ) in form.room_images_preview.slice(3)" :key="'room-extra-' + index"
                                class="flex items-center justify-between text-xs p-1.5 hover:bg-surface-container rounded transition-colors">
                                <span class="truncate max-w-[200px] font-medium text-on-surface-variant">{{ file.name
                                    }}</span>
                                <button type="button" @click="
                                    removeFile(index + 3, 'room_images')
                                    " class="text-error hover:text-error-dim font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">delete</span>
                                    Xoá
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-on-surface-variant italic mt-3">* Tọa độ sẽ được trích xuất từ dữ liệu GPS nằm trong ảnh đã tải lên để hiển thị chính xác trong admin.</p>
                </section>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-12 flex flex-col-reverse md:flex-row justify-between items-center gap-4 w-full">
            <button type="button" @click="emit('prev')"
                class="w-full md:w-44 py-4 rounded-full border-2 border-primary text-primary font-bold hover:bg-primary-container/10 transition-all flex items-center justify-center gap-2 whitespace-nowrap flex-shrink-0">
                <span class="material-symbols-outlined">arrow_back</span>
                Quay lại
            </button>
            <button type="button" @click="nextStep"
                class="w-full md:w-44 py-4 rounded-full bg-gradient-to-br from-primary to-primary-container text-on-primary font-bold shadow-xl hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 whitespace-nowrap flex-shrink-0">
                Tiếp theo
                <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(20px);
    border: 1.5px solid rgba(217, 221, 224, 0.3);
}

select {
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    background-image: none !important;
}

select::-ms-expand {
    display: none !important;
}

/* Custom Scrollbar for Dropdown List */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 9999px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.2);
}

/* Transition for Custom Dropdown */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
