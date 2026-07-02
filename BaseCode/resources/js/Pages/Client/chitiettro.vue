<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    room: { type: Object, required: true },
    similarRooms: { type: Array, default: () => [] },
    is_favorited: { type: Boolean, default: false }
});

const isFavorited = ref(props.is_favorited);

const page = usePage();
const user = computed(() => page.props.auth?.user);

const router = usePage().router; // or import it from @inertiajs/vue3
import { router as inertiaRouter } from '@inertiajs/vue3';

function toggleFavorite() {
    if (!user.value) {
        if (confirm('Bạn cần đăng nhập để thêm phòng vào danh sách yêu thích. Đi đến trang đăng nhập?')) {
            window.location.href = route('login');
        }
        return;
    }
    
    inertiaRouter.post(route('rooms.favorite', props.room.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isFavorited.value = !isFavorited.value;
        }
    });
}

// Image carousel state
const activeImageIndex = ref(0);
const roomImages = computed(() => {
    if (props.room.images && Array.isArray(props.room.images) && props.room.images.length > 0) {
        return props.room.images;
    }
    // Default image if none
    return [props.room.images || '/anh/banner_tro.png'];
});

const activeImage = computed(() => roomImages.value[activeImageIndex.value] || '/anh/banner_tro.png');

function nextImage() {
    activeImageIndex.value = (activeImageIndex.value + 1) % roomImages.value.length;
}

function prevImage() {
    activeImageIndex.value = (activeImageIndex.value - 1 + roomImages.value.length) % roomImages.value.length;
}

// Split amenities
const roomAmenities = computed(() => {
    if (!props.room.amenities) return [];
    if (Array.isArray(props.room.amenities)) return props.room.amenities;
    return props.room.amenities.split(',').map(s => s.trim()).filter(Boolean);
});

// Timezone-safe today date string for minimum date input
// Timezone-safe today date string for minimum date input
const getTodayDateStr = () => {
    const today = new Date();
    const offset = today.getTimezoneOffset();
    const localDate = new Date(today.getTime() - (offset * 60 * 1000));
    return localDate.toISOString().split('T')[0];
};

const getDaysOfWeek = () => {
    const list = [];
    const days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
    const fullDays = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
    const now = new Date();

    for (let i = 0; i < 7; i++) {
        const d = new Date(now.getTime());
        d.setDate(now.getDate() + i);
        
        // Timezone-safe value
        const offset = d.getTimezoneOffset();
        const localDate = new Date(d.getTime() - (offset * 60 * 1000));
        const valStr = localDate.toISOString().split('T')[0];
        
        let dayLabel = days[d.getDay()];
        if (i === 0) dayLabel = 'Hôm nay';
        else if (i === 1) dayLabel = 'Ngày mai';

        list.push({
            dayName: dayLabel,
            fullDayName: fullDays[d.getDay()],
            dateNum: d.getDate(),
            monthNum: d.getMonth() + 1,
            yearNum: d.getFullYear(),
            value: valStr
        });
    }
    return list;
};

const dateList = computed(() => getDaysOfWeek());

const timeSlots = [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00'
];

const isTimeSlotDisabled = (slot) => {
    if (form.date !== getTodayDateStr()) {
        return false;
    }
    const now = new Date();
    const [slotHour, slotMin] = slot.split(':').map(Number);
    const currentHour = now.getHours();
    const currentMin = now.getMinutes();

    if (slotHour < currentHour) {
        return true;
    }
    if (slotHour === currentHour && slotMin <= currentMin) {
        return true;
    }
    return false;
};

function selectDate(dateVal) {
    form.date = dateVal;
    if (form.time && isTimeSlotDisabled(form.time)) {
        form.time = '';
    }
}

function selectTime(timeVal) {
    if (isTimeSlotDisabled(timeVal)) return;
    form.time = timeVal;
}

// Live booking preview display text
const bookingPreview = computed(() => {
    if (!form.date && !form.time) {
        return 'Vui lòng chọn ngày và giờ cụ thể để đặt lịch xem phòng.';
    }
    let dateStr = '';
    if (form.date) {
        const d = new Date(form.date);
        dateStr = `ngày ${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
    }
    const timeStr = form.time ? `vào lúc ${form.time}` : 'trong ngày';
    return `Bạn đăng ký xem phòng: ${timeStr} ${dateStr}`;
});

// Format price helper
const formatPrice = (price) => {
    const p = parseFloat(price);
    if (p >= 1000000) {
        return (p / 1000000).toFixed(1).replace('.0', '') + ' Triệu/Tháng';
    }
    return p.toLocaleString('vi-VN') + ' đ/Tháng';
};

// Booking modal state
const showBookingModal = ref(false);

const form = useForm({
    date: getTodayDateStr(),
    time: '',
    note: ''
});

function openBooking() {
    if (!user.value) {
        // Redirect to login or show notice
        if (confirm('Bạn cần đăng nhập tài khoản khách thuê để đặt lịch xem phòng. Đi đến trang đăng nhập?')) {
            window.location.href = route('login');
        }
        return;
    }
    showBookingModal.value = true;
}

function submitBooking() {
    form.post(route('rooms.book', props.room.id), {
        preserveScroll: true,
        onSuccess: () => {
            showBookingModal.value = false;
            form.reset();
            alert('Gửi yêu cầu đặt lịch hẹn thành công! Vui lòng đợi chủ trọ phản hồi.');
        }
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
                            <button v-if="roomImages.length > 1" @click="prevImage" class="prev">&#10094;</button>
                            <img id="currentImage" :src="activeImage" alt="Room Image">
                            <button v-if="roomImages.length > 1" @click="nextImage" class="next">&#10095;</button>
                        </div>

                        <!-- Thumbnail -->
                        <div class="thumbnails" v-if="roomImages.length > 1">
                            <img v-for="(img, idx) in roomImages" :key="idx" 
                                 :src="img" 
                                 :class="['thumb', activeImageIndex === idx ? 'active' : '']"
                                 @click="activeImageIndex = idx"
                                 alt="Thumbnail">
                        </div>
                    </div>
                    <div class="detail_tro">
                        <div class="theloai">
                            <i class="bi bi-award"></i>
                            <span>{{ room.status === 'available' ? 'ĐANG TRỐNG / ĐÃ KIỂM CHỨNG' : 'ĐÃ ĐƯỢC KIỂM CHỨNG' }}</span>
                        </div>
                        <div class="tieude" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <h2>Phòng {{ room.room_number }} - {{ room.property?.name || 'Phòng trọ dịch vụ' }}</h2>
                            <button @click="toggleFavorite" class="favorite-heart-btn" :title="isFavorited ? 'Bỏ yêu thích' : 'Thêm vào yêu thích'">
                                <i :class="['bi', isFavorited ? 'bi-heart-fill text-red' : 'bi-heart']"></i>
                            </button>
                        </div>
                        <div class="location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>Địa điểm: {{ room.address || room.property?.address || 'Ninh Bình' }}</span>
                        </div>
                        <div class="infor_tro">
                            <div class="price_tro">
                                <p>{{ formatPrice(room.price) }}</p>
                            </div>
                            <div class="trangthai">
                                <p>Diện tích: {{ parseFloat(room.area) }} m² · Sức chứa: {{ room.capacity }} người</p>
                            </div>
                        </div>
                        <div class="thongtin_tro">
                            <h4>Thông tin mô tả:</h4>
                            <p>{{ room.property?.description || 'Chưa có thông tin mô tả chi tiết từ chủ nhà.' }}</p>
                            
                            <h4 v-if="roomAmenities.length > 0">Tiện ích đi kèm:</h4>
                            <div class="tienich" v-if="roomAmenities.length > 0">
                                <div v-for="amenity in roomAmenities" :key="amenity" class="baotienich">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>{{ amenity }}</span>
                                </div>
                            </div>

                            <div class="bando">
                                <h2>Vị trí & Bản Đồ</h2>
                                <iframe
                                    :src="`https://maps.google.com/maps?q=${encodeURIComponent(room.address || room.property?.address || 'Ninh Bình')}&t=&z=15&ie=UTF8&iwloc=&output=embed`"
                                    width="100%" height="350" style="border:0; border-radius:12px;" allowfullscreen="" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="info_chutro">
                <div class="baochutro">
                    <div class="avatar1">
                        <div class="avatar-img">
                            <img :src="room.property?.landlord?.avatar ? '/storage/' + room.property.landlord.avatar : '/anh/banner.png'" alt="Avatar">
                            <span class="status1 online"></span>
                        </div>
                        <div class="name_chutro">
                            <h3>{{ room.property?.landlord?.name || 'Nhật Minh' }}</h3>
                            <div class="kiem_chung">
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Chủ Trọ Đã Xác Thực</span>
                            </div>
                        </div>
                    </div>
                    <div class="content_chutro">
                        <div class="phone">
                            <a class="btn_content" :href="`tel:${room.property?.landlord?.phone || '0862931722'}`">
                                <i class="bi bi-telephone"></i>
                                <span>{{ room.property?.landlord?.phone || '0862931722' }}</span>
                            </a>
                        </div>
                        <div class="nhantin_chutro">
                            <button @click="openBooking" class="btn_mess" style="border:none; width: 100%; text-align: center; cursor:pointer;">
                                <i class="bi bi-calendar-check-fill"></i>
                                <span>Đặt Lịch Hẹn</span>
                            </button>
                        </div>
                        <div class="warning">
                            <a class="btn_waring" href="">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Báo Xấu</span>
                            </a>
                        </div>
                    </div>
                    <div class="luu_y">
                        <h5>Lưu ý an toàn</h5>
                        <div class="warning_content">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>Không đặt cọc nếu chưa xem phòng</span>
                        </div>
                        <div class="success_content">
                            <i class="bi bi-check-circle"></i>
                            <span>Kiểm tra giấy tờ chính chủ</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- phần bài đăng liên quan -->
        <section class="tindang_tro">
            <h2>Tin đăng tương tự</h2>
            <div class="bao_tindang">
                <div v-if="similarRooms.length === 0" class="no-similar-rooms">
                    Chưa có phòng tương tự khác.
                </div>
                <div v-for="sim in similarRooms" :key="sim.id" class="item_tindang">
                    <Link :href="route('chitiettro', sim.id)" class="similar-card-link">
                        <div class="img">
                            <img :src="(sim.images && sim.images[0]) || '/anh/banner_tro.png'">
                            <span class="count"><i class="bi bi-camera"></i> 3</span>
                        </div>
                        <div class="content">
                            <h3>Phòng {{ sim.room_number }} - {{ sim.property?.name }}</h3>
                            <p class="dientich">Diện tích: {{ parseFloat(sim.area) }} m² · Giá: {{ formatPrice(sim.price) }}</p>
                            <p class="diachi"><i class="bi bi-geo-alt"></i> {{ sim.address || sim.property?.address }}</p>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Booking Modal overlay -->
        <div v-if="showBookingModal" class="booking-modal-overlay" @click.self="showBookingModal = false">
            <div class="booking-modal-box">
                <div class="modal-header">
                    <h3><i class="bi bi-calendar2-check-fill"></i> Đặt Lịch Hẹn Xem Phòng</h3>
                    <button class="close-btn" @click="showBookingModal = false">&times;</button>
                </div>
                <form @submit.prevent="submitBooking" class="modal-body">
                    <!-- Premium visual scheduling summary preview -->
                    <div class="booking-preview-card">
                        <i class="bi bi-calendar-check-fill text-blue"></i>
                        <div class="preview-text">
                            <p class="preview-title">Xem trước thời gian hẹn</p>
                            <p class="preview-desc">{{ bookingPreview }}</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="modal-label">Chọn ngày hẹn xem phòng (Tối đa 7 ngày) <span class="required">*</span></label>
                        <div class="weekly-date-strip">
                            <div v-for="d in dateList" :key="d.value"
                                 :class="['date-strip-card', form.date === d.value ? 'active' : '']"
                                 @click="selectDate(d.value)">
                                <span class="strip-day">{{ d.dayName }}</span>
                                <span class="strip-date">{{ d.dateNum }}</span>
                                <span class="strip-month">Thg {{ d.monthNum }}</span>
                            </div>
                        </div>
                        <span v-if="form.errors.date" class="modal-error">{{ form.errors.date }}</span>
                    </div>

                    <div class="form-group">
                        <label class="modal-label">Chọn giờ hẹn xem phòng <span class="required">*</span></label>
                        <div class="time-slots-grid">
                            <button v-for="slot in timeSlots" :key="slot" type="button"
                                    :class="['time-slot', form.time === slot ? 'active' : '', isTimeSlotDisabled(slot) ? 'disabled' : '']"
                                    :disabled="isTimeSlotDisabled(slot)"
                                    @click="selectTime(slot)">
                                <i class="bi bi-clock-fill slot-icon"></i>
                                <span>{{ slot }}</span>
                            </button>
                        </div>
                        <span v-if="form.errors.time" class="modal-error">{{ form.errors.time }}</span>
                    </div>

                    <div class="form-group">
                        <label class="modal-label">Ghi chú gửi chủ trọ</label>
                        <textarea v-model="form.note" class="modal-textarea" placeholder="Nhập ghi chú thêm cho chủ nhà biết nhu cầu của bạn..." rows="3"></textarea>
                        <span v-if="form.errors.note" class="modal-error">{{ form.errors.note }}</span>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-cancel-modal" @click="showBookingModal = false">Hủy Bỏ</button>
                        <button type="submit" class="btn-submit-modal" :disabled="form.processing">Gửi Yêu Cầu</button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
@import "../../css/chitiettro.css";
@import '../../css/responsive/responsivechitiettro.css';
@import '../../css/responsive/responsive.css';

/* Booking Modal styling */
.booking-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(12px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.booking-modal-box {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.45);
    border-radius: 24px;
    width: 480px;
    max-width: 90%;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    animation: modalFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalFadeIn {
    from { transform: translateY(40px) scale(0.96); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}

.modal-header {
    background: linear-gradient(135deg, #1e40af, #1e3a8a);
    color: #fff;
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 800;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.close-btn {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #fff;
    font-size: 20px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: rotate(90deg);
}

.modal-body {
    padding: 28px 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.booking-preview-card {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border: 1px solid #bfdbfe;
    border-radius: 16px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.text-blue {
    color: #2563eb !important;
    font-size: 22px;
}

.preview-title {
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 700;
    color: #1e40af;
    letter-spacing: 0.05em;
    margin: 0 0 2px;
}

.preview-desc {
    font-size: 13px;
    font-weight: 600;
    color: #1e3a8a;
    margin: 0;
    line-height: 1.4;
}

.modal-label {
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
    color: #475569;
    margin-bottom: 8px;
    display: block;
}

.required {
    color: #ef4444;
}

.modal-input {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    outline: none;
    transition: all 0.2s;
    box-sizing: border-box;
}

.modal-input:focus {
    border-color: #166ea9;
}

.modal-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    resize: none;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.modal-textarea:focus {
    border-color: #166ea9;
}

.modal-error {
    color: #ef4444;
    font-size: 12px;
    margin-top: 4px;
    display: block;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 8px;
}

.btn-cancel-modal {
    padding: 10px 16px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    border-radius: 8px;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.btn-submit-modal {
    padding: 10px 20px;
    border: none;
    background: #166ea9;
    color: #fff;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-submit-modal:hover {
    background: #0f4f7a;
}

.btn-submit-modal:disabled {
    background-color: #94a3b8;
    cursor: not-allowed;
}

/* Premium Date/Time Input custom styling */
.date-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}

/* Custom Date cards design */
.custom-date-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    width: 100%;
}

.date-card {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Weekly date strip design */
.weekly-date-strip {
    display: flex;
    gap: 8px;
    width: 100%;
    overflow-x: auto;
    padding: 4px 2px;
}

.weekly-date-strip::-webkit-scrollbar {
    height: 4px;
}

.weekly-date-strip::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.date-strip-card {
    flex: 0 0 60px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.date-strip-card:hover {
    border-color: #cbd5e1;
    background: #f1f5f9;
}

.date-strip-card.active {
    background: #eff6ff;
    border-color: #2563eb;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.08);
}

.date-strip-card .strip-day {
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 2px;
}

.date-strip-card.active .strip-day {
    color: #2563eb;
}

.date-strip-card .strip-date {
    font-size: 15px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
}

.date-strip-card.active .strip-date {
    color: #1e3a8a;
}

.date-strip-card .strip-month {
    font-size: 8.5px;
    font-weight: 600;
    color: #94a3b8;
    margin-top: 2px;
}

.date-strip-card.active .strip-month {
    color: #3b82f6;
}

/* Time slots grid design */
.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    width: 100%;
    max-height: 160px;
    overflow-y: auto;
    padding-right: 4px;
}

.time-slot {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 4px;
    font-size: 12.5px;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    transition: all 0.2s;
}

.time-slot .slot-icon {
    font-size: 11px;
    color: #94a3b8;
}

.time-slot:hover:not(.disabled) {
    border-color: #cbd5e1;
    background: #f8fafc;
    color: #1e293b;
}

.time-slot.active {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
}

.time-slot.active .slot-icon {
    color: #ffffff;
}

.time-slot.disabled {
    opacity: 0.45;
    background: #f1f5f9;
    border-color: #e2e8f0;
    color: #94a3b8;
    cursor: not-allowed;
}

.similar-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.flash-success-alert {
    background-color: #f0fdf4;
    border-left: 4px solid #15803d;
    color: #15803d;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 16px auto;
    max-width: 1200px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flash-error-alert {
    background-color: #fef2f2;
    border-left: 4px solid #b91c1c;
    color: #b91c1c;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 16px auto;
    max-width: 1200px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Favorite Heart button styling */
.favorite-heart-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 24px;
    color: #94a3b8;
    transition: all 0.2s ease-in-out;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.favorite-heart-btn:hover {
    transform: scale(1.15);
}

.text-red {
    color: #ef4444 !important;
}
</style>