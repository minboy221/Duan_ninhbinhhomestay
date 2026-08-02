<script setup>
import LandlordLayout from "@/Layouts/LandlordLayout.vue";
import { ref, watch } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import axios from "axios";
import { computed } from "vue";
//phần soạn thảo văn bản
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";
import { showWarning, showError } from "@/Utils/swal";
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
    current_people: 0,
    capacity: 1,
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
            form.current_people = detailsResponse.data.current_people ?? 0;
            form.capacity = detailsResponse.data.capacity ?? 1;
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
        } catch (error) {
            console.error(
                "Lỗi khi tải thông tin chi tiết và tiện ích phòng:",
                error,
            );
        } finally {
            isLoadingDetails.value = false;
        }
    },
);

//Phần xử lý tải ảnh lên
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
        showWarning("Lỗi GPS", "Trình duyệt của bạn không hỗ trợ chức năng định vị GPS.");
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

// Phần chức năng chuyển giọng nói sang văn bản
const recordingField = ref(null);
const SpeechRecognition =
    window.SpeechRecognition || window.webkitSpeechRecognition;

let recognition = null;

if (SpeechRecognition) {
    recognition = new SpeechRecognition();
    recognition.lang = "vi-VN";
    recognition.continuous = true;
    recognition.interimResults = false;

    //khi micro bắt được giọng nói
    recognition.onresult = (event) => {
        let transcript = "";
        for (let i = event.resultIndex; i < event.results.length; i++) {
            transcript += event.results[i][0].transcript;
        }

        if (recordingField.value) {
            const currentVal = form[recordingField.value] || "";
            form[recordingField.value] = (currentVal + " " + transcript).trim();
        }
    };

    recognition.onend = () => {
        recordingField.value = null;
    };

    // Báo lỗi hệ thống nếu có
    recognition.onerror = (event) => {
        console.error("Lỗi Speech API:", event.error);

        if (event.error === "not-allowed") {
            showWarning(
                "Quyền Microphone",
                "Vui lòng cấp quyền truy cập Microphone trên trình duyệt để sử dụng tính năng này!",
            );
        }
        recordingField.value = null;
    };
}

//phần bật tắt micro
const toggleSpeechToText = (fieldName) => {
    if (!recognition) {
        showWarning(
            "Trình duyệt không hỗ trợ",
            "Trình duyệt của bạn quá cũ hoặc không hỗ trợ Web Speech API. Vui lòng dùng Google Chrome hoặc Microsoft Edge mới nhất!",
        );
        return;
    }
    //nếu ô đó đang ghi âm -> bấm lần nữa để dừng
    if (recordingField.value === fieldName) {
        recognition.stop();
    } else {
        //nếu ô khác nghi âm thì dừng ô cũ
        if (recordingField.value) {
            recognition.stop();
        }
        //kích hoạt ghi âm cho ô mới
        recordingField.value = fieldName;
        recognition.start();
    }
};

//Set Format mặc định cho ghi âm
const formatGeneralText = (text) => {
    if (!text) return "";
    let formatted = text;

    //nếu có từ khoá giọng nói thì dịch sang ký tự
    formatted = formatted.replace(/xuống dòng|xuống hàng/gi, "\n");
    formatted = formatted.replace(/gạch đầu dòng/gi, "\n-");
    formatted = formatted.replace(/\schấm\s|\schấm$/gi, ".");
    formatted = formatted.replace(/\sphẩy\s|\sphẩy$/gi, ",");

    //chuẩn hoá khoảng trắng và dấu xuống dòng
    formatted = formatted.replace(/\r\n/g, "\n");
    formatted = formatted.replace(/[\t]+/g, "");

    // Quy tắc: Xóa khoảng trắng trước dấu câu, ép có đúng 1 khoảng trắng sau dấu câu
    formatted = formatted.replace(/\s*([.,?!;:])\s*/g, "$1 ");

    //tự động viết hoa đầu câu
    formatted = formatted.replace(
        /(^\s*|[.\n!?]\s*)(\p{L})/gu,
        (match, p1, p2) => {
            return p1 + p2.toUpperCase();
        },
    );
    // 5. Dọn dẹp khoảng trắng thừa ở đầu/cuối các dòng
    formatted = formatted.replace(/\s+\n/g, "\n").replace(/\n\s+/g, "\n");

    return formatted.trim();
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
                        <div class="mb-5">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                                    <i class="bi bi-pencil-square text-blue-600"></i>
                                    Tiêu đề tin đăng
                                </label>

                                <button type="button" @click="toggleSpeechToText('title')" class="w-11 h-11 sm:w-12 sm:h-12 rounded-full flex items-center justify-center
           transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-110" :class="recordingField === 'title'
            ? 'bg-gradient-to-br from-red-500 to-pink-500 text-white animate-pulse'
            : 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white'"
                                    :title="recordingField === 'title' ? 'Đang lắng nghe...' : 'Nhập bằng giọng nói'">
                                    <i class="bi text-xl" :class="recordingField === 'title'
                                        ? 'bi-mic-fill'
                                        : 'bi-mic'"></i>
                                </button>
                            </div>

                            <p class="text-xs text-gray-500 flex items-center gap-1">
                                <i class="bi bi-info-circle"></i>
                                Bạn có thể bấm micro và đọc tiêu đề thay vì nhập
                                bằng bàn phím.
                            </p>
                            <input type="text" v-model="form.title" @blur="
                                form.title = formatGeneralText(form.title)
                                " placeholder="Nhập tiêu đề tin đăng trọ..."
                                class="w-full text-sm rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500" />
                            <p v-if="form.errors.title"
                                class="text-red-500 font-medium text-xs mt-1.5 flex items-center gap-1">
                                {{ form.errors.title }}
                            </p>
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
                                        {{
                                            floor.name
                                                .toLowerCase()
                                                .startsWith("tầng")
                                                ? floor.name
                                                : "Tầng " + floor.name
                                        }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Phòng & Diện tích -->
                        <div class="form-row-2 mb-4">
                            <div class="form-group">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Chọn phòng trọ tiếp
                                    thị:</label>
                                <select v-model="form.room_id" class="w-full text-sm rounded-xl border-gray-300">
                                    <option value="">
                                        -- Vui lòng chọn phòng --
                                    </option>
                                    <option v-for="room in availableRooms" :key="room.id" :value="room.id">
                                        Phòng {{ room.room_number }}
                                    </option>
                                </select>
                                <p v-if="form.errors.room_id"
                                    class="text-red-500 font-medium text-xs mt-1.5 flex items-center gap-1">
                                    {{ form.errors.room_id }}
                                </p>
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

                        <div class="mt-4 mb-4" v-if="roomServices.length > 0">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Các tiện ích sẵn có của phòng này:
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                <div v-for="service in roomServices" :key="service.id"
                                    class="flex items-center gap-2 p-2 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                                    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
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
                        <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500"
                            v-else-if="form.room_id">
                            Phòng này hiện chưa được thiết lập tiện ích nào.
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Địa chỉ khu trọ / Phòng trọ:
                            </label>

                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input type="text" v-model="form.address"
                                        placeholder="Số nhà, tên đường, phường/xã, quận/huyện..."
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                </div>
                            </div>

                            <div v-if="form.latitude && form.longitude" class="mt-1.5 text-xs text-gray-500 flex gap-4">
                                <span><strong>Vĩ độ (Lat):</strong>
                                    {{ form.latitude }}</span>
                                <span><strong>Kinh độ (Lng):</strong>
                                    {{ form.longitude }}</span>
                            </div>

                            <div v-if="form.errors.address" class="text-red-500 text-xs mt-1">
                                {{ form.errors.address }}
                            </div>
                        </div>
                        <div class="mb-6">
                            <!-- TẦNG 1: Tiêu đề và Nút bấm giọng nói cân bằng 2 bên -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="bi bi-card-text text-indigo-600"></i>
                                        Mô tả chi tiết bài đăng
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Viết càng chi tiết càng giúp tăng tỷ lệ
                                        cho thuê thành công.
                                    </p>
                                </div>

                                <!-- Nút ghi âm giọng nói -->
                                <button type="button" @click="toggleSpeechToText('description')" class="w-11 h-11 sm:w-12 sm:h-12 rounded-full flex items-center justify-center
           shadow-lg transition-all duration-300 hover:scale-110 self-start sm:self-center" :class="recordingField === 'description'
            ? 'bg-gradient-to-br from-red-500 to-pink-500 text-white animate-pulse'
            : 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white'" :title="recordingField === 'description'
                ? 'Đang ghi âm...'
                : 'Nhập bằng giọng nói'">
                                    <i class="bi text-xl" :class="recordingField === 'description'
                                        ? 'bi-mic-fill'
                                        : 'bi-mic'"></i>
                                </button>
                            </div>

                            <!-- TẦNG 2: Khu vực hiển thị Trạng thái và Phần Word (QuillEditor) -->
                            <div
                                class="editor-card relative border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                <!-- Banner thông báo khi bật ghi âm -->
                                <div v-if="recordingField === 'description'"
                                    class="recording-banner p-2.5 bg-red-50 text-red-600 text-xs font-medium flex items-center gap-2 border-b border-red-100">
                                    <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                                    🎙️ Trợ lý ảo đang lắng nghe... Hãy nói rõ
                                    ràng, bạn có thể nói "xuống dòng" hoặc
                                    "chấm", "phẩy".
                                </div>

                                <QuillEditor v-model:content="form.description" contentType="html" theme="snow" @blur="
                                    form.description = formatHtmlContent(
                                        form.description,
                                    )
                                    "
                                    placeholder="Ví dụ: Phòng rộng 25m², có điều hòa, giường tủ, giờ giấc tự do, không chung chủ..." />
                            </div>

                            <!-- Hiển thị lỗi từ Backend Laravel -->
                            <p v-if="form.errors.description"
                                class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                                <i class="bi bi-exclamation-circle-fill"></i>
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
                                <label class="form-label">Giá thuê (đ/tháng) *</label>
                                <input type="number" :value="roomDetails?.price || ''" disabled class="form-input"
                                    placeholder="3000000" />
                                <span class="form-hint" v-if="roomDetails?.price">
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

                        <label class="img-upload-area transition-all" :class="form.errors.images
                            ? 'border-red-500 bg-red-50/30 hover:bg-red-50/50'
                            : ''
                            ">
                            <input type="file" multiple accept="image/*" @change="handleFileChange"
                                style="display: none" />
                            <i class="bi bi-cloud-upload" :class="form.errors.images ? 'text-red-500' : ''
                                "></i>
                            <span :class="form.errors.images
                                ? 'text-red-700 font-medium'
                                : ''
                                ">Nhấn để chọn ảnh</span>
                            <span class="img-hint">JPG, PNG tối đa 5MB mỗi ảnh</span>
                        </label>

                        <p v-if="form.errors.images"
                            class="text-red-500 font-semibold text-xs mt-2 flex items-center gap-1.5 animate-pulse">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            {{ form.errors.images }}
                        </p>

                        <div class="mt-2 space-y-1" v-if="
                            Object.keys(form.errors).some((k) =>
                                k.startsWith('images.'),
                            )
                        ">
                            <div v-for="(error, key) in form.errors" :key="key">
                                <p v-if="key.startsWith('images.')"
                                    class="text-red-500 font-medium text-xs flex items-center gap-1.5">
                                    <i class="bi bi-x-circle-fill text-[10px]"></i>
                                    {{ error }}
                                </p>
                            </div>
                        </div>

                        <div class="img-preview-grid mt-4" v-if="form.images.length > 0">
                            <div v-for="(src, i) in form.images" :key="i" class="img-preview-item">
                                <img :src="getObjectUrl(src)" :alt="`Ảnh ${i + 1}`" />
                                <button class="img-remove" @click="removeImage(i)">
                                    <i class="bi bi-x"></i>
                                </button>
                                <span v-if="i === 0" class="img-main-badge">Ảnh chính</span>
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
                            <iframe v-if="mapUrl" :src="mapUrl" width="100%" height="250"
                                style="border: 0; border-radius: 12px" loading="lazy">
                            </iframe>

                            <div v-else class="map-placeholder">
                                <i class="bi bi-map"></i>
                                <span>Nhập địa chỉ hoặc lấy GPS để xem bản
                                    đồ</span>
                            </div>
                        </div>
                        <input v-model="form.address" class="form-input mt-10"
                            placeholder="Nhập địa chỉ để tìm trên bản đồ..." />
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
                        <div v-if="roomDetails?.services?.length" class="flex flex-wrap gap-2 mt-3">
                            <span v-for="service in roomDetails.services" :key="service.id"
                                class="px-2 py-1 rounded-full bg-green-50 text-green-700 text-xs">
                                {{ service.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="submit-bar">
                <Link :href="route('landlord.listings.index')" class="btn-cancel">Hủy</Link>
                <button type="button" class="btn-draft" @click="submitForm('draft')">
                    <i class="bi bi-save"></i> Lưu Nháp
                </button>
                <button type="button" class="btn-submit" @click="submitForm('publish')">
                    <i class="bi bi-send-fill"></i> Đăng Tin
                </button>
            </div>
        </div>
    </LandlordLayout>
</template>

<style scoped>
@import "../../../css/landlord_listings_create.css";
</style>
