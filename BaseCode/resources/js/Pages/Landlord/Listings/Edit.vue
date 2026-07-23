<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, watch, computed } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import axios from "axios";
// Phần soạn thảo văn bản
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import { showWarning, showError } from "@/Utils/swal";

const props = defineProps({
    post: Object,
    boardingHouses: Array,
});

// Xác định vị trí nhà trọ & tầng hiện tại để pre-select dropdowns
const initialHouse =
    props.boardingHouses.find((h) =>
        h.floors.some((f) => f.rooms.some((r) => r.id === props.post.room_id)),
    ) || null;

const initialFloor = initialHouse
    ? initialHouse.floors.find((f) =>
          f.rooms.some((r) => r.id === props.post.room_id),
      )
    : null;

const selectedHouse = ref(initialHouse);
const selectedFloor = ref(initialFloor);
const availableFloors = ref(initialHouse ? initialHouse.floors : []);
const availableRooms = ref([]);

const roomServices = ref([]);
const selectedRoomInfo = ref(null);
const roomDetails = ref(null);
const isLoadingDetails = ref(false);
const isLocating = ref(false);

const form = useForm({
    room_id: props.post.room_id || "",
    title: props.post.title || "",
    description: props.post.description || "",
    address: props.post.address || "",
    latitude: props.post.latitude || null,
    longitude: props.post.longitude || null,
    existing_images: props.post.image || [], // Chứa ảnh cũ
    images: [], // Chứa ảnh mới upload
    action: "publish",
});


// Load danh sách phòng của tầng ban đầu
if (selectedFloor.value) {
    availableRooms.value = selectedFloor.value.rooms.filter((r) => {
        if (r.id === props.post.room_id) return true; // Luôn hiển thị phòng hiện tại đang được sửa
        const posts = r.room_posts || r.roomPosts;
        const hasActivePost =
            posts &&
            posts.some((p) =>
                ["draft", "pending", "approved"].includes(p.status),
            );
        return r.status === "available" && !hasActivePost;
    });
}

// Watchers theo dõi thay đổi nhà trọ
watch(selectedHouse, (newHouse) => {
    selectedFloor.value = null;
    form.room_id = "";
    roomDetails.value = null;
    availableFloors.value = newHouse ? newHouse.floors : [];
});

// Watchers theo dõi thay đổi tầng
watch(selectedFloor, (newFloor) => {
    if (newFloor && newFloor.rooms.some((r) => r.id === props.post.room_id)) {
        form.room_id = props.post.room_id;
    } else {
        form.room_id = "";
        roomDetails.value = null;
    }

    availableRooms.value = newFloor
        ? newFloor.rooms.filter((r) => {
              if (r.id === props.post.room_id) return true; // Giữ lại phòng đang sửa
              const posts = r.room_posts || r.roomPosts;
              const hasActivePost =
                  posts &&
                  posts.some((p) =>
                      ["draft", "pending", "approved"].includes(p.status),
                  );
              return r.status === "available" && !hasActivePost;
          })
        : [];
});

// Theo dõi thay đổi phòng để tải chi tiết và tiện ích
watch(
    () => form.room_id,
    async (newRoomId) => {
        if (!newRoomId) {
            roomDetails.value = null;
            roomServices.value = [];
            selectedRoomInfo.value = null;
            return;
        }
        isLoadingDetails.value = true;
        try {
            const [detailsResponse, servicesResponse] = await Promise.all([
                axios.get(`/landlord/rooms/${newRoomId}/details-for-listing`),
                axios.get(`/landlord/rooms/${newRoomId}/services`),
            ]);
            roomDetails.value = detailsResponse.data;
            roomServices.value = servicesResponse.data.services;
            selectedRoomInfo.value = { price: servicesResponse.data.price };
            //tự động điền địa chỉ & gps mới từ tầng/khu
            if (detailsResponse.data.floor) {
                //nếu tin đăng chưa có địa chỉ, hoặc chủ trọ chọn phòng thuộc tầng khác với tầng cũ của phòng
                if (!form.address || (props.post.room?.floor_id !== detailsResponse.data.floor_id)) {
                    form.address = detailsResponse.data.floor.address || "";
                    form.latitude = detailsResponse.data.floor.latitude || null;
                    form.longitude = detailsResponse.data.floor.longitude || null;
                }
            }
        } catch (error) {
            console.error("Lỗi khi tải thông tin chi tiết phòng:", error);
        } finally {
            isLoadingDetails.value = false;
        }
    },
    { immediate: true },
);

// Xử lý xóa ảnh cũ
const removeExistingImage = (index) => {
    form.existing_images.splice(index, 1);
};

// Xử lý thêm ảnh mới
const handleFileChange = (e) => {
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

// Xoá ảnh mới
const removeNewImage = (index) => {
    form.images.splice(index, 1);
};

// Hàm định vị GPS
const getCurrentPosition = () => {
    if (!navigator.geolocation) {
        showWarning("Lỗi GPS", "Trình duyệt của bạn không hỗ trợ chức năng định vị GPS.");
        return;
    }
    isLocating.value = true;

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            form.latitude = lat;
            form.longitude = lon;

            try {
                const response = await axios.get(
                    `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&addressdetails=1`,
                );
                if (response.data && response.data.display_name) {
                    form.address = response.data.display_name;
                }
            } catch (error) {
                console.error("Lỗi dịch toạ độ sang địa chỉ:", error);
                showWarning("Cảnh báo", "Đã lấy được toạ độ nhưng không thể dịch thành địa chỉ.");
            } finally {
                isLocating.value = false;
            }
        },
        (error) => {
            isLocating.value = false;
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    showWarning("Từ chối truy cập", "Bạn đã từ chối cấp quyền truy cập GPS.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    showError("Lỗi vị trí", "Không thể xác định được vị trí hiện tại.");
                    break;
                case error.TIMEOUT:
                    showWarning("Hết thời gian", "Quá thời gian yêu cầu lấy vị trí.");
                    break;
                default:
                    showError("Lỗi không xác định", "Đã xảy ra lỗi không xác định khi lấy vị trí.");
                    break;
            }
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

const submitForm = (actionType) => {
    form.action = actionType;
    
    // Xóa sạch lỗi cũ trước khi validate mới
    form.clearErrors();
    
    let hasError = false;
    
    // Validate Tiêu đề
    if (!form.title || form.title.trim() === "") {
        form.setError("title", "Tiêu đề bài đăng không được để trống.");
        hasError = true;
    } else if (form.title.length < 10) {
        form.setError("title", "Tiêu đề bài đăng phải từ 10 ký tự trở lên.");
        hasError = true;
    } else if (form.title.length > 255) {
        form.setError("title", "Tiêu đề bài đăng không được vượt quá 255 ký tự.");
        hasError = true;
    }
    
    // Validate Phòng trọ
    if (!form.room_id) {
        form.setError("room_id", "Vui lòng chọn một căn phòng cụ thể.");
        hasError = true;
    }
    
    // Validate Mô tả
    const cleanDesc = form.description ? form.description.replace(/<[^>]*>/g, '').trim() : "";
    if (actionType === "publish") {
        if (!cleanDesc || cleanDesc === "") {
            form.setError("description", "Vui lòng nhập mô tả chi tiết cho phòng trọ.");
            hasError = true;
        } else if (cleanDesc.length < 20) {
            form.setError("description", "Nội dung mô tả phòng trọ phải từ 20 ký tự trở lên.");
            hasError = true;
        }
    } else {
        if (cleanDesc && cleanDesc.length > 0 && cleanDesc.length < 20) {
            form.setError("description", "Nội dung mô tả nếu nhập phải từ 20 ký tự trở lên.");
            hasError = true;
        }
    }

    // Validate Hình ảnh (Khi Đăng tin chính thức, tổng ảnh cũ và ảnh mới chọn thêm phải >= 1)
    if (actionType === "publish") {
        const totalImages = (form.existing_images ? form.existing_images.length : 0) + (form.images ? form.images.length : 0);
        if (totalImages === 0) {
            form.setError("images", "Bạn phải giữ lại hoặc tải lên ít nhất một tấm hình ảnh thực tế.");
            hasError = true;
        }
    }
    
    // Validate kích thước ảnh mới chọn thêm (Tối đa 2MB mỗi file)
    if (form.images && form.images.length > 0) {
        const invalidSize = form.images.some(file => file.size > 2 * 1024 * 1024); // 2MB
        if (invalidSize) {
            form.setError("images", "Dung lượng mỗi ảnh mới chọn không được vượt quá 2MB.");
            hasError = true;
        }
    }
    
    // Nếu có lỗi, cuộn màn hình đến phần tử lỗi đầu tiên
    if (hasError) {
        setTimeout(() => {
            const firstErrorEl = document.querySelector(".text-red-500");
            if (firstErrorEl) {
                firstErrorEl.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        }, 100);
        return;
    }
    
    // Phương pháp Spoofing PUT của Laravel vì có file đính kèm
    form.transform((data) => ({
        ...data,
        _method: "PUT",
    })).post(route("landlord.listings.update", props.post.id));
};
</script>

<template>
    <LandlordLayout>
        <template #header-title>
            <h1 class="ll-header-title">Chỉnh Sửa Tin Đăng</h1>
        </template>

        <div class="create-wrap">
            <div class="create-cols">
                <!-- Form chỉnh sửa -->
                <div class="form-col">
                    <!-- Thông tin cơ bản -->
                    <div class="form-card">
                        <h3 class="fc-title">
                            <i class="bi bi-info-circle-fill"></i> Thông Tin Cơ
                            Bản
                        </h3>
                                                <div class="form-group">
                            <label class="form-label">Tiêu đề tin đăng *</label>
                            <input
                                v-model="form.title"
                                class="form-input"
                                placeholder="VD: Phòng trọ sạch sẽ thoáng mát trung tâm..."
                            />
                            <div
                                v-if="form.errors.title"
                                class="text-red-500 text-xs mt-1"
                            >
                                {{ form.errors.title }}
                            </div>
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
                                <label class="form-label">Phòng *</label>
                                <select
                                    v-model="form.room_id"
                                    class="form-input"
                                >
                                    <option value="">Chọn Phòng</option>
                                    <option
                                        v-for="room in availableRooms"
                                        :key="room.id"
                                        :value="room.id"
                                    >
                                        Phòng {{ room.room_number }}
                                    </option>
                                </select>
                                <div
                                    v-if="form.errors.room_id"
                                    class="text-red-500 text-xs mt-1"
                                >
                                    {{ form.errors.room_id }}
                                </div>
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
                                        ],
                                        [
                                            { list: 'ordered' },
                                            { list: 'bullet' },
                                        ],
                                        [{ header: [1, 2, 3, false] }],
                                        [{ color: [] }, { background: [] }],
                                        [{ align: [] }],
                                        ['clean'],
                                    ]"
                                />
                            </div>
                            <div
                                v-if="form.errors.description"
                                class="text-red-500 text-xs mt-1"
                            >
                                {{ form.errors.description }}
                            </div>
                        </div>
                    </div>

                    <!-- Giá thuê -->
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
                                    class="form-input bg-gray-50 text-gray-500"
                                />
                                <span
                                    class="form-hint"
                                    v-if="roomDetails?.price"
                                >
                                    {{
                                        new Intl.NumberFormat("vi-VN").format(
                                            roomDetails.price,
                                        )
                                    }}đ
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Quản lý hình ảnh và Bản đồ -->
                <div class="right-col">
                    <!-- Hình ảnh -->
                    <div class="form-card">
                        <h3 class="fc-title">
                            <i class="bi bi-images"></i> Hình Ảnh Phòng
                        </h3>

                        <label class="img-upload-area mb-4">
                            <input
                                type="file"
                                multiple
                                accept="image/*"
                                @change="handleFileChange"
                                style="display: none"
                            />
                            <i class="bi bi-cloud-upload"></i>
                            <span>Chọn thêm ảnh mới</span>
                            <span class="img-hint"
                                >JPG, PNG tối đa 2MB mỗi ảnh</span
                            >
                        </label>

                        <!-- Ảnh hiện tại của bài viết -->
                        <div
                            v-if="form.existing_images.length > 0"
                            class="space-y-2"
                        >
                            <h4 class="text-xs font-bold text-slate-500">
                                Ảnh hiện tại:
                            </h4>
                            <div class="img-preview-grid">
                                <div
                                    v-for="(src, i) in form.existing_images"
                                    :key="'existing-' + i"
                                    class="img-preview-item"
                                >
                                    <img :src="src" alt="Ảnh hiện tại" />
                                    <button
                                        type="button"
                                        class="img-remove"
                                        @click="removeExistingImage(i)"
                                    >
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Ảnh mới chọn thêm -->
                        <div
                            v-if="form.images.length > 0"
                            class="space-y-2 mt-4"
                        >
                            <h4 class="text-xs font-bold text-slate-500">
                                Ảnh mới chọn thêm:
                            </h4>
                            <div class="img-preview-grid">
                                <div
                                    v-for="(src, i) in form.images"
                                    :key="'new-' + i"
                                    class="img-preview-item"
                                >
                                    <img
                                        :src="getObjectUrl(src)"
                                        alt="Ảnh mới thêm"
                                    />
                                    <button
                                        type="button"
                                        class="img-remove"
                                        @click="removeNewImage(i)"
                                    >
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="form.errors.images"
                            class="text-red-500 text-xs mt-1"
                        >
                            {{ form.errors.images }}
                        </div>
                    </div>

                    <!-- Google Map Preview -->
                    <div class="form-card">
                        <h3 class="fc-title">
                            <i class="bi bi-geo-alt-fill"></i> Vị Trí Trên Bản
                            Đồ
                        </h3>
                        <div class="map-container">
                            <iframe
                                v-if="form.latitude && form.longitude"
                                :src="`https://maps.google.com/maps?q=${form.latitude},${form.longitude}&z=15&output=embed`"
                                width="100%"
                                height="250"
                                style="border: 0; border-radius: 12px" loading="lazy" ></iframe> <div v-else class="map-placeholder"> <i class="bi bi-map"></i> <span>Nhập địa chỉ để định vị bản đồ</span> </div> </div> </div> <!-- Xem trước nội dung (Preview) -->
                    <div class="preview-box">
                        <div class="prev-title">
                            {{ form.title || "Tiêu đề bài đăng..." }}
                        </div>
                        <div class="prev-price text-emerald-600 font-bold">
                            {{
                                roomDetails?.price
                                    ? new Intl.NumberFormat("vi-VN").format(
                                          roomDetails.price,
                                      ) + "đ"
                                    : "0đ"
                            }}
                        </div>
                        <div class="prev-meta text-slate-400 text-xs mt-1">
                            <span
                                >Diện tích:
                                {{ roomDetails?.area || 0 }} m²</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thanh chức năng gửi Form -->
            <div class="submit-bar">
                <Link
                    :href="route('landlord.listings.index')"
                    class="btn-cancel"
                    >Hủy</Link
                >
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
                    <i class="bi bi-send-fill"></i> Cập Nhật
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
    grid-template-columns: 1fr;
    gap: 20px;
    align-items: flex-start;
}

@media (min-width: 1024px) {
    .create-cols {
        grid-template-columns: 1fr 360px;
    }
}

.form-col,
.right-col {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 100%;
}

.form-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid #f0fdf4;
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
    box-sizing: border-box;
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
    width: 100%;
}

.form-row-2,
.form-row-3 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

@media (min-width: 640px) {
    .form-row-2 {
        grid-template-columns: repeat(2, 1fr);
    }
    .form-row-3 {
        grid-template-columns: repeat(3, 1fr);
    }
}

.form-label {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
}

.form-input {
    padding: 9px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    width: 100%;
    box-sizing: border-box;
}

.form-input:focus {
    border-color: #0f766e;
}

.form-hint {
    font-size: 12px;
    color: #0f766e;
    font-weight: 600;
}

/* Images Upload UI */
.img-upload-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    border: 2px dashed #d1fae5;
    border-radius: 8px;
    padding: 20px;
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
}

.img-preview-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.img-preview-item {
    position: relative;
    border-radius: 6px;
    overflow: hidden;
    aspect-ratio: 1;
    border: 1px solid #e2e8f0;
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
    background: rgba(239, 68, 68, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
}

.img-remove:hover {
    background: rgb(220, 38, 38);
}

/* Map */
.map-container {
    background: #f8fafc;
    border-radius: 8px;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    width: 100%;
}

.map-placeholder {
    color: #94a3b8;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

/* Preview Box */
.preview-box {
    background: #fdfdfd;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
}

.prev-title {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}

/* Action bar */
.submit-bar {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 10px;
    border-top: 1px solid #e2e8f0;
    padding-top: 16px;
    background: #fff;
    border-radius: 8px;
    padding: 16px 20px;
}

.btn-cancel {
    padding: 10px 20px;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-cancel:hover {
    background: #f1f5f9;
}

.btn-draft {
    padding: 10px 20px;
    border: 1.5px solid #0f766e;
    background: transparent;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 700;
    color: #0f766e;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-draft:hover {
    background: #f0fdf4;
}

.btn-submit {
    padding: 10px 20px;
    background: #0f766e;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 700;
    color: white;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-submit:hover {
    background: #0d5c56;
}

.quill-editor-wrapper {
    background: white;
    border-radius: 6px;
    border: 1.5px solid #e2e8f0;
    max-width: 100%;
    overflow-x: hidden;
}

:deep(.ql-container.ql-snow) {
    border: none;
    min-height: 150px;
}

:deep(.ql-toolbar.ql-snow) {
    border: none;
    border-bottom: 1.5px solid #e2e8f0;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .create-wrap {
        gap: 14px;
        padding-bottom: 80px;
    }

    .form-card {
        padding: 14px 16px;
        gap: 12px;
    }

    .submit-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 99;
        margin: 0;
        border-radius: 0;
        box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.12);
        border-top: 1px solid #e2e8f0;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        padding: 10px 16px;
        display: flex;
        gap: 8px;
        justify-content: space-between;
    }

    .btn-cancel,
    .btn-draft,
    .btn-submit {
        flex: 1;
        padding: 10px 8px;
        font-size: 13px;
        text-align: center;
        justify-content: center;
        box-sizing: border-box;
    }
}
</style>
