<script setup>
import MainLayout from "@/Layouts/MainLayout.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import axios from "axios";
import { showSuccess, showWarning, showConfirm } from "@/Utils/swal";
import LandlordRankCard from "@/Components/LandlordRankCard.vue";

const props = defineProps({
    reportable_type: "Room",
    room: { type: Object, required: true },
    similarRooms: { type: Array, default: () => [] },
    reasons: { type: Array, default: () => [] },
    hasReportPermission: { type: Boolean, default: false },
});

const currentRoomStatus = ref(props.room.status);

//trạng thái hiển thị modal báo cáo
const showReportModal = ref(false);

const page = usePage();
const user = computed(() => page.props.auth?.user);

const canReview = computed(() => {
    if (!user.value) return false;
    if (user.value.role === 'admin') return true;
    if (user.value.id === props.room.boardingHouse?.user_id) return true;
    return props.hasReportPermission;
});

const isNoticeSender = computed(() => {
    if (!user.value) return false;
    return user.value.role === 'admin' || user.value.id === props.room.boardingHouse?.user_id;
});

const reviewForm = useForm({
    rating: 5,
    comment: ''
});

const submitDirectReview = () => {
    reviewForm.post(route('rooms.direct-review', props.room.room_id), {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset();
            showSuccess('Cảm ơn bạn đã gửi đánh giá!');
        }
    });
};

//State lưu trữ danh sách các giờ đã có người chọn của phòng trong ngày đang chọn
const disabledSlots = ref([]);
const availablSlots = ref([]);

// Image carousel state
const activeImageIndex = ref(0);
const roomImages = computed(() => {
    const imgs =
        props.room.post_images?.length > 0
            ? props.room.post_images
            : props.room.images?.length > 0
                ? props.room.images
                : null;
    if (imgs && Array.isArray(imgs) && imgs.length > 0) return imgs;
    return ["/anh/banner_tro.png"];
});

const activeImage = computed(
    () => roomImages.value[activeImageIndex.value] || "/anh/banner_tro.png",
);

function nextImage() {
    activeImageIndex.value =
        (activeImageIndex.value + 1) % roomImages.value.length;
}

function prevImage() {
    activeImageIndex.value =
        (activeImageIndex.value - 1 + roomImages.value.length) %
        roomImages.value.length;
}

// Similar rooms slider state
const sliderRef = ref(null);

function slideLeft() {
    if (sliderRef.value) {
        const item = sliderRef.value.querySelector(".item_tindang");
        if (item) {
            const itemWidth = item.offsetWidth;
            sliderRef.value.scrollBy({
                left: -(itemWidth + 20),
                behavior: "smooth",
            });
        }
    }
}

function slideRight() {
    if (sliderRef.value) {
        const item = sliderRef.value.querySelector(".item_tindang");
        if (item) {
            const itemWidth = item.offsetWidth;
            sliderRef.value.scrollBy({
                left: itemWidth + 20,
                behavior: "smooth",
            });
        }
    }
}

// Split amenities
const roomAmenities = computed(() => {
    if (!props.room.amenities) return [];
    if (Array.isArray(props.room.amenities)) return props.room.amenities;
    return props.room.amenities
        .split(",")
        .map((s) => s.trim())
        .filter(Boolean);
});

// Timezone-safe today date string
const getTodayDateStr = () => {
    const today = new Date();
    const offset = today.getTimezoneOffset();
    const localDate = new Date(today.getTime() - offset * 60 * 1000);
    return localDate.toISOString().split("T")[0];
};

const getDaysOfWeek = () => {
    const list = [];
    // Thêm lại 2 mảng này để tránh bị lỗi undefined khi lấy thứ
    const days = ["CN", "T2", "T3", "T4", "T5", "T6", "T7"];
    const fullDays = [
        "Chủ Nhật",
        "Thứ Hai",
        "Thứ Ba",
        "Thứ Tư",
        "Thứ Năm",
        "Thứ Sáu",
        "Thứ Bảy",
    ];
    const now = new Date();

    for (let i = 0; i < 7; i++) {
        const d = new Date(now.getTime());
        d.setDate(now.getDate() + i);

        const offset = d.getTimezoneOffset();
        const localDate = new Date(d.getTime() - offset * 60 * 1000);
        const valStr = localDate.toISOString().split("T")[0];

        let dayLabel = days[d.getDay()];
        if (i === 0) dayLabel = "Hôm nay";
        else if (i === 1) dayLabel = "Ngày mai";

        list.push({
            dayName: dayLabel,
            fullDayName: fullDays[d.getDay()],
            dateNum: d.getDate(),
            monthNum: d.getMonth() + 1,
            yearNum: d.getFullYear(),
            value: valStr,
        });
    }
    return list;
};

const dateList = computed(() => getDaysOfWeek());

const isTimeSlotDisabled = (slot) => {
    //kiểm tra giờ này nằm trong danh sách bận từ backend
    if (disabledSlots.value.includes(slot)) {
        return true;
    }
    //kiểm tra giờ quá khứ nếu là ngày hôm nay
    if (form.date === getTodayDateStr()) {
        const now = new Date();
        const [slotHour, slotMin] = slot.split(":").map(Number);
        const currentHour = now.getHours();
        const currentMin = now.getMinutes();

        if (slotHour < currentHour) return true;
        if (slotHour === currentHour && slotMin <= currentMin) return true;
    }
    return false;
};

//Hàm độc lập call API tải danh sách giờ mà use đã đặt lịch theo ngày
const fetchBookedSlots = async (dateVal) => {
    try {
        const postId = props.room.id; // props.room.id chính là id của RoomPost
        const response = await axios.get(
            `/chitiettro/${postId}/booked_slots?date=${dateVal}`,
        );
        disabledSlots.value = response.data.booked_slots || []; // mảng trả về dữ liệu
        availablSlots.value = response.data.available_slots || [];
    } catch (error) {
        console.error("Không thể lấy danh sách khung giờ trùng:", error);
    }
};

async function selectDate(dateVal) {
    form.date = dateVal;
    //tải lại lịch bận cho ngày mới chọn
    await fetchBookedSlots(dateVal);

    if (form.time && isTimeSlotDisabled(form.time)) {
        form.time = "";
    }
}

function selectTime(timeVal) {
    if (isTimeSlotDisabled(timeVal)) return;
    form.time = timeVal;
}

const bookingPreview = computed(() => {
    if (!form.date && !form.time) {
        return "Vui lòng chọn ngày và giờ cụ thể để đặt lịch xem phòng.";
    }
    let dateStr = "";
    if (form.date) {
        const d = new Date(form.date);
        dateStr = `ngày ${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
    }
    const timeStr = form.time ? `vào lúc ${form.time}` : "trong ngày";
    return `Bạn đăng ký xem phòng: ${timeStr} ${dateStr}`;
});

const formatPrice = (price) => {
    const p = parseFloat(price);
    if (p >= 1000000) {
        return (p / 1000000).toFixed(1).replace(".0", "") + " Triệu/Tháng";
    }
    return p.toLocaleString("vi-VN") + " đ/Tháng";
};

// Booking modal state
const showBookingModal = ref(false);

const form = useForm({
    date: getTodayDateStr(),
    time: "",
    note: "",
});

//trạng thái ẩn/ hiển thông báo khi đặt lịch
const ShowSuccessAlert = ref(false);
const showErrorAlert = ref(false);
//theo dõi dữ liệu flash từ inertia gửi về để bật thông báo nổi và tắt sau 4s
watch(
    () => page.props.flash?.success,
    (newVal) => {
        if (newVal) {
            showSuccessAlert.value = true;
            setTimeout(() => {
                showSuccessAlert.value = false;
            }, 4000);
        }
    },
    { immediate: true },
);

watch(
    () => page.props.flash?.error,
    (newVal) => {
        if (newVal) {
            showErrorAlert.value = true;
            setTimeout(() => {
                showErrorAlert.value = false;
            }, 4000);
        }
    },
    { immediate: true },
);

async function openBookingModal() {
    if (!user.value) {
        const confirmed = await showConfirm(
            "Yêu cầu đăng nhập",
            "Bạn cần đăng nhập tài khoản khách thuê để đặt lịch xem phòng.",
            "Đăng nhập",
            "Đóng",
        );
        if (confirmed) {
            window.location.href = route("login");
        }
        return;
    }
    if (user.value.role === "landlord") {
        showWarning(
            "Thông báo",
            "Tài khoản chủ trọ không thể thực hiện chức năng đặt lịch xem phòng.",
        );
        return;
    }
    //tải danh sách lịch trùng ngay cho ngày mặc định
    await fetchBookedSlots(form.date);
    showBookingModal.value = true;
}

//hàm xử lý khi nhấp vào sđt của chủ trọ
async function handlePhoneClick(e) {
    if (!user.value) {
        e.preventDefault();

        const confirmed = await showConfirm(
            "Yêu cầu đăng nhập",
            "Bạn cần đăng nhập tài khoản để xem số điện thoại của chủ trọ.",
            "Đăng nhập",
            "Hủy",
        );
        if (confirmed) {
            window.location.href = route("login");
        }
    }
}

function submitBooking() {
    form.post(route("rooms.book", props.room.id), {
        preserveScroll: true,
        onSuccess: (page) => {
            const flash = page.props.flash;
            if (flash && flash.error) {
                showWarning("Không thể đặt lịch", flash.error);
                return;
            }
            // Chỉ khi đặt lịch thành công
            showBookingModal.value = false;
            form.reset();
            showSuccess(
                "Thành công",
                "Gửi yêu cầu đặt lịch hẹn thành công! Vui lòng đợi chủ trọ phản hồi.",
            );
        },
        onError: (errors) => {
            const firstErr = Object.values(errors)[0];
            showWarning(
                "Lỗi nhập dữ liệu",
                firstErr || "Vui lòng kiểm tra lại thông tin gửi đi.",
            );
        },
    });
}

//Form báo cáo vi phạm
const reportForm = useForm({
    reportable_type: "Room",
    reportable_id: props.room.room_id,
    reason: "",
    description: "",
    resolve_type: "direct",
    evidence_images: [],
});

//xử lý khi user ấn nút báo cáo
function reportListing() {
    if (!usePage().props.auth.user) {
        showConfirm(
            "Yêu cầu đăng nhập",
            "Bạn cần đăng nhập tài khoản khách hàng thuê để gửi báo cáo vi phạm.",
            "Đăng nhập",
            "Huỷ",
        ).then((confirmed) => {
            if (confirmed) {
                window.location.href = route("login");
            }
        });
        return;
    }
    //chặn tài khoản chủ trọ / admin tự báo cáo
    if (user.value.role === "landlord" || user.value.role === "admin") {
        showWarning(
            "Thông báo",
            "Tài khoản quản trị hoặc chủ trọ không thể thực hiện báo cáo vi phạm.",
        );
        return;
    }
    //ràng buộc báo cáo (Phải từng thuê hoặc đặt lịch xem phòng ở đây)
    if (!props.hasReportPermission) {
        showWarning(
            "Không thể báo cáo",
            "Bạn chỉ được quyền báo cáo phòng hoặc cơ sở này nếu đã từng làm hợp đồng thuê hoặc đã đặt lịch hẹn xem phòng thực tế tại đây.",
        );
        return;
    }

    reportForm.reason = "";
    reportForm.description = "";
    reportForm.evidence_images = [];
    showReportModal.value = true;
}
// Hàm nén ảnh bằng HTML5 Canvas trực tiếp ở trình duyệt
function compressImage(
    file,
    { maxWidth = 1200, maxHeight = 1200, quality = 0.7 } = {},
) {
    return new Promise((resolve, reject) => {
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
                            const compressedFile = new File([blob], file.name, {
                                type: file.type,
                                lastModified: Date.now(),
                            });
                            resolve(compressedFile);
                        } else {
                            resolve(file);
                        }
                    },
                    file.type,
                    quality,
                );
            };
        };
        reader.onerror = (error) => reject(error);
    });
}

//khi chọn ảnh minh chứng
const previewUrls = ref([]);

const handleEvidenceChange = async (e) => {
    const files = Array.from(e.target.files);

    // Nén song song toàn bộ ảnh bằng chứng
    const compressed = await Promise.all(
        files.map((file) => compressImage(file)),
    );

    reportForm.evidence_images = compressed;
    previewUrls.value = compressed.map((file) => URL.createObjectURL(file));
};

const removeEvidenceImage = (index) => {
    reportForm.evidence_images.splice(index, 1);
    previewUrls.value.splice(index, 1);
};

//gửi báo cáo lên server
const submitReport = () => {
    reportForm.post(route("reports.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            const flash = page.props.flash;
            if (flash && flash.error) {
                showWarning("Không thể gửi", flash.error);
                return;
            }
            showReportModal.value = false;
            previewUrls.value = [];
            showSuccess(
                "Đã gửi báo cáo vi phạm!",
                reportForm.resolve_type === "direct"
                    ? "Yêu cầu tự giải quyết khiếu nại đã gửi tới chủ trọ!"
                    : "Hệ thống đã ghi nhận báo cáo của bạn và tiến hành xác minh thông tin phòng trọ này trong thời gian sớm nhất!",
            );
        },
        onError: (errors) => {
            const firstErr = Object.values(errors)[0];
            showWarning(
                "Lỗi nhập liệu",
                firstErr || "Vui lòng kiểm tra lại thông tin gửi đi.",
            );
        },
    });
};

onMounted(() => {
    window.Echo.channel("rooms").listen("RoomStatusUpdated", (e) => {
        if (e.roomId === props.room.id) {
            currentRoomStatus.value = e.status;
        }
    });
});
onUnmounted(() => {
    window.Echo.leaveChannel("rooms");
});
</script>

<template>

    <Head :title="`Xem Chi Tiết Phòng ${room.room_number} | Ninh Bình HomeStay`" />
    <MainLayout>
        <!-- điều hướng -->
        <div class="dieuhuong">
            <div class="baodieuhuong">
                <Link :href="route('home')">Trang Chủ</Link> /
                <Link :href="route('timtro')">Tìm Phòng Trọ</Link> /
                <span>Xem Chi Tiết Phòng</span>
            </div>
        </div>

        <!-- Flash messages(thông báo góc) -->
        <div class="flash-alert-container">
            <div v-if="$page.props.flash.success && showSuccessAlert" class="flash-success-alert">
                <i class="bi bi-check-circle-fill"></i>
                <span class="flash-message">{{
                    $page.props.flash.success
                    }}</span>
                <button type="button" @click="showSuccessAlert = false" class="flash-close-btn">
                    &times;
                </button>
                <div class="flash-progress"></div>
            </div>

            <div v-if="$page.props.flash.error && showErrorAlert" class="flash-error-alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span class="flash-message">{{ $page.props.flash.error }}</span>
                <button type="button" @click="showErrorAlert = false" class="flash-close-btn">
                    &times;
                </button>
                <div class="flash-progress"></div>
            </div>
        </div>

        <!-- phần chi tiết phòng -->
        <div class="layout">
            <section class="room_detail">
                <div class="container">
                    <div class="gallery">
                        <!-- Ảnh lớn -->
                        <div class="main-image">
                            <button v-if="roomImages.length > 1" @click="prevImage" class="prev">
                                &#10094;
                            </button>
                            <img id="currentImage" :src="activeImage" alt="Room Image" />
                            <button v-if="roomImages.length > 1" @click="nextImage" class="next">
                                &#10095;
                            </button>
                        </div>

                        <!-- Thumbnail -->
                        <div class="thumbnails" v-if="roomImages.length > 1">
                            <img v-for="(img, idx) in roomImages" :key="idx" :src="img" :class="[
                                'thumb',
                                activeImageIndex === idx ? 'active' : '',
                            ]" @click="activeImageIndex = idx" alt="Thumbnail" />
                        </div>
                    </div>

                    <div class="detail_tro">
                        <div class="theloai">
                            <i class="bi bi-award"></i>
                            <span>{{
                                room.status === "available"
                                    ? "ĐANG TRỐNG / ĐÃ KIỂM CHỨNG"
                                    : "ĐÃ ĐƯỢC KIỂM CHỨNG"
                            }}</span>
                        </div>
                        <div v-if="room.current_people > 0 || room.status === 'rented'" class="theloai" style="background: #ecfdf5; color: #059669; border-color: #a7f3d0; margin-left: 6px;">
                            <i class="bi bi-person-check-fill"></i>
                            <span>ĐÃ CÓ {{ room.current_people || 1 }} NGƯỜI Ở</span>
                        </div>
                        <div class="tieude">
                            <h2>
                                {{
                                    room.title ||
                                    `Phòng ${room.room_number} - ${room.boardingHouse?.name || "Phòng trọ dịch vụ"}`
                                }}
                            </h2>
                        </div>
                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>Địa điểm:
                                {{
                                    room.address ||
                                    room.boardingHouse?.address_detail ||
                                    "Ninh Bình"
                                }}</span>
                        </div>
                        <div class="infor_tro">
                            <div class="price_tro">
                                <p>{{ formatPrice(room.price) }}</p>
                            </div>
                            <div class="trangthai">
                                <p>
                                    Diện tích: {{ parseFloat(room.area) }} m² ·
                                    Sức chứa: {{ room.capacity }} người
                                </p>
                            </div>
                        </div>
                        <div class="thongtin_tro">
                            <h4>Thông tin mô tả:</h4>
                            <div v-html="room.description ||
                                room.boardingHouse?.description ||
                                'Chưa có thông tin mô tả chi tiết từ chủ nhà.'
                                "></div>

                            <!-- Phần dịch vụ đi kèm -->
                            <div class="tienich" v-if="room.services && room.services.length > 0">
                                <div v-for="srv in room.services" :key="srv.id" class="baotienich">
                                    <i :class="[
                                        'bi',
                                        srv.icon ||
                                        'bi-lightning-charge-fill',
                                    ]"></i>

                                    <span>
                                        {{ srv.name }}
                                    </span>
                                </div>
                            </div>

                            <div class="tienich" v-else>
                                <div class="baotienich">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>Chưa cập nhật dịch vụ</span>
                                </div>
                            </div>

                            <div class="bando">
                                <h2>Vị trí &amp; Bản Đồ</h2>
                                <iframe
                                    :src="`https://maps.google.com/maps?q=${encodeURIComponent(room.address || room.boardingHouse?.address_detail || 'Ninh Bình')}&t=&z=15&ie=UTF8&iwloc=&output=embed`"
                                    width="100%" height="350" style="border: 0; border-radius: 12px" allowfullscreen=""
                                    loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="info_chutro">
                <div class="baochutro">
                    <div class="avatar1">
                        <div class="avatar-img">
                            <img :src="room.boardingHouse?.user?.avatar
                                ? '/storage/' +
                                room.boardingHouse.user.avatar
                                : '/anh/banner.png'
                                " alt="Avatar" />
                            <span :class="[
                                'status1',
                                room.boardingHouse?.user?.is_online
                                    ? 'online'
                                    : 'offline',
                            ]"></span>
                        </div>
                        <div class="name_chutro">
                            <h3>
                                {{
                                    room.boardingHouse?.user?.name || "Chủ trọ"
                                }}
                                <span style="
                                        font-size: 12px;
                                        font-weight: normal;
                                        display: block;
                                        margin-bottom: 10px;
                                    " :style="{
                                        color: room.boardingHouse?.user
                                            ?.is_online
                                            ? '#22c55e'
                                            : '#94a3b8',
                                    }">
                                    ({{
                                        room.boardingHouse?.user?.is_online
                                            ? "Đang hoạt động"
                                            : "Ngoại tuyến"
                                    }})
                                </span>
                            </h3>

                            <div class="kiem_chung">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Chủ Trọ Đã Xác Thực</span>
                            </div>
                        </div>
                    </div>

                    <div class="content_chutro">
                        <div class="phone">
                            <a class="btn_content" :href="user
                                ? `tel:${room.boardingHouse?.user?.phone}`
                                : '#'
                                " @click="handlePhoneClick">
                                <i class="bi bi-telephone"></i>
                                <span>{{
                                    user
                                        ? room.boardingHouse?.user?.phone
                                        : room.boardingHouse?.user?.phone
                                            ? room.boardingHouse?.user?.phone.substring(
                                                0,
                                                6,
                                            ) + "xxxx "
                                            : "086293xxxx"
                                }}</span>
                            </a>
                        </div>
                        <div class="nhantin_chutro">
                            <!-- Nút hiển thị cho khách thuê bình thường hoặc chưa đăng nhập -->
                            <button v-if="!user || user.role !== 'landlord'" @click="openBookingModal" class="btn_mess"
                                style="
                                    border: none;
                                    width: 100%;
                                    text-align: center;
                                    cursor: pointer;
                                ">
                                <i class="bi bi-calendar-check-fill"></i>
                                <span>Đặt Lịch Hẹn</span>
                            </button>

                            <!-- Nút bị vô hiệu hóa khi tài khoản là Chủ trọ -->
                            <button v-else class="btn_mess" style="
                                    border: none;
                                    width: 100%;
                                    text-align: center;
                                    cursor: not-allowed;
                                " disabled>
                                <i class="bi bi-calendar-x"></i>
                                <span> Không Thể Đặt Lịch</span>
                            </button>
                        </div>

                        <div class="warning">
                            <button @click="reportListing" class="btn_waring" style="
                                    border: none;
                                    width: 100%;
                                    text-align: center;
                                    cursor: pointer;
                                    color: #fff;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    gap: 8px;
                                ">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>Báo Xấu</span>
                            </button>
                        </div>
                    </div>

                    <!-- Safety Note -->
                    <div class="luu_y">
                        <h4 style="
                                font-weight: 700;
                                font-size: 14px;
                                margin-bottom: 8px;
                                display: flex;
                                align-items: center;
                                gap: 6px;
                                color: #1f2937;
                            ">
                            Lưu ý an toàn
                        </h4>
                        <div style="
                                display: flex;
                                flex-direction: column;
                                gap: 8px;
                            ">
                            <div style="
                                    display: flex;
                                    align-items: flex-start;
                                    gap: 8px;
                                ">
                                <i class="bi bi-exclamation-triangle-fill" style="color: #eab308"></i>
                                <span>Không đặt cọc nếu chưa xem phòng</span>
                            </div>
                            <div style="
                                    display: flex;
                                    align-items: flex-start;
                                    gap: 8px;
                                ">
                                <i class="bi bi-check-circle-fill" style="color: #22c55e; margin-top: 2px"></i>
                                <span>Kiểm tra giấy tờ chính chủ</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD BẢNG XẾP HẠNG UY TÍN CHỦ TRỌ (VÙNG KHOANH ĐỎ) -->
                <LandlordRankCard
                    :landlord="room.boardingHouse?.user"
                    :reviews-count="room.reviews?.length || 128"
                    :avg-rating="4.9"
                />
            </section>
        </div>

        <!-- Phần Bình Luận / Đánh giá -->
        <section class="reviews-section" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
            <div style="background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-chat-right-quote-fill" style="color: #7c3aed;"></i> Đánh giá từ người thuê
                </h3>
                
                <!-- Danh sách bình luận -->
                <div v-if="room.reviews && room.reviews.length > 0" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 30px;">
                    <div v-for="rev in room.reviews" :key="rev.id" 
                         :style="rev.is_notice ? 'padding: 16px; background: #fef2f2; border-radius: 8px; border: 1px solid #fee2e2; border-left: 4px solid #ef4444;' : 'padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;'">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b;">
                                    <img v-if="rev.tenant_avatar" :src="rev.tenant_avatar.startsWith('http') || rev.tenant_avatar.startsWith('/') ? rev.tenant_avatar : `/storage/${rev.tenant_avatar}`" style="width:100%; height:100%; object-fit:cover;" />
                                    <span v-else>{{ rev.tenant_name ? rev.tenant_name[0].toUpperCase() : 'U' }}</span>
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
                                        <p style="margin: 0; font-weight: 600;" :style="rev.is_notice ? 'color: #b91c1c;' : 'color: #0f172a;'">
                                            {{ rev.tenant_name || 'Khách hàng' }}
                                        </p>
                                        <span v-if="rev.is_notice" style="background: rgba(239,68,68,0.15); color: #ef4444; font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: 700;">
                                            <i class="bi bi-megaphone-fill"></i> Tin quan trọng
                                        </span>
                                        <span v-if="rev.is_admin" style="background: #ef4444; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 12px; font-weight: 700;">
                                            <i class="bi bi-star-fill"></i> Boss
                                        </span>
                                        <span v-else-if="rev.is_owner" style="background: #ef4444; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 12px; font-weight: 700;">
                                            <i class="bi bi-house-fill"></i> Chủ trọ
                                        </span>
                                    </div>
                                    <p style="margin: 0; font-size: 12px;" :style="rev.is_notice ? 'color: #94a3b8;' : 'color: #64748b;'">
                                        {{ rev.created_at }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="!rev.is_notice" style="color: #fbbf24; font-size: 14px;">
                                <i v-for="s in 5" :key="s" :class="s <= rev.rating ? 'bi bi-star-fill' : 'bi bi-star'" style="margin-left: 2px;"></i>
                            </div>
                        </div>
                        <p style="margin: 0; font-size: 14px; line-height: 1.5;" :style="rev.is_notice ? 'color: #1e293b; font-weight: 500;' : 'color: #334155;'">
                            {{ rev.comment }}
                        </p>
                    </div>
                </div>
                <div v-else style="text-align: center; padding: 30px; color: #94a3b8; background: #f8fafc; border-radius: 8px; margin-bottom: 30px;">
                    <i class="bi bi-chat-square-dots" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                    Chưa có đánh giá nào cho phòng này.
                </div>

                <!-- Form gửi bình luận (chỉ hiện nếu được phép) -->
                <div v-if="canReview" style="border-top: 1px solid #e2e8f0; padding-top: 24px;">
                    <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">
                        {{ isNoticeSender ? 'Ghim thông báo mới' : 'Gửi đánh giá của bạn' }}
                    </h4>
                    <form @submit.prevent="submitDirectReview">
                        <div v-if="!isNoticeSender" style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 14px; color: #475569; margin-bottom: 8px;">Đánh giá (Sao)</label>
                            <div style="display: flex; gap: 8px; font-size: 24px; cursor: pointer; color: #cbd5e1;">
                                <i v-for="s in 5" :key="s" 
                                   :class="s <= reviewForm.rating ? 'bi bi-star-fill text-yellow-400' : 'bi bi-star'"
                                   @click="reviewForm.rating = s"
                                   style="transition: color 0.2s;"
                                ></i>
                            </div>
                        </div>
                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 14px; color: #475569; margin-bottom: 8px;">
                                {{ isNoticeSender ? 'Nội dung thông báo' : 'Bình luận' }}
                            </label>
                            <textarea v-model="reviewForm.comment" rows="3" 
                                      :placeholder="isNoticeSender ? 'Nhập nội dung thông báo (sẽ được ghim lên đầu)...' : 'Chia sẻ trải nghiệm của bạn khi ở phòng này...'" 
                                      style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; outline: none; font-size: 14px; resize: vertical;"></textarea>
                            <span v-if="reviewForm.errors.comment" style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ reviewForm.errors.comment }}</span>
                        </div>
                        <button type="submit" :disabled="reviewForm.processing" 
                                :style="isNoticeSender 
                                    ? 'background: #ef4444; color: #fff; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; gap: 8px;' 
                                    : 'background: #7c3aed; color: #fff; padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; gap: 8px;'">
                            <span v-if="reviewForm.processing" class="spinner-border spinner-border-sm"></span>
                            <i v-else :class="isNoticeSender ? 'bi bi-megaphone-fill' : 'bi bi-send-fill'"></i>
                            {{ isNoticeSender ? 'Đăng thông báo' : 'Gửi đánh giá' }}
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- phần phòng tương tự -->
        <section class="tindang_tro">
            <div class="tindang-header">
                <h2>Tin đăng tương tự</h2>
                <div class="tindang-nav" v-if="similarRooms.length > 0">
                    <button class="nav-btn tindang-prev" @click="slideLeft" aria-label="Previous">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="nav-btn tindang-next" @click="slideRight" aria-label="Next">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
            <div class="bao_tindang" ref="sliderRef">
                <div v-if="similarRooms.length === 0" class="no-similar-rooms">
                    Chưa có phòng tương tự khác.
                </div>
                <div v-for="sim in similarRooms" :key="sim.id" class="item_tindang">
                    <Link :href="route('chitiettro', sim.slug_with_hash)" class="similar-card-link">
                        <div class="img">
                            <img :src="(sim.images && sim.images[0]) ||
                                '/anh/banner_tro.png'
                                " alt="Phòng tương tự" />
                            <span class="count"><i class="bi bi-camera"></i>
                                {{ sim.images ? sim.images.length : 1 }}</span>
                        </div>
                        <div class="content">
                            <h3>
                                Phòng {{ sim.room_number }} -
                                {{ sim.boardingHouse?.name || "Phòng trọ" }}
                            </h3>
                            <p class="dientich">
                                Diện tích: {{ parseFloat(sim.area) }} m² · Giá:
                                {{ formatPrice(sim.price) }}
                            </p>
                            <p class="diachi">
                                <i class="bi bi-geo-alt"></i>
                                {{
                                    sim.address ||
                                    sim.boardingHouse?.address_detail ||
                                    "Ninh Bình"
                                }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Booking Modal overlay -->
        <div v-if="showBookingModal" class="booking-modal-overlay" @click.self="showBookingModal = false">
            <div class="booking-modal-box">
                <div class="modal-header">
                    <h3>
                        <i class="bi bi-calendar2-check-fill"></i> Đặt Lịch Hẹn
                        Xem Phòng
                    </h3>
                    <button class="close-btn" @click="showBookingModal = false">
                        &times;
                    </button>
                </div>
                <form @submit.prevent="submitBooking" class="modal-body">
                    <!-- 1. Preview thời gian hẹn -->
                    <div class="booking-preview-card">
                        <i class="bi bi-calendar-check-fill text-blue"></i>
                        <div class="preview-text">
                            <p class="preview-title">Xem trước thời gian hẹn</p>
                            <p class="preview-desc">{{ bookingPreview }}</p>
                        </div>
                    </div>

                    <!-- 2. Chọn ngày hẹn (Tối đa 7 ngày) -->
                    <div class="form-group">
                        <label class="modal-label">
                            Chọn ngày hẹn xem phòng (Tối đa 7 ngày)
                            <span class="required">*</span>
                        </label>
                        <div class="weekly-date-strip">
                            <div v-for="d in dateList" :key="d.value" :class="[
                                'date-strip-card',
                                form.date === d.value ? 'active' : '',
                            ]" @click="selectDate(d.value)">
                                <span class="strip-day">{{ d.dayName }}</span>
                                <span class="strip-date">{{ d.dateNum }}</span>
                                <span class="strip-month">Thg {{ d.monthNum }}</span>
                            </div>
                        </div>
                        <span v-if="form.errors.date" class="modal-error">{{
                            form.errors.date
                            }}</span>
                    </div>

                    <!-- 3. Chọn giờ (Liên kết động với khung giờ rảnh của chủ trọ) -->
                    <div class="form-group">
                        <label class="modal-label">
                            Chọn giờ hẹn xem phòng
                            <span class="required">*</span>
                        </label>

                        <!-- Trường hợp chủ trọ không có khung giờ rảnh nào vào ngày này -->
                        <div v-if="availablSlots.length === 0" class="modal-error" style="
                                background: #fff1f2;
                                border: 1px solid #fecdd3;
                                padding: 12px;
                                border-radius: 12px;
                                color: #e11d48;
                                font-weight: 600;
                                font-size: 13px;
                                margin-bottom: 10px;
                            ">
                            <i class="bi bi-exclamation-circle-fill"></i> Chủ
                            trọ không nhận lịch hẹn vào ngày này hoặc chưa cấu
                            hình giờ rảnh. Vui lòng chọn ngày khác!
                        </div>

                        <!-- Trường hợp có giờ rảnh, lặp qua danh sách giờ rảnh thực tế từ DB -->
                        <div v-else class="time-slots-grid">
                            <template v-for="slot in availablSlots" :key="slot">
                                <button v-if="!disabledSlots.includes(slot)" type="button" :class="[
                                    'time-slot',
                                    form.time === slot ? 'active' : '',
                                    isTimeSlotDisabled(slot)
                                        ? 'disabled'
                                        : '',
                                ]" :disabled="isTimeSlotDisabled(slot)" @click="selectTime(slot)">
                                    <i class="bi bi-clock-fill slot-icon"></i>
                                    <span>{{ slot }}</span>
                                </button>
                            </template>
                        </div>

                        <span v-if="form.errors.time" class="modal-error">{{
                            form.errors.time
                            }}</span>
                    </div>

                    <!-- 4. Ghi chú -->
                    <div class="form-group">
                        <label class="modal-label">Ghi chú gửi chủ trọ</label>
                        <textarea v-model="form.note" class="modal-textarea"
                            placeholder="Nhập ghi chú thêm cho chủ nhà biết nhu cầu của bạn..." rows="3"></textarea>
                        <span v-if="form.errors.note" class="modal-error">{{
                            form.errors.note
                            }}</span>
                    </div>

                    <!-- 5. Nút gửi -->
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel-modal" @click="showBookingModal = false">
                            Hủy Bỏ
                        </button>
                        <button type="submit" class="btn-submit-modal" :disabled="form.processing">
                            Gửi Yêu Cầu
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- MODAL BÁO CÁO VI PHẠM -->
        <div v-if="showReportModal" class="booking-modal-overlay" @click.self="showReportModal = false">
            <div class="booking-modal-box">
                <!-- Header -->
                <div class="modal-header">
                    <h3>
                        <i class="bi bi-flag-fill text-rose-500"></i>
                        Báo cáo phòng trọ vi phạm
                    </h3>
                    <button @click="showReportModal = false" class="close-btn">
                        <i class="bi bi-x"></i>
                    </button>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="submitReport" class="report-form">
                    <!-- Hình thức xử lý -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-shield-check"></i>
                            Hình thức xử lý khiếu nại
                            <span>*</span>
                        </label>
                        <div class="report-type-grid">
                            <!-- Tự giải quyết trực tiếp -->
                            <label class="report-card" :class="{
                                active:
                                    reportForm.resolve_type === 'direct',
                            }">
                                <div class="report-card-content">
                                    <input type="radio" value="direct" v-model="reportForm.resolve_type" />
                                    <i class="bi bi-people-fill"></i>
                                    <div>
                                        <h4>Tự giải quyết</h4>
                                        <p>
                                            Hai bên tự thương lượng với nhau,
                                            không cần Admin.
                                        </p>
                                    </div>
                                </div>
                            </label>
                            <!-- Báo cáo lên Admin -->
                            <label class="report-card system" :class="{
                                active:
                                    reportForm.resolve_type === 'system',
                            }">
                                <div class="report-card-content">
                                    <input type="radio" value="system" v-model="reportForm.resolve_type" />

                                    <i class="bi bi-flag-fill"></i>
                                    <div>
                                        <h4>Báo cáo Admin</h4>
                                        <p>Admin sẽ tiếp nhận và xử lý.</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Lý do -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-patch-question"></i>
                            Lý do báo cáo
                            <span>*</span>
                        </label>
                        <select v-model="reportForm.reason" class="form-select" :class="{
                            'border-red-500 bg-red-50':
                                reportForm.errors.reason,
                        }">
                            <option disabled value="">
                                -- Chọn lý do báo cáo --
                            </option>
                            <option v-for="(reason, index) in reasons" :key="index" :value="reason">
                                {{ reason }}
                            </option>
                        </select>
                        <p v-if="reportForm.errors.reason" class="modal-error">
                            {{ reportForm.errors.reason }}
                        </p>
                    </div>

                    <!-- Mô tả (Chỉ hiển thị khi báo cáo hệ thống) -->
                    <div v-if="reportForm.resolve_type === 'system'" class="form-group">
                        <label class="form-label">
                            <i class="bi bi-pencil-square"></i>
                            Mô tả chi tiết
                            <span>*</span>
                        </label>
                        <textarea v-model="reportForm.description" rows="4"
                            placeholder="Mô tả cụ thể sự việc bạn gặp phải..." class="form-textarea" :class="{
                                'border-red-500 bg-red-50':
                                    reportForm.errors.description,
                            }"></textarea>
                        <p v-if="reportForm.errors.description" class="modal-error">
                            {{ reportForm.errors.description }}
                        </p>
                    </div>

                    <!-- Upload (Chỉ hiển thị khi báo cáo hệ thống) -->
                    <div v-if="reportForm.resolve_type === 'system'" class="form-group">
                        <label class="form-label">
                            <i class="bi bi-images"></i>
                            Hình ảnh bằng chứng
                        </label>
                        <label class="upload-box">
                            <i class="bi bi-cloud-upload"></i>
                            <p>Nhấn để chọn ảnh</p>
                            <span>PNG, JPG, JPEG • Tối đa 5 ảnh</span>
                            <input type="file" class="hidden" multiple accept="image/*" style="display: none"
                                @change="handleEvidenceChange" />
                        </label>

                        <!-- Hiển thị danh sách ảnh xem trước (Preview) -->
                        <div v-if="previewUrls.length > 0" class="preview-grid">
                            <div v-for="(url, idx) in previewUrls" :key="idx" class="preview-item">
                                <img :src="url" />
                                <button type="button" @click="removeEvidenceImage(idx)" class="preview-remove">
                                    &times;
                                </button>
                            </div>
                        </div>
                        <p v-if="reportForm.errors.evidence_images" class="modal-error">
                            {{ reportForm.errors.evidence_images }}
                        </p>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel-modal" @click="showReportModal = false">
                            Hủy
                        </button>
                        <button type="submit" class="btn-submit-modal" :disabled="reportForm.processing">
                            {{
                                reportForm.processing
                                    ? "Đang gửi..."
                                    : "Gửi báo cáo"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
@import "../../css/chitiettro.css";
@import "../../css/responsive/responsivechitiettro.css";
@import "../../css/responsive/responsive.css";
</style>
