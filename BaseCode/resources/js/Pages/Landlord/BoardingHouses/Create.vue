<script setup>
import { ref, watch } from "vue";
import axios from "axios";
import { Head, useForm, Link, usePage } from "@inertiajs/vue3";
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { showError, showWarning } from "@/Utils/swal";

const form = useForm({
    name: "",
    district: "",
    address_detail: "",
    directions_guide: "",
    latitude: "",
    longitude: "",
    contract_images: [],
    room_images: [],
});

const contractImagesPreview = ref([]);
const roomImagesPreview = ref([]);

const handleFileChange = (e, type) => {
    const files = Array.from(e.target.files);
    if (!files.length) return;

    if (type === "contract") {
        form.contract_images = files;
        contractImagesPreview.value = files.map((file) =>
            URL.createObjectURL(file),
        );
    } else {
        form.room_images = files;
        roomImagesPreview.value = files.map((file) =>
            URL.createObjectURL(file),
        );
    }
};

const getLocation = () => {
    if (!navigator.geolocation) {
        showWarning("Lỗi trình duyệt", "Trình duyệt không hỗ trợ Geolocation.");
        return;
    }
    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            form.latitude = lat.toString();
            form.longitude = lon.toString();

            try {
                //gọi API để dịch toạ độ
                const response = await axios.get(
                    `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&addressdetails=1`,
                );
                if (response.data) {
                    const addressObj = response.data.address || {};
                    //phân tích quận/huyện/xã
                    const districtVal =
                        addressObj.district ||
                        addressObj.suburd ||
                        addressObj.county ||
                        addressObj.city_district ||
                        addressObj.city ||
                        "";
                    form.district = districtVal;
                    //phân tích số nhà, ngõ ngách, tên đường
                    const road = addressObj.road || "";
                    const houseNumber = (addressObj.house_number =
                        addressObj.house_number || "");
                    const neighbourhood =
                        addressObj.neighbourhood || addressObj.quarter || "";
                    let detailAddress = "";
                    if (houseNumber) detailAddress += houseNumber + "";
                    if (road) detailAddress += road;
                    if (
                        neighbourhood &&
                        !!detailAddress.includes(neighbourhood)
                    ) {
                        detailAddress +=
                            (detailAddress ? "," : "") + neighbourhood;
                    }
                    //nếu không tách biệt được số nhà cụ thể, lấy tên đụa điểm
                    if (!detailAddress) {
                        detailAddress = response.data.display_name;
                    }
                    form.address_detail = detailAddress;
                    form.clearErrors(
                        "latitude",
                        "longitude",
                        "district",
                        "address_detail",
                    );
                }
            } catch (error) {
                console.error("Lỗi lấy địa chỉ từ GPS:", error);
            }
        },
        (error) => {
            showWarning(
                "Lỗi GPS",
                "Không thể lấy vị trí. Vui lòng nhập thủ công hoặc cho phép quyền truy cập vị trí.",
            );
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

const submit = () => {
    form.post(route("landlord.boarding-houses.store"), {
        preserveScroll: true,
        onSuccess: () => {
            // Handled by redirect in controller
        },
    });
};

const page = usePage();
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.error) {
            showError("Thất bại", flash.error);
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>

    <Head title="Thêm Cơ Sở Mới" />
    <LandlordLayout>
        <div class="max-w-4xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-black text-slate-800 tracking-tight">
                    Thêm Cơ Sở Mới
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Điền thông tin và nộp giấy tờ để tạo một khu trọ mới
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 md:p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tên cơ sở -->
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tên Cơ Sở / Khu Trọ
                                <span class="text-rose-500">*</span></label>
                            <input v-model="form.name" type="text"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all outline-none"
                                placeholder="VD: Trọ Sinh Viên Cầu Giấy" />
                            <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Khu vực -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Quận / Huyện / Xã
                                <span class="text-rose-500">*</span></label>
                            <input v-model="form.district" type="text"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all outline-none"
                                placeholder="Nhập khu vực..." />
                            <div v-if="form.errors.district" class="text-rose-500 text-xs mt-1">
                                {{ form.errors.district }}
                            </div>
                        </div>

                        <!-- Địa chỉ chi tiết -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Địa Chỉ Chi Tiết
                                <span class="text-rose-500">*</span></label>
                            <input v-model="form.address_detail" type="text"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all outline-none"
                                placeholder="Số nhà, ngõ, ngách..." />
                            <div v-if="form.errors.address_detail" class="text-rose-500 text-xs mt-1">
                                {{ form.errors.address_detail }}
                            </div>
                        </div>

                        <!-- Chỉ dẫn đường đi (Cẩm nang ngõ ngách) -->
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Chỉ dẫn đường đi (Cẩm nang tìm
                                phòng)
                                <span class="text-slate-400 font-normal">(Không bắt buộc)</span></label>
                            <textarea v-model="form.directions_guide" rows="3"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all outline-none resize-none"
                                placeholder="VD: Đi vào ngõ 12, rẽ phải ở cây cột điện thứ 2, nhà màu xanh có biển hiệu..."></textarea>
                            <div v-if="form.errors.directions_guide" class="text-rose-500 text-xs mt-1">
                                {{ form.errors.directions_guide }}
                            </div>
                        </div>

                        <!-- GPS -->
                        <div class="col-span-1 md:col-span-2 bg-emerald-50/50 p-4 rounded-xl border border-emerald-100">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-sm font-bold text-emerald-800">
                                        Tọa độ Bản Đồ (GPS)
                                        <span class="text-rose-500">*</span>
                                    </h3>
                                    <p class="text-xs text-emerald-600/80 mt-0.5">
                                        Giúp khách hàng dễ dàng tìm thấy nhà trọ
                                        của bạn trên bản đồ
                                    </p>
                                </div>
                                <button type="button" @click="getLocation"
                                    class="bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold px-3 py-2 rounded-lg transition-colors flex items-center gap-1.5 shadow-sm shadow-emerald-500/20">
                                    <i class="bi bi-geo-alt-fill"></i> Lấy Vị
                                    Trí Hiện Tại
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Vĩ độ
                                        (Latitude)</label>
                                    <input v-model="form.latitude" type="text"
                                        class="w-full bg-white border border-emerald-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Kinh độ
                                        (Longitude)</label>
                                    <input v-model="form.longitude" type="text"
                                        class="w-full bg-white border border-emerald-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none" />
                                </div>
                            </div>
                            <div v-if="
                                form.errors.latitude ||
                                form.errors.longitude
                            " class="text-rose-500 text-xs mt-2">
                                Vui lòng nhập đầy đủ tọa độ GPS
                            </div>

                            <!-- Bản đồ hiển thị vị trí -->
                            <div v-if="form.latitude && form.longitude"
                                class="mt-4 rounded-xl overflow-hidden border border-emerald-200 h-64 shadow-inner">
                                <iframe
                                    :src="`https://www.google.com/maps?q=${form.latitude},${form.longitude}&hl=vi&output=embed`"
                                    width="100%" height="100%" style="border: 0" allowfullscreen="" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                            </div>
                        </div>

                        <!-- Ảnh nhà / Ảnh mặt tiền -->
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Ảnh chụp khu trọ / Mặt tiền
                                <span class="text-rose-500">*</span></label>
                            <div class="relative w-full">
                                <input type="file" multiple accept="image/*"
                                    @change="(e) => handleFileChange(e, 'room')"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                                <div
                                    class="w-full bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl px-4 py-8 text-center hover:bg-slate-100 hover:border-emerald-400 transition-all flex flex-col items-center justify-center gap-2">
                                    <div
                                        class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center text-slate-400">
                                        <i class="bi bi-camera text-xl"></i>
                                    </div>
                                    <span class="text-sm font-bold text-slate-600">Bấm hoặc kéo thả ảnh mặt tiền vào
                                        đây</span>
                                    <span class="text-xs text-slate-400">Tối đa 5MB mỗi ảnh</span>
                                </div>
                            </div>
                            <div v-if="form.errors.room_images" class="text-rose-500 text-xs mt-1">
                                {{ form.errors.room_images }}
                            </div>
                            <div v-if="roomImagesPreview.length > 0" class="flex flex-wrap gap-2 mt-3">
                                <img v-for="(img, idx) in roomImagesPreview" :key="idx" :src="img"
                                    class="h-20 w-20 object-cover rounded-lg border border-slate-200" />
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-100" />

                    <div class="flex justify-end gap-3">
                        <Link :href="route('landlord.boarding-houses.index')"
                            class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                            Hủy Bỏ
                        </Link>
                        <button type="submit" :disabled="form.processing"
                            class="bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white px-6 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                            <span v-if="form.processing"><i class="bi bi-arrow-repeat animate-spin"></i>
                                Đang Xử Lý...</span>
                            <span v-else><i class="bi bi-check-lg"></i> Nộp Đơn
                                Duyệt</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </LandlordLayout>
</template>
