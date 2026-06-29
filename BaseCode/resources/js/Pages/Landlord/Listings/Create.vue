<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, watch } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import axios from "axios";
import { computed } from "vue";
//phần soạn thảo văn bản
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
const props = defineProps({
    boardingHouses: Array,
});
const selectedHouse = ref(null);
const selectedFloor = ref(null);
const availableFloors = ref([]);
const availableRooms = ref([]);
//hiển thị tiện ích của phòng
const roomServices = ref([]);
const selectedRoomInfo = ref(null);
const roomDetails = ref(null);
const isLoadingDetails = ref(false);
// lấy toạ độ trực tiếp khi đăng tin
const isLocating = ref(false);

const form = useForm({
    room_id: "",
    title: "",
    description: "",
    address: "",
    latitude: null,
    longitude: null,
    images: [],
    action: "publish",
});

watch(selectedHouse, (newHouse) => {
    selectedFloor.value = null;
    form.room_id = "";
    roomDetails.value = null;
    availableFloors.value = newHouse ? newHouse.floors : [];
});

watch(selectedFloor, (newFloor) => {
    form.room_id = "";
    roomDetails.value = null;
    availableRooms.value = newFloor
        ? newFloor.rooms.filter((r) => {
              //lấy mảng bài viết
              const posts = r.room_posts || r.roomPosts;
              //kiểm tra phòng này có tin dăng nháp, chờ, hay đã duyệt chx
              const hasActivePost =
                  posts &&
                  posts.some((p) =>
                      ["draft", "pending", "approved"].includes(p.status),
                  );
              return r.status === "available" && !hasActivePost;
          })
        : [];
});

watch(
    () => form.room_id,
    async (newRoomId) => {
        if (!newRoomId) {
            roomDetails.value = null;
            roomServices.value = [];
            selectedRoomInfo.value = null;
            // Reset địa chỉ khi không chọn phòng
            form.address = "";
            form.latitude = null;
            form.longitude = null;
            return;
        }
        isLoadingDetails.value = true;
        try {
            const [detailsResponse, servicesResponse] = await Promise.all([
                axios.get(`/landlord/rooms/${newRoomId}/details-for-listing`),
                axios.get(`/landlord/rooms/${newRoomId}/services`),
            ]);
            roomDetails.value = detailsResponse.data;
            if (selectedHouse.value) {
                form.title = `Cho thuê phòng ${detailsResponse.data.room_number} - Khu nhà ${selectedHouse.value.name}`;
            }
            roomServices.value = servicesResponse.data.services;
            selectedRoomInfo.value = {
                price: servicesResponse.data.price,
            };

            // === TỰ ĐỘNG ĐIỀN ĐỊA CHỈ & GPS CỦA KHU TRỌ/TẦNG VÀO GIAO DIỆN ===
            if (detailsResponse.data.floor) {
                form.address = detailsResponse.data.floor.address || "";
                form.latitude = detailsResponse.data.floor.latitude || null;
                form.longitude = detailsResponse.data.floor.longitude || null;
            }
            // ========================================================
            
        } catch (error) {
            console.error("Lỗi khi tải thông tin chi tiết và tiện ích phòng:", error);
        } finally {
            isLoadingDetails.value = false;
        }
    },
);

//Phần xử lý tải ảnh lên
const handleFileChange = (e) => {
    // Thêm ảnh mới vào danh sách hiện tại thay vì ghi đè (tuỳ chọn, nhưng nếu cần sửa lỗi ko cho ảnh được thì sửa hàm này)
    // Để giữ ảnh cũ nếu chọn thêm nhiều lần:
    const newFiles = Array.from(e.target.files);
    form.images = [...form.images, ...newFiles];
};

const getObjectUrl = (file) => {
    if (file instanceof File) {
        if (!file.objectUrl) {
            file.objectUrl = URL.createObjectURL(file);
        }
        return file.objectUrl;
    }
    return file;
};

//Phần xoá bớt ảnh trong danh sách xem trước
const removeImage = (index) => {
    form.images.splice(index, 1);
};

//phần submit
const submitForm = (actionType) => {
    form.clearErrors();
    // Xóa sạch các lỗi đỏ cũ trước khi gửi lượt mới
    form.transform((data) => ({
        ...data,
        action: actionType,
    })).post(route("landlord.listings.store"), {
        preserveScroll: true,
        onSuccess: () => {
            // Tự động chuyển hướng hoặc hiện thông báo thành công
        },
    });
};

//Phần định vị tiền tệ
const formatMoney = (n) =>
    n ? new Intl.NumberFormat("vi-VN").format(n) + "đ" : "-";

//hàm kích hoạt GPS để lấy địa chỉ tự động
const getCurrentPosition = () => {
    if (!navigator.geolocation) {
        alert("Trình duyệt của bạn không hỗ trợ trức năng định vị GPS");
        return;
    }
    isLocating.value = true;

    //Gọi API của trình duyệt để lấy toạ độ GPS hiện tại
    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            //lưu toạ độ vào form để gửi lên backend
            form.latitude = lat;
            form.longitude = lon;

            try {
                //lấy API của OpenStreetMap để dịch toạ độ thành địa chỉ
                const response = await axios.get(
                    `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&addressdetails=1`,
                );
                if (response.data && response.data.display_name) {
                    //tự động điền địa chỉ dịch được vào ô input
                    form.address = response.data.display_name;
                }
            } catch (error) {
                console.error("Lỗi dịch toạ độ sang địa chỉ:", error);
                alert("Đã lấy được toạ độ nhưng không thể dịc thành địa chỉ");
            } finally {
                isLocating.value = false;
            }
        },
        (error) => {
            isLocating.value = false;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    alert("Bạn đã từ chối cấp quyền truy cập GPS");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Không thể xác định được vị trí hiện tại");
                    break;
                case error.TIMEOUT:
                    alert("Quá thời gian yêu cầu lấy vị trí.");
                    break;
                default:
                    alert("Đã xảy ra lỗi không xác định khi lấy vị trí.");
                    break;
            }
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

//phần hiển thị bản đồ map
const mapUrl = computed(() => {
    if (form.latitude && form.longitude) {
        return `https://maps.google.com/maps?q=${form.latitude},${form.longitude}&z=15&output=embed`;
    }

    if (form.address) {
        return `https://maps.google.com/maps?q=${encodeURIComponent(form.address)}&z=15&output=embed`;
    }

    return null;
});

// cấu hình đơn vị tính
const typeUnits = {
    per_kwh: "kWh",
    per_m3: "m³",
    fixed: "tháng",
    per_person: "người",
};
</script>

<template>
    <LandlordLayout>
        <template #header-title>
            <h1 class="ll-header-title">Đăng Tin Cho Thuê</h1>
        </template>

        <div class="create-wrap">
            <div class="create-cols">
                <!-- Form -->
                <div class="form-col">
                    <!-- Basic info -->
                    <div class="form-card">
                        <h3 class="fc-title">
                            <i class="bi bi-info-circle-fill"></i> Thông Tin Cơ
                            Bản
                        </h3>
                                                <div class="form-group mb-4">
                            <label
                                class="block text-sm font-bold text-gray-700 mb-1"
                                >Tiêu đề tin đăng:</label
                            >
                            <input
                                type="text"
                                v-model="form.title"
                                placeholder="Ví dụ: Phòng trọ khép kín giá rẻ ngay trung tâm TP Ninh Bình..."
                                class="w-full text-sm rounded-xl border-gray-300"
                                :class="
                                    form.errors.title
                                        ? 'border-red-500 focus:ring-red-500'
                                        : ''
                                "
                            />
                            <p
                                v-if="form.errors.title"
                                class="text-red-500 font-medium text-xs mt-1.5 flex items-center gap-1"
                            >
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <!-- Nhà trọ & Tầng (Chọn trước) -->
                        <div class="form-row-2 mb-4">
                            <div class="form-group">
                                <label class="form-label"> Nhà trọ </label>
                                <select v-model="selectedHouse" class="form-input">
                                    <option :value="null">Chọn nhà trọ</option>
                                    <option
                                        v-for="house in boardingHouses"
                                        :key="house.id"
                                        :value="house"
                                    >
                                        {{ house.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"> Tầng </label>
                                <select v-model="selectedFloor" class="form-input">
                                    <option :value="null">Chọn tầng</option>
                                    <option
                                        v-for="floor in availableFloors"
                                        :key="floor.id"
                                        :value="floor"
                                    >
                                        {{ floor.name.toLowerCase().startsWith('tầng') ? floor.name : 'Tầng ' + floor.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Phòng & Diện tích -->
                        <div class="form-row-2 mb-4">
                            <div class="form-group">
                                <label
                                    class="block text-sm font-bold text-gray-700 mb-1"
                                    >Chọn phòng trọ tiếp thị:</label
                                >
                                <select
                                    v-model="form.room_id"
                                    class="w-full text-sm rounded-xl border-gray-300"
                                >
                                    <option value="">
                                        -- Vui lòng chọn phòng --
                                    </option>
                                    <option
                                        v-for="room in availableRooms"
                                        :key="room.id"
                                        :value="room.id"
                                    >
                                        Phòng {{ room.room_number }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.room_id"
                                    class="text-red-500 font-medium text-xs mt-1.5 flex items-center gap-1"
                                >
                                    {{ form.errors.room_id }}
                                </p>
                            </div>
                            <div class="form-group">
                                <label class="form-label"
                                    >Diện tích (m²) *</label
                                >
                                <input
                                    :value="roomDetails?.area || ''"
                                    disabled
                                    class="form-input bg-gray-50 text-gray-500"
                                />
                            </div>
                        </div>

                        <!-- Các tiện ích sẵn có của phòng này -->
                        <div class="mb-4" v-if="roomServices.length > 0">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                Các tiện ích sẵn có của phòng này:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div
                                    v-for="service in roomServices"
                                    :key="service.id"
                                    class="flex items-center gap-2 p-2 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm"
                                >
                                    <svg
                                        class="w-4 h-4 text-green-600 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 13l4 4L19 7"
                                        ></path>
                                    </svg>
                                    <span>{{ service.name }}</span>
                                    <span
                                        v-if="service.price > 0"
                                        class="text-xs text-gray-500"
                                    >
                                        ({{
                                            new Intl.NumberFormat(
                                                "vi-VN",
                                            ).format(service.price)
                                        }}đ)
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div
                            class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500"
                            v-else-if="form.room_id"
                        >
                            Phòng này hiện chưa được thiết lập tiện ích nào.
                        </div>

                        <!-- Chỉ hiển thị thông tin địa chỉ khi đã chọn tầng và phòng -->
                        <div v-if="selectedFloor && form.room_id" class="space-y-4 mb-4">


                            <!-- Thông tin địa chỉ đầy đủ từ khu trọ -->
                            <div class="bg-blue-50/40 border border-blue-100 rounded-lg p-3.5">
                                <label class="block text-xs font-bold text-blue-800 mb-1.5 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="bi bi-info-circle"></i> Địa chỉ phòng trọ (Tự động cập nhật)
                                </label>
                                <p class="text-sm text-gray-700 font-medium">
                                    {{ form.address }}
                                </p>
                                <div
                                    v-if="form.latitude && form.longitude"
                                    class="mt-2 text-xs text-gray-500 flex gap-4 border-t border-blue-100/50 pt-2"
                                >
                                    <span><i class="bi bi-compass text-gray-400"></i> <strong>Vĩ độ (Lat):</strong> {{ form.latitude }}</span>
                                    <span><i class="bi bi-compass text-gray-400"></i> <strong>Kinh độ (Lng):</strong> {{ form.longitude }}</span>
                                </div>
                                <div
                                    v-if="form.errors.address"
                                    class="text-red-500 text-xs mt-1"
                                >
                                    {{ form.errors.address }}
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                            >
                                Mô tả chi tiết bài đăng:
                            </label>

                            <div class="quill-editor-wrapper">
                                <QuillEditor
                                    v-model:content="form.description"
                                    contentType="html"
                                    theme="snow"
                                    placeholder="Nhập mô tả chi tiết phòng trọ của bạn (ví dụ: quy định giờ giấc, nội thất đi kèm, cọc bao nhiêu tháng...)"
                                    :toolbar="[
                                        [
                                            'bold',
                                            'italic',
                                            'underline',
                                            'strike',
                                        ], // Các nút Đậm, Nghiêng, Gạch chân, Gạch ngang
                                        [
                                            { list: 'ordered' },
                                            { list: 'bullet' },
                                        ], // Danh sách số, danh sách dấu chấm
                                        [{ header: [1, 2, 3, false] }], // Kích thước tiêu đề chữ
                                        [{ color: [] }, { background: [] }], // Màu chữ, màu nền chữ
                                        [{ align: [] }], // Căn lề trái, giữa, phải
                                        ['clean'], // Nút xóa toàn bộ định dạng để viết lại
                                    ]"
                                />
                            </div>

                            <p
                                v-if="form.errors.description"
                                class="text-red-500 font-medium text-xs mt-1.5 flex items-center gap-1"
                            >
                                {{ form.errors.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="form-card">
                        <h3 class="fc-title">
                            <i class="bi bi-cash-coin"></i> Giá Thuê & Phí Dịch
                            Vụ
                        </h3>
                        <div class="form-row-3">
                            <div class="form-group">
                                <label class="form-label"
                                    >Giá thuê (đ/tháng) *</label
                                >
                                <input
                                    type="number"
                                    :value="roomDetails?.price || ''"
                                    disabled
                                    class="form-input"
                                    placeholder="3000000"
                                />
                                <span
                                    class="form-hint"
                                    v-if="roomDetails?.price"
                                >
                                    {{ formatMoney(roomDetails.price) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: images + map + preview -->
                <div class="right-col">
                    <!-- Images -->
                    <div class="form-card">
                        <h3 class="fc-title">
                            <i class="bi bi-images"></i> Hình Ảnh Phòng
                        </h3>

                        <label
                            class="img-upload-area transition-all"
                            :class="
                                form.errors.images
                                    ? 'border-red-500 bg-red-50/30 hover:bg-red-50/50'
                                    : ''
                            "
                        >
                            <input
                                type="file"
                                multiple
                                accept="image/*"
                                @change="handleFileChange"
                                style="display: none"
                            />
                            <i
                                class="bi bi-cloud-upload"
                                :class="
                                    form.errors.images ? 'text-red-500' : ''
                                "
                            ></i>
                            <span
                                :class="
                                    form.errors.images
                                        ? 'text-red-700 font-medium'
                                        : ''
                                "
                                >Nhấn để chọn ảnh</span
                            >
                            <span class="img-hint"
                                >JPG, PNG tối đa 5MB mỗi ảnh</span
                            >
                        </label>

                        <p
                            v-if="form.errors.images"
                            class="text-red-500 font-semibold text-xs mt-2 flex items-center gap-1.5 animate-pulse"
                        >
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            {{ form.errors.images }}
                        </p>

                        <div
                            class="mt-2 space-y-1"
                            v-if="
                                Object.keys(form.errors).some((k) =>
                                    k.startsWith('images.'),
                                )
                            "
                        >
                            <div v-for="(error, key) in form.errors" :key="key">
                                <p
                                    v-if="key.startsWith('images.')"
                                    class="text-red-500 font-medium text-xs flex items-center gap-1.5"
                                >
                                    <i
                                        class="bi bi-x-circle-fill text-[10px]"
                                    ></i>
                                    {{ error }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="img-preview-grid mt-4"
                            v-if="form.images.length > 0"
                        >
                            <div
                                v-for="(src, i) in form.images"
                                :key="i"
                                class="img-preview-item"
                            >
                                <img
                                    :src="getObjectUrl(src)"
                                    :alt="`Ảnh ${i + 1}`"
                                />
                                <button
                                    class="img-remove"
                                    @click="removeImage(i)"
                                >
                                    <i class="bi bi-x"></i>
                                </button>
                                <span v-if="i === 0" class="img-main-badge"
                                    >Ảnh chính</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Map placeholder -->
                    <div class="form-card">
                        <h3 class="fc-title">
                            <i class="bi bi-geo-alt-fill"></i> Vị Trí Trên Bản
                            Đồ
                        </h3>
                        <div class="map-container">
                            <iframe
                                v-if="mapUrl"
                                :src="mapUrl"
                                width="100%"
                                height="250"
                                style="border: 0; border-radius: 12px"
                                loading="lazy"
                            >
                            </iframe>

                            <div v-else class="map-placeholder">
                                <i class="bi bi-map"></i>
                                <span
                                    >Nhập địa chỉ hoặc lấy GPS để xem bản
                                    đồ</span
                                >
                            </div>
                        </div>
                        <input
                            v-model="form.address"
                            class="form-input mt-10"
                            placeholder="Nhập địa chỉ để tìm trên bản đồ..."
                        />
                    </div>

                    <!-- Preview -->
                    <div class="preview-box">
                        <div class="prev-title">
                            {{ form.title || "Tiêu đề bài đăng..." }}
                        </div>
                        <div class="prev-price">
                            {{ roomDetails?.price?.toLocaleString() || 0 }}đ
                        </div>
                        <div class="prev-meta">
                            <span> {{ roomDetails?.area || 0 }} m² </span>
                        </div>
                        <div
                            v-if="roomDetails?.services?.length"
                            class="flex flex-wrap gap-2 mt-3"
                        >
                            <span
                                v-for="service in roomDetails.services"
                                :key="service.id"
                                class="px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs"
                            >
                                {{ service.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="submit-bar">
                <Link :href="route('landlord.listings.index')">Hủy</Link>
                <button
                    type="button"
                    class="btn-draft"
                    @click="submitForm('draft')"
                >
                    <i class="bi bi-save"></i> Lưu Nháp
                </button>
                <button
                    type="button"
                    class="btn-submit"
                    @click="submitForm('publish')"
                >
                    <i class="bi bi-send-fill"></i> Đăng Tin
                </button>
            </div>
        </div>
    </LandlordLayout>
</template>

<style scoped>
.create-wrap {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.create-cols {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 20px;
    align-items: flex-start;
}

.form-col,
.right-col {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.form-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid #f0fdf4;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.fc-title {
    font-size: 14px;
    font-weight: 700;
    color: #064e3b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 7px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-row-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.form-row-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

form.price {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
}

.form-input {
    padding: 9px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 9px;
    font-size: 14px;
    outline: none;
    width: 100%;
    box-sizing: border-box;
}

.form-input:focus {
    border-color: #0f766e;
}

.form-textarea {
    resize: vertical;
    font-family: inherit;
}

.form-hint {
    font-size: 12px;
    color: #0f766e;
    font-weight: 600;
}

.mt-10 {
    margin-top: 10px;
}

/* Amenities */
.amenity-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.amenity-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 10px 6px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    color: #6b7280;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}

.amenity-btn i {
    font-size: 18px;
}

.amenity-active {
    border-color: #0f766e !important;
    background: #f0fdf4 !important;
    color: #0f766e !important;
}

.amenity-btn:hover:not(.amenity-active) {
    border-color: #d1fae5;
    background: #f0fdf4;
    color: #374151;
}

/* Images */
.img-upload-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    border: 2px dashed #d1fae5;
    border-radius: 12px;
    padding: 24px;
    cursor: pointer;
    transition: border-color 0.15s;
    color: #6b7280;
}

.img-upload-area:hover {
    border-color: #0f766e;
}

.img-upload-area i {
    font-size: 32px;
    color: #0f766e;
}

.img-upload-area span {
    font-size: 14px;
    font-weight: 600;
}

.img-hint {
    font-size: 11px;
    color: #9ca3af;
    font-weight: 400;
}

.img-preview-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-top: 10px;
}

.img-preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1;
}

.img-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.img-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.5);
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.img-main-badge {
    position: absolute;
    bottom: 4px;
    left: 4px;
    background: #0f766e;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 100px;
}

/* Map */
.map-placeholder {
    background: #f0fdf4;
    border-radius: 10px;
    height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #6b7280;
}

.map-placeholder i {
    font-size: 36px;
    color: #6ee7b7;
}

.map-hint {
    font-size: 11px;
    color: #9ca3af;
}

/* Preview */
.preview-card {
    background: #f0fdf4;
    border: 1.5px solid #d1fae5;
    border-radius: 16px;
    padding: 18px;
}

.preview-box {
    background: #fff;
    border-radius: 12px;
    padding: 14px;
    margin-top: 10px;
}

.prev-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
}

.prev-price {
    font-size: 20px;
    font-weight: 800;
    color: #0f766e;
    margin-bottom: 8px;
}

.prev-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.prev-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.prev-amenities {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.prev-amenity {
    padding: 3px 10px;
    background: #f0fdf4;
    color: #0f766e;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 600;
}

/* Submit bar */
.submit-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    background: #fff;
    border-radius: 16px;
    padding: 16px 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.btn-cancel {
    padding: 10px 20px;
    background: #fff;
    color: #374151;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.btn-draft {
    padding: 10px 20px;
    background: #fef9c3;
    color: #854d0e;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-submit {
    padding: 10px 24px;
    background: #0f766e;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 7px;
}

.btn-submit:hover {
    background: #0d9488;
}

@media (max-width: 1100px) {
    .create-cols {
        grid-template-columns: 1fr;
    }

    .amenity-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.map-container {
    width: 100%;
    overflow: hidden;
    border-radius: 12px;
}

.map-placeholder {
    height: 250px;
}
</style>
