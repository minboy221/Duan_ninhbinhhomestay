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
const availableFloors = ref(
    initialHouse 
        ? initialHouse.floors.filter(floor => 
            floor.rooms && floor.rooms.some(room => room.boarding_house_id === initialHouse.id)
          )
        : []
);
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
    current_people: props.post.room?.current_people ?? 0,
    capacity: props.post.room?.capacity ?? 1,
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
        const belongsToSelectedHouse = selectedHouse.value && r.boarding_house_id === selectedHouse.value.id;
        if(!belongsToSelectedHouse) return false;
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
    availableFloors.value = newHouse 
        ? newHouse.floors.filter(floor => 
            floor.rooms && floor.rooms.some(room => room.boarding_house_id === newHouse.id)
          )
        : [];
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
            const belongsToSelectedHouse = selectedHouse.value && r.boarding_house_id === selectedHouse.value.id;;
            if(!belongsToSelectedHouse) return false;
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
            form.current_people = detailsResponse.data.current_people ?? 0;
            form.capacity = detailsResponse.data.capacity ?? 1;
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
const handleFileChange = async (e) => {
    const newFiles = Array.from(e.target.files);
    // Nén song song tất cả các ảnh mới chọn bằng hàm compressImage
    const compressedFiles = await Promise.all(
        newFiles.map(file => compressImage(file))
    );
    form.images = [...form.images, ...compressedFiles];
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

// Hàm nén ảnh bằng HTML5 Canvas trực tiếp ở trình duyệt
function compressImage(file, { maxWidth = 1200, maxHeight = 1200, quality = 0.7 } = {}) {
    return new Promise((resolve, reject) => {
        // Chỉ nén các file thực sự là hình ảnh
        if (!file.type.startsWith("image/")) {
            return resolve(file);
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement("canvas");
                let width = img.width;
                let height = img.height;

                // Tính toán tỷ lệ co giãn ảnh
                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        if (blob) {
                            // Tạo lại đối tượng File mới đã nén chất lượng (quality = 70%)
                            const compressedFile = new File([blob], file.name, {
                                type: file.type,
                                lastModified: Date.now(),
                            });
                            resolve(compressedFile);
                        } else {
                            resolve(file); // Nếu lỗi nén thì trả về file gốc dự phòng
                        }
                    },
                    file.type,
                    quality
                );
            };
        };
        reader.onerror = (error) => reject(error);
    });
}
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
                            <input v-model="form.title" class="form-input"
                                placeholder="VD: Phòng trọ sạch sẽ thoáng mát trung tâm..." />
                            <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">
                                {{ form.errors.title }}
                            </div>
                        </div>

                        <!-- Nhà trọ & Tầng (Chọn trước) -->
                        <div class="form-row-2 mb-4">
                            <div class="form-group">
                                <label class="form-label"> Nhà trọ </label>
                                <select v-model="selectedHouse" class="form-input">
                                    <option :value="null">Chọn nhà trọ</option>
                                    <option v-for="house in boardingHouses" :key="house.id" :value="house">
                                        {{ house.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label"> Tầng </label>
                                <select v-model="selectedFloor" class="form-input">
                                    <option :value="null">Chọn tầng</option>
                                    <option v-for="floor in availableFloors" :key="floor.id" :value="floor">
                                        {{ floor.name.toLowerCase().startsWith('tầng') ? floor.name : 'Tầng ' +
                                            floor.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Phòng & Diện tích -->
                        <div class="form-row-2 mb-4">
                            <div class="form-group">
                                <label class="form-label">Phòng *</label>
                                <select v-model="form.room_id" class="form-input">
                                    <option value="">Chọn Phòng</option>
                                    <option v-for="room in availableRooms" :key="room.id" :value="room.id">
                                        Phòng {{ room.room_number }}
                                    </option>
                                </select>
                                <div v-if="form.errors.room_id" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.room_id }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Diện tích (m²) *</label>
                                <input :value="roomDetails?.area || ''" disabled
                                    class="form-input bg-gray-50 text-gray-500" />
                            </div>
                        </div>

                        <!-- Số người đang có / Sức chứa tối đa (Chỉ hiện Số người đang ở khi phòng đã có người > 0) -->
                        <div class="mb-4" :class="form.current_people > 0 ? 'form-row-2' : ''">
                            <div class="form-group" v-if="form.current_people > 0">
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Số người đang ở trong phòng <span class="text-xs text-gray-400 font-normal">(Mặc định của phòng)</span>
                                </label>
                                <input type="number" :value="form.current_people" disabled readonly class="w-full text-sm rounded-xl border-gray-300 bg-gray-100 text-gray-600 font-bold cursor-not-allowed" />
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-bold text-gray-700 mb-1">
                                    Sức chứa tối đa (Số người tổng) <span class="text-xs text-gray-400 font-normal">(Mặc định của phòng)</span>
                                </label>
                                <input type="number" :value="form.capacity" disabled readonly class="w-full text-sm rounded-xl border-gray-300 bg-gray-100 text-gray-600 font-bold cursor-not-allowed" />
                            </div>
                        </div>

                        <!-- Các tiện ích sẵn có của phòng này -->
                        <div class="mb-4" v-if="roomServices.length > 0">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Các tiện ích sẵn có của phòng này:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div v-for="service in roomServices" :key="service.id"
                                    class="flex items-center gap-2 p-2 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                                    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>{{ service.name }}</span>
                                    <span v-if="service.price > 0" class="text-xs text-gray-500">
                                        ({{
                                            new Intl.NumberFormat(
                                                "vi-VN",
                                            ).format(service.price)
                                        }}đ)
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500"
                            v-else-if="form.room_id">
                            Phòng này hiện chưa được thiết lập tiện ích nào.
                        </div>

                        <!-- Chỉ hiển thị thông tin địa chỉ khi đã chọn tầng và phòng -->
                        <div v-if="selectedFloor && form.room_id" class="space-y-4 mb-4">


                            <!-- Thông tin địa chỉ đầy đủ từ khu trọ -->
                            <div class="bg-blue-50/40 border border-blue-100 rounded-lg p-3.5">
                                <label
                                    class="block text-xs font-bold text-blue-800 mb-1.5 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="bi bi-info-circle"></i> Địa chỉ phòng trọ (Tự động cập nhật)
                                </label>
                                <p class="text-sm text-gray-700 font-medium">
                                    {{ form.address }}
                                </p>
                                <div v-if="form.latitude && form.longitude"
                                    class="mt-2 text-xs text-gray-500 flex gap-4 border-t border-blue-100/50 pt-2">
                                    <span><i class="bi bi-compass text-gray-400"></i> <strong>Vĩ độ (Lat):</strong> {{
                                        form.latitude }}</span>
                                    <span><i class="bi bi-compass text-gray-400"></i> <strong>Kinh độ (Lng):</strong> {{
                                        form.longitude }}</span>
                                </div>
                                <div v-if="form.errors.address" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.address }}
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Mô tả chi tiết bài đăng:
                            </label>
                            <div class="quill-editor-wrapper">
                                <QuillEditor v-model:content="form.description" contentType="html" theme="snow"
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
                                    ]" />
                            </div>
                            <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">
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
                                <label class="form-label">Giá thuê (đ/tháng) *</label>
                                <input type="number" :value="roomDetails?.price || ''" disabled
                                    class="form-input bg-gray-50 text-gray-500" />
                                <span class="form-hint" v-if="roomDetails?.price">
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
                            <input type="file" multiple accept="image/*" @change="handleFileChange"
                                style="display: none" />
                            <i class="bi bi-cloud-upload"></i>
                            <span>Chọn thêm ảnh mới</span>
                            <span class="img-hint">JPG, PNG tối đa 2MB mỗi ảnh</span>
                        </label>

                        <!-- Ảnh hiện tại của bài viết -->
                        <div v-if="form.existing_images.length > 0" class="space-y-2">
                            <h4 class="text-xs font-bold text-slate-500">
                                Ảnh hiện tại:
                            </h4>
                            <div class="img-preview-grid">
                                <div v-for="(src, i) in form.existing_images" :key="'existing-' + i"
                                    class="img-preview-item">
                                    <img :src="src" alt="Ảnh hiện tại" />
                                    <button type="button" class="img-remove" @click="removeExistingImage(i)">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Ảnh mới chọn thêm -->
                        <div v-if="form.images.length > 0" class="space-y-2 mt-4">
                            <h4 class="text-xs font-bold text-slate-500">
                                Ảnh mới chọn thêm:
                            </h4>
                            <div class="img-preview-grid">
                                <div v-for="(src, i) in form.images" :key="'new-' + i" class="img-preview-item">
                                    <img :src="getObjectUrl(src)" alt="Ảnh mới thêm" />
                                    <button type="button" class="img-remove" @click="removeNewImage(i)">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.images" class="text-red-500 text-xs mt-1">
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
                            <iframe v-if="form.latitude && form.longitude"
                                :src="`https://maps.google.com/maps?q=${form.latitude},${form.longitude}&z=15&output=embed`"
                                width="100%" height="250" style="border: 0; border-radius: 12px"
                                loading="lazy"></iframe>
                            <div v-else class="map-placeholder"> <i class="bi bi-map"></i> <span>Nhập địa chỉ để định vị
                                    bản
                                    đồ</span> </div>
                        </div>
                    </div> <!-- Xem trước nội dung (Preview) -->
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
                            <span>Diện tích:
                                {{ roomDetails?.area || 0 }} m²</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thanh chức năng gửi Form -->
            <div class="submit-bar">
                <Link :href="route('landlord.listings.index')" class="btn-cancel">Hủy</Link>
                <button type="button" class="btn-draft" @click="submitForm('draft')">
                    <i class="bi bi-save"></i> Lưu Nháp
                </button>
                <button type="button" class="btn-submit" @click="submitForm('publish')">
                    <i class="bi bi-send-fill"></i> Cập Nhật
                </button>
            </div>
        </div>
    </LandlordLayout>
</template>

<style scoped>
@import "../../../css/landlord_listings_edit.css";
</style>
