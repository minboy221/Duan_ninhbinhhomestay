<script setup>
import MainLayout from "@/Layouts/MainLayout.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import axios from "axios";

const props = defineProps({
    room: { type: Object, required: true },
    similarRooms: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

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

async function openBooking() {
    if (!user.value) {
        if (
            confirm(
                "Bạn cần đăng nhập tài khoản khách thuê để đặt lịch xem phòng. Đi đến trang đăng nhập?",
            )
        ) {
            window.location.href = route("login");
        }
        return;
    }
    //tải danh sách lịch trùng ngay cho ngày mặc định
    await fetchBookedSlots(form.date);
    showBookingModal.value = true;
}

function reportListing() {
    alert("Cảm ơn bạn đã gửi báo cáo. Ninh Bình HomeStay sẽ tiến hành kiểm tra và xác minh thông tin phòng trọ này trong thời gian sớm nhất!");
}

function submitBooking() {
    form.post(route("rooms.book", props.room.id), {
        preserveScroll: true,
        onSuccess: () => {
            showBookingModal.value = false;
            form.reset();
            alert(
                "Gửi yêu cầu đặt lịch hẹn thành công! Vui lòng đợi chủ trọ phản hồi.",
            );
        },
    });
}
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

        <!-- Flash messages -->
        <div v-if="$page.props.flash.success" class="flash-success-alert">
            <i class="bi bi-check-circle-fill"></i>
            {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash.error" class="flash-error-alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ $page.props.flash.error }}
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
                        <div class="tieude">
                            <h2>
                                {{ room.title || `Phòng ${room.room_number} - ${room.boardingHouse?.name || 'Phòng trọ dịch vụ'}` }}
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
                            <p style="white-space: pre-line; line-height: 1.6; color: #4b5563;">
                                {{
                                    room.description ||
                                    room.boardingHouse?.description ||
                                    "Chưa có thông tin mô tả chi tiết từ chủ nhà."
                                }}
                            </p>

                            <h4 v-if="roomAmenities.length > 0">
                                Tiện ích đi kèm:
                            </h4>
                            <div class="tienich" v-if="roomAmenities.length > 0">
                                <div v-for="amenity in roomAmenities" :key="amenity" class="baotienich">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>{{ amenity }}</span>
                                </div>
                            </div>

                            <h4 v-if="room.services && room.services.length > 0" style="margin-top: 24px;">
                                Chi phí &amp; Dịch vụ đi kèm:
                            </h4>
                            <div class="bang-dichvu" v-if="room.services && room.services.length > 0">
                                <div v-for="srv in room.services" :key="srv.id" class="item-dichvu">
                                    <div :class="['icon-dv', srv.color || 'emerald']">
                                        <i :class="['bi', srv.icon || 'bi-lightning-charge-fill']"></i>
                                    </div>
                                    <div class="info-dv">
                                        <span class="ten-dv">{{ srv.name }}</span>
                                        <span class="gia-dv">
                                            {{ new Intl.NumberFormat('vi-VN').format(srv.price) }}đ
                                            <span class="dv-unit">
                                                / {{ srv.type === 'per_kwh' ? 'kWh' : srv.type === 'per_m3' ? 'm³' : srv.type === 'per_person' ? 'người' : 'phòng' }}
                                            </span>
                                        </span>
                                    </div>
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
                            <span class="status1 online"></span>
                        </div>
                        <div class="name_chutro">
                            <h3>
                                {{
                                    room.boardingHouse?.user?.name || "Chủ trọ"
                                }}
                            </h3>
                            <div class="kiem_chung">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Chủ Trọ Đã Xác Thực</span>
                            </div>
                        </div>
                    </div>

                    <div class="content_chutro">
                        <div class="phone">
                            <a class="btn_content" :href="`tel:${room.boardingHouse?.user?.phone || '0862931722'}`">
                                <i class="bi bi-telephone"></i>
                                <span>{{
                                    room.boardingHouse?.user?.phone ||
                                    "0862931722"
                                }}</span>
                            </a>
                        </div>
                        <div class="nhantin_chutro">
                            <button @click="openBooking" class="btn_mess" style="
                                    border: none;
                                    width: 100%;
                                    text-align: center;
                                    cursor: pointer;
                                ">
                                <i class="bi bi-calendar-check-fill"></i>
                                <span>Đặt Lịch Hẹn</span>
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
                        <h4 style="font-weight: 700; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; color: #1f2937;">
                            Lưu ý an toàn
                        </h4>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <i class="bi bi-exclamation-triangle-fill" style="color: #eab308;"></i>
                                <span>Không đặt cọc nếu chưa xem phòng</span>
                            </div>
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <i class="bi bi-check-circle-fill" style="color: #22c55e; margin-top: 2px;"></i>
                                <span>Kiểm tra giấy tờ chính chủ</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- phần phòng tương tự -->
        <section class="tindang_tro">
            <h2>Tin đăng tương tự</h2>
            <div class="bao_tindang">
                <div v-if="similarRooms.length === 0" class="no-similar-rooms">
                    Chưa có phòng tương tự khác.
                </div>
                <div v-for="sim in similarRooms" :key="sim.id" class="item_tindang">
                    <Link :href="route('chitiettro', sim.id)" class="similar-card-link">
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
                        <div v-if="availablSlots.length === 0" class="modal-error"
                            style="background: #fff1f2; border: 1px solid #fecdd3; padding: 12px; border-radius: 12px; color: #e11d48; font-weight: 600; font-size: 13px; margin-bottom: 10px;">
                            <i class="bi bi-exclamation-circle-fill"></i> Chủ trọ không nhận lịch hẹn vào ngày này hoặc
                            chưa cấu hình giờ rảnh. Vui lòng chọn ngày khác!
                        </div>

                        <!-- Trường hợp có giờ rảnh, lặp qua danh sách giờ rảnh thực tế từ DB -->
                        <div v-else class="time-slots-grid">
                            <template v-for="slot in availablSlots" :key="slot">
                                <button v-if="!disabledSlots.includes(slot)" type="button" :class="[
                                    'time-slot',
                                    form.time === slot ? 'active' : '',
                                    isTimeSlotDisabled(slot) ? 'disabled' : '',
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
    </MainLayout>
</template>

<style scoped>
@import "../../css/chitiettro.css";
@import "../../css/responsive/responsivechitiettro.css";
@import "../../css/responsive/responsive.css";

/* TIỆN ÍCH */
.tienich {
    margin-top: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.baotienich {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f1f3f5;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 13px;
    transition: 0.3s;
}

.baotienich i {
    color: #45abe6;
}

/* hover nhẹ */
.baotienich:hover {
    background: #e6f9f2;
}

/* Styling cho Booking Modal */
.booking-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(6px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
}

.booking-modal-box {
    background: #ffffff;
    border-radius: 20px;
    width: 520px;
    max-width: 92%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(226, 232, 240, 0.8);
    animation: modalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    border-bottom: 1px solid #f1f5f9;
}

.modal-header h3 {
    font-size: 17px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-header h3 i {
    color: #3b82f6;
}

.close-btn {
    background: none;
    border: none;
    font-size: 26px;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.2s;
    padding: 0;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
}

.close-btn:hover {
    color: #ef4444;
    background: #fef2f2;
}

.modal-body {
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.booking-preview-card {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.booking-preview-card i {
    font-size: 22px;
    color: #2563eb;
}

.preview-text {
    display: flex;
    flex-direction: column;
}

.preview-title {
    font-size: 11px;
    font-weight: 600;
    color: #1e40af;
    text-transform: uppercase;
    margin: 0 0 2px 0;
    letter-spacing: 0.5px;
}

.preview-desc {
    font-size: 14px;
    font-weight: 700;
    color: #1d4ed8;
    margin: 0;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.modal-label {
    font-size: 14px;
    font-weight: 600;
    color: #475569;
    display: flex;
    align-items: center;
}

.required {
    color: #ef4444;
    margin-left: 3px;
}

/* Weekly Date Strip */
.weekly-date-strip {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 6px;
    scrollbar-width: thin;
}

.weekly-date-strip::-webkit-scrollbar {
    height: 4px;
}

.weekly-date-strip::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.date-strip-card {
    flex: 0 0 72px;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 6px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.date-strip-card:hover {
    border-color: #93c5fd;
    background: #f0f7ff;
}

.date-strip-card.active {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff !important;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
}

.strip-day {
    font-size: 10px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
}

.date-strip-card.active .strip-day {
    color: rgba(255, 255, 255, 0.85);
}

.strip-date {
    font-size: 18px;
    font-weight: 800;
    color: #1e293b;
    margin: 2px 0;
}

.date-strip-card.active .strip-date {
    color: #ffffff;
}

.strip-month {
    font-size: 9px;
    font-weight: 600;
    color: #94a3b8;
}

.date-strip-card.active .strip-month {
    color: rgba(255, 255, 255, 0.8);
}

/* Time Slots Grid */
.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.time-slot {
    padding: 8px 4px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}

.time-slot:hover:not(.disabled) {
    border-color: #3b82f6;
    background: #eff6ff;
    color: #2563eb;
}

.time-slot.active {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
}

.time-slot.disabled {
    background: #f1f5f9;
    border-color: #e2e8f0;
    color: #94a3b8;
    cursor: not-allowed;
    opacity: 0.6;
}

.time-slot.disabled .slot-icon {
    color: #94a3b8;
}

.slot-icon {
    font-size: 12px;
    color: #64748b;
}

.time-slot.active .slot-icon {
    color: #ffffff;
}

.modal-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    resize: none;
    transition: border-color 0.2s;
}

.modal-textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.modal-error {
    font-size: 12px;
    color: #ef4444;
    font-weight: 600;
    margin-top: 4px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 12px;
}

.btn-cancel-modal {
    padding: 10px 20px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.btn-cancel-modal:hover {
    background: #f8fafc;
    border-color: #94a3b8;
}

.btn-submit-modal {
    padding: 10px 24px;
    border: none;
    background: linear-gradient(90deg, #102a6d, #45abe6);
    color: #ffffff;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    font-size: 13px;
    transition: opacity 0.2s;
}

.btn-submit-modal:hover:not(:disabled) {
    opacity: 0.9;
}

.btn-submit-modal:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Styling cho Dịch vụ đi kèm */
.bang-dichvu {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-top: 12px;
    margin-bottom: 24px;
}

.item-dichvu {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.25s ease;
}

.item-dichvu:hover {
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-color: #cbd5e1;
    transform: translateY(-2px);
}

.icon-dv {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    border: 1px solid transparent;
}

.icon-dv.emerald { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
.icon-dv.blue { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
.icon-dv.amber { background: #fffbeb; color: #d97706; border-color: #fde68a; }
.icon-dv.cyan { background: #ecfeff; color: #0891b2; border-color: #a5f3fc; }
.icon-dv.rose { background: #fff5f5; color: #e11d48; border-color: #fecaca; }
.icon-dv.purple { background: #faf5ff; color: #7e22ce; border-color: #e9d5ff; }

.info-dv {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.ten-dv {
    font-size: 13px;
    font-weight: 700;
    color: #475569;
}

.gia-dv {
    font-size: 15px;
    font-weight: 800;
    color: #0f172a;
}

.dv-unit {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
}

@media (max-width: 576px) {
    .bang-dichvu {
        grid-template-columns: 1fr;
    }
}

/* Responsive cho Mobile */
@media (max-width: 480px) {
    .time-slots-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .modal-footer {
        flex-direction: column-reverse;
    }
    .btn-cancel-modal,
    .btn-submit-modal {
        width: 100%;
        text-align: center;
    }
}
</style>
