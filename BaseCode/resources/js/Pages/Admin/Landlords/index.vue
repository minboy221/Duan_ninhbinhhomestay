<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import axios from "axios";
import { getAvatarUrl, DEFAULT_AVATAR } from "@/Utils/media";

const page = usePage();
const currentAdminName = computed(() => page.props.auth?.user?.name || "Admin");

const props = defineProps({
    landlords: {
        type: Array,
        default: () => [],
    },
});

const showDetail = ref(false);
const selected = ref(null);
const showImageModal = ref(false);
const currentImageUrl = ref("");
const activeMapLayer = ref("osm");
const copiedGps = ref(false);

const isKycUnlocked = ref(false);
const kycCountdown = ref(0);
let timerInterval = null;

function formatMaskedCccd(cccdStr) {
    if (!cccdStr || typeof cccdStr !== "string") return "Chưa cập nhật";
    if (cccdStr.length < 8) return cccdStr;
    return cccdStr.substring(0, 4) + " •••• •••• " + cccdStr.substring(cccdStr.length - 2);
}

function open(l) {
    selected.value = l;
    isKycUnlocked.value = false;
    kycCountdown.value = 0;
    if (timerInterval) clearInterval(timerInterval);
    showDetail.value = true;
}

function toggleKycLock() {
    if (!isKycUnlocked.value) {
        // Mở khóa thông tin nhạy cảm
        isKycUnlocked.value = true;
        kycCountdown.value = 30;

        // Gửi Audit Log ghi vết bảo mật
        if (selected.value?.id) {
            axios.post(route("admin.landlords.log-kyc-access", selected.value.id)).catch(() => {});
        }

        // Bắt đầu đếm ngược 30 giây
        if (timerInterval) clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            if (kycCountdown.value > 1) {
                kycCountdown.value--;
            } else {
                isKycUnlocked.value = false;
                kycCountdown.value = 0;
                clearInterval(timerInterval);
            }
        }, 1000);
    } else {
        // Tự khóa lại thủ công
        isKycUnlocked.value = false;
        kycCountdown.value = 0;
        if (timerInterval) clearInterval(timerInterval);
    }
}

const getPrivateImageUrl = (path, type) => {
    if (!path || path === "0" || path === "false" || path === false) {
        return null;
    }
    if (typeof path !== "string") return null;
    const trimmed = path.trim();
    if (!trimmed || trimmed === "0" || trimmed === "false") return null;

    if (trimmed.startsWith("http://") || trimmed.startsWith("https://") || trimmed.startsWith("blob:")) {
        return trimmed;
    }
    if (trimmed.startsWith("/storage/")) {
        return trimmed;
    }
    if (trimmed.startsWith("boarding_houses/") || trimmed.startsWith("rooms/")) {
        return `/storage/${trimmed}`;
    }

    const filename = trimmed.replace(/\\/g, "/").split("/").pop();
    if (!filename || filename === "0" || filename === "false") return null;

    return route("files.private", { type: type, filename: filename });
};

const normalizeImages = (images) => {
    if (!images) return [];
    if (typeof images === "string") {
        try {
            const parsed = JSON.parse(images);
            return normalizeImages(parsed);
        } catch (e) {
            const trimmed = images.trim();
            return trimmed && trimmed !== "false" && trimmed !== "0" ? [trimmed] : [];
        }
    }
    if (Array.isArray(images)) {
        return images
            .flatMap((item) => normalizeImages(item))
            .filter((item) => item && typeof item === "string" && item !== "false" && item !== "0");
    }
    return [];
};

const openImage = (url) => {
    if (!url) return;
    currentImageUrl.value = url;
    showImageModal.value = true;
};

const isVideo = (path) => {
    if (!path || typeof path !== "string") return false;
    const ext = path.split(".").pop().toLowerCase();
    return ["mp4", "mov", "avi"].includes(ext);
};

const toDMS = (deg, isLat) => {
    if (deg === null || deg === undefined || isNaN(deg)) return "";
    const num = Number(deg);
    const absolute = Math.abs(num);
    const degrees = Math.floor(absolute);
    const minutesNotTruncated = (absolute - degrees) * 60;
    const minutes = Math.floor(minutesNotTruncated);
    const seconds = ((minutesNotTruncated - minutes) * 60).toFixed(1);
    const direction = isLat ? (num >= 0 ? "N" : "S") : (num >= 0 ? "E" : "W");
    return `${degrees}°${minutes}'${seconds}"${direction}`;
};

const copyGpsCoordinates = (lat, lng) => {
    if (!lat || !lng) return;
    navigator.clipboard.writeText(`${lat}, ${lng}`).then(() => {
        copiedGps.value = true;
        setTimeout(() => { copiedGps.value = false; }, 2000);
    });
};
</script>

<template>

    <Head title="Admin - Quản Lý Chủ Trọ" />
    <AdminLayout>
        <template #header-title>
            <div>
                <h1 class="page-title">Quản Lý Tài Khoản Chủ Trọ</h1>
                <p class="page-sub">
                    Danh sách các tài khoản chủ trọ đã được kiểm duyệt
                </p>
            </div>
        </template>

        <div class="stats-row">
            <div class="scard">
                <i class="bi bi-house-check-fill" style="color: #7c3aed"></i>
                <div>
                    <p class="snum">{{ landlords.length }}</p>
                    <p class="slbl">Tổng chủ trọ</p>
                </div>
            </div>
            <div class="scard">
                <i class="bi bi-patch-check-fill" style="color: #22c55e"></i>
                <div>
                    <p class="snum">{{ landlords.length }}</p>
                    <p class="slbl">Đã xác minh</p>
                </div>
            </div>
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Chủ trọ</th>
                        <th>Số điện thoại</th>
                        <th>Số phòng</th>
                        <th>Gói dịch vụ</th>
                        <th>Xác minh</th>
                        <th style="text-align: center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="landlords.length === 0">
                        <td colspan="7" style="
                                text-align: center;
                                padding: 30px;
                                color: #94a3b8;
                            ">
                            Không có chủ trọ nào.
                            <Link :href="route('admin.verifications.index')" class="text-blue-600 hover:underline">Đến
                                trang
                                duyệt hồ sơ</Link>
                        </td>
                    </tr>
                    <tr v-for="(l, i) in landlords" :key="l.id" class="trow">
                        <td class="idx">{{ i + 1 }}</td>
                        <td>
                            <div class="user-cell">
                                <div class="ava" :style="l.avatar ? 'overflow: hidden; background: #f1f5f9; padding: 0;' : `background:hsl(${l.id * 80}deg,60%,55%)`">
                                    <img v-if="l.avatar"
                                        :src="getAvatarUrl(l.avatar)" 
                                        @error="$event.target.onerror = null; $event.target.src = DEFAULT_AVATAR"
                                        :alt="l.name"
                                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;"
                                    />
                                    <span v-else>{{
                                        l.name ? l.name[0].toUpperCase() : 'U'
                                    }}</span>
                                </div>
                                <div>
                                    <p class="fw">{{ l.name }}</p>
                                    <p class="sm">{{ l.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="sm">{{ l.phone }}</td>
                        <td>
                            <span class="room-badge">{{ l.rooms }} phòng</span>
                        </td>
                        <td>
                            <span :class="[
                                'plan-badge',
                                l.plan === 'Trả phí'
                                    ? 'plan-paid'
                                    : 'plan-free',
                            ]">{{ l.plan }}</span>
                        </td>
                        <td>
                            <span class="ver-badge ver-ok">
                                <i class="bi bi-patch-check-fill"></i> Đã xác
                                minh
                            </span>
                        </td>
                        <td style="text-align: center">
                            <button @click="open(l)" class="act-btn act-primary">
                                <i class="bi bi-eye"></i> Xem
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Teleport to="body">
            <div v-if="showDetail" class="modal-overlay" @click.self="showDetail = false">
                <div class="modal-box modal-lg">
                    <div class="modal-header">
                        <h3>Thông Tin Chủ Trọ & Hồ Sơ Đã Duyệt</h3>
                        <button @click="showDetail = false" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                        <div class="ll-avatar" style="overflow: hidden">
                            <img v-if="selected?.avatar" :src="getAvatarUrl(selected.avatar)"
                                @error="$event.target.onerror = null; $event.target.src = DEFAULT_AVATAR"
                                class="w-full h-full object-cover rounded-full" style="
                                    width: 100%;
                                    height: 100%;
                                    object-fit: cover;
                                " />
                            <span v-else>{{
                                selected?.name[0]?.toUpperCase()
                            }}</span>
                        </div>
                        <h4 class="ll-name">{{ selected?.name }}</h4>
                        <p class="ll-email">{{ selected?.email }}</p>
                        
                        <div class="info-block">
                            <div class="ib-row">
                                <span class="ib-l">Số ĐT</span><span class="ib-v">{{ selected?.phone }}</span>
                            </div>
                            <div class="ib-row">
                                <span class="ib-l">CCCD</span>
                                <span class="ib-v font-mono">
                                    {{ isKycUnlocked ? selected?.cccd : formatMaskedCccd(selected?.cccd) }}
                                </span>
                            </div>
                            <div class="ib-row">
                                <span class="ib-l">Cơ sở trọ</span><span class="ib-v">{{ selected?.boarding_house_name }}</span>
                            </div>
                            <div class="ib-row" v-if="selected?.boarding_house?.district">
                                <span class="ib-l">Khu vực</span><span class="ib-v">{{ selected.boarding_house.district }}</span>
                            </div>
                            <div class="ib-row" v-if="selected?.boarding_house?.address_detail">
                                <span class="ib-l">Địa chỉ</span><span class="ib-v">{{ selected.boarding_house.address_detail }}</span>
                            </div>
                            <div class="ib-row">
                                <span class="ib-l">Số phòng</span><span class="ib-v">{{ selected?.rooms }} phòng</span>
                            </div>
                            <div class="ib-row">
                                <span class="ib-l">Gói</span><span class="ib-v">{{ selected?.plan }}</span>
                            </div>
                            <div class="ib-row">
                                <span class="ib-l">Tham gia</span><span class="ib-v">{{ selected?.joined }}</span>
                            </div>
                        </div>

                        <!-- HỒ SƠ ĐÃ ĐĂNG KÝ / XÁC MINH -->
                        <div class="doc-section">
                            <div class="doc-header-wrap">
                                <h4 class="doc-title">
                                    <i class="bi bi-shield-lock-fill text-purple-600"></i> Giấy Tờ Xác Minh Danh Tính (KYC)
                                </h4>
                                <button type="button" @click="toggleKycLock" :class="['btn-kyc-toggle', isKycUnlocked ? 'btn-unlocked' : 'btn-locked']">
                                    <i :class="['bi', isKycUnlocked ? 'bi-eye-slash-fill' : 'bi-eye-fill']"></i>
                                    <span>{{ isKycUnlocked ? `Khóa lại (${kycCountdown}s)` : 'Mở xem thông tin nhạy cảm' }}</span>
                                </button>
                            </div>

                            <div class="face-match-badge" v-if="selected?.verification?.kyc_status === 'approved'">
                                <i class="bi bi-shield-fill-check"></i>
                                <span>Kết quả đối sánh khuôn mặt (Face-API): <strong>Khớp 100%</strong></span>
                            </div>

                            <div class="doc-grid" v-if="getPrivateImageUrl(selected?.verification?.id_card_front, 'id_cards') || getPrivateImageUrl(selected?.verification?.id_card_back, 'id_cards') || getPrivateImageUrl(selected?.verification?.face_auth_image, 'faces')">
                                <!-- CCCD Mặt trước -->
                                <div class="doc-card" v-if="getPrivateImageUrl(selected?.verification?.id_card_front, 'id_cards')">
                                    <span class="doc-lbl">CCCD Mặt trước</span>
                                    <div :class="['doc-img-wrap', !isKycUnlocked ? 'kyc-blurred' : '']" @contextmenu.prevent @dragstart.prevent @click="isKycUnlocked && openImage(getPrivateImageUrl(selected.verification.id_card_front, 'id_cards'))">
                                        <img :src="getPrivateImageUrl(selected.verification.id_card_front, 'id_cards')" alt="CCCD Trước" />
                                        
                                        <!-- Watermark Overlay khi mở xem -->
                                        <div class="watermark-overlay" v-if="isKycUnlocked">
                                            <span>STAYWORK CONFIDENTIAL • ADMIN: {{ currentAdminName }}</span>
                                        </div>

                                        <!-- Lock Banner khi chưa mở xem -->
                                        <div class="blur-lock-banner" v-else @click.stop="toggleKycLock">
                                            <i class="bi bi-lock-fill"></i>
                                            <span>Đã làm mờ bảo mật</span>
                                        </div>

                                        <div class="img-overlay" v-if="isKycUnlocked"><i class="bi bi-zoom-in"></i> Xem ảnh</div>
                                    </div>
                                </div>

                                <!-- CCCD Mặt sau -->
                                <div class="doc-card" v-if="getPrivateImageUrl(selected?.verification?.id_card_back, 'id_cards')">
                                    <span class="doc-lbl">CCCD Mặt sau</span>
                                    <div :class="['doc-img-wrap', !isKycUnlocked ? 'kyc-blurred' : '']" @contextmenu.prevent @dragstart.prevent @click="isKycUnlocked && openImage(getPrivateImageUrl(selected.verification.id_card_back, 'id_cards'))">
                                        <img :src="getPrivateImageUrl(selected.verification.id_card_back, 'id_cards')" alt="CCCD Sau" />
                                        
                                        <div class="watermark-overlay" v-if="isKycUnlocked">
                                            <span>STAYWORK CONFIDENTIAL • ADMIN: {{ currentAdminName }}</span>
                                        </div>

                                        <div class="blur-lock-banner" v-else @click.stop="toggleKycLock">
                                            <i class="bi bi-lock-fill"></i>
                                            <span>Đã làm mờ bảo mật</span>
                                        </div>

                                        <div class="img-overlay" v-if="isKycUnlocked"><i class="bi bi-zoom-in"></i> Xem ảnh</div>
                                    </div>
                                </div>

                                <!-- Ảnh selfie thực tế -->
                                <div class="doc-card" v-if="getPrivateImageUrl(selected?.verification?.face_auth_image, 'faces')">
                                    <span class="doc-lbl">Ảnh selfie thực tế</span>
                                    <div :class="['doc-img-wrap', !isKycUnlocked ? 'kyc-blurred' : '']" @contextmenu.prevent @dragstart.prevent @click="isKycUnlocked && openImage(getPrivateImageUrl(selected.verification.face_auth_image, 'faces'))">
                                        <img :src="getPrivateImageUrl(selected.verification.face_auth_image, 'faces')" alt="Ảnh selfie" />
                                        
                                        <div class="watermark-overlay" v-if="isKycUnlocked">
                                            <span>STAYWORK CONFIDENTIAL • ADMIN: {{ currentAdminName }}</span>
                                        </div>

                                        <div class="blur-lock-banner" v-else @click.stop="toggleKycLock">
                                            <i class="bi bi-lock-fill"></i>
                                            <span>Đã làm mờ bảo mật</span>
                                        </div>

                                        <div class="img-overlay" v-if="isKycUnlocked"><i class="bi bi-zoom-in"></i> Xem ảnh</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="empty-doc-msg">
                                <i class="bi bi-patch-check-fill text-green-500"></i>
                                <span>Tài khoản chủ trọ đã được xác minh thành công. (Dữ liệu thử nghiệm không đính kèm tệp ảnh).</span>
                            </div>
                        </div>

                        <!-- HỢP ĐỒNG / PHÁP LÝ NHÀ TRỌ -->
                        <div class="doc-section" v-if="normalizeImages(selected?.boarding_house?.contract_images).length">
                            <h4 class="doc-title">
                                <i class="bi bi-file-earmark-text-fill text-blue-600"></i> Giấy Tờ Pháp Lý / Hợp Đồng Cơ Sở
                            </h4>
                            <div class="doc-grid">
                                <div v-for="(path, idx) in normalizeImages(selected.boarding_house.contract_images)" :key="'c-'+idx" class="doc-card">
                                    <span class="doc-lbl">Hợp đồng #{{ idx + 1 }}</span>
                                    <div class="doc-img-wrap" @click="openImage(getPrivateImageUrl(path, 'contracts'))">
                                        <img :src="getPrivateImageUrl(path, 'contracts')" :alt="'Hợp đồng ' + (idx + 1)" />
                                        <div class="img-overlay"><i class="bi bi-zoom-in"></i> Xem ảnh</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- HÌNH ẢNH / VIDEO KHÔNG GIAN THỰC TẾ -->
                        <div class="doc-section" v-if="normalizeImages(selected?.boarding_house?.room_images).length">
                            <h4 class="doc-title">
                                <i class="bi bi-images text-indigo-600"></i> Hình Ảnh / Video Không Gian Trọ Thực Tế
                            </h4>
                            <div class="doc-grid">
                                <div v-for="(path, idx) in normalizeImages(selected.boarding_house.room_images)" :key="'r-'+idx" class="doc-card">
                                    <span class="doc-lbl">{{ isVideo(path) ? 'Video' : 'Ảnh' }} #{{ idx + 1 }}</span>
                                    <div class="doc-img-wrap">
                                        <video v-if="isVideo(path)" :src="getPrivateImageUrl(path, 'rooms')" controls style="width:100%;height:100%;object-fit:cover;"></video>
                                        <div v-else style="width:100%;height:100%;" @click="openImage(getPrivateImageUrl(path, 'rooms'))">
                                            <img :src="getPrivateImageUrl(path, 'rooms')" :alt="'Không gian ' + (idx + 1)" />
                                            <div class="img-overlay"><i class="bi bi-zoom-in"></i> Xem ảnh</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BẢN ĐỒ & TỌA ĐỘ GPS -->
                        <div class="doc-section" v-if="Number(selected?.boarding_house?.latitude) && Number(selected?.boarding_house?.longitude)">
                            <h4 class="doc-title" style="color:#047857;">
                                <i class="bi bi-geo-alt-fill text-emerald-600"></i> Tọa Độ Định Vị GPS Chính Xác
                            </h4>
                            <div class="gps-box">
                                <div class="gps-info">
                                    <div>
                                        <p class="gps-dec">Thập phân: <span>{{ Number(selected.boarding_house.latitude).toFixed(7) }}, {{ Number(selected.boarding_house.longitude).toFixed(7) }}</span></p>
                                        <p class="gps-dms">DMS: {{ toDMS(selected.boarding_house.latitude, true) }} , {{ toDMS(selected.boarding_house.longitude, false) }}</p>
                                    </div>
                                    <button type="button" @click="copyGpsCoordinates(selected.boarding_house.latitude, selected.boarding_house.longitude)" class="btn-copy-gps">
                                        <i class="bi" :class="copiedGps ? 'bi-check-lg text-emerald-600' : 'bi-clipboard'"></i>
                                        {{ copiedGps ? 'Đã sao chép' : 'Sao chép' }}
                                    </button>
                                </div>
                                <div class="map-frame">
                                    <iframe width="100%" height="220" style="border:0" loading="lazy" :src="'https://maps.google.com/maps?q=' + selected.boarding_house.latitude + ',' + selected.boarding_house.longitude + '&z=18&output=embed'"></iframe>
                                </div>
                            </div>
                        </div>
                        <div class="doc-section" v-else-if="selected?.boarding_house?.address_detail || selected?.boarding_house?.district">
                            <h4 class="doc-title">
                                <i class="bi bi-map-fill text-blue-600"></i> Vị Trí Tham Khảo Theo Địa Chỉ Khai Báo
                            </h4>
                            <div class="map-frame">
                                <iframe width="100%" height="200" style="border:0" loading="lazy" :src="'https://maps.google.com/maps?q=' + encodeURIComponent([selected.boarding_house.address_detail, selected.boarding_house.district, 'Ninh Bình'].filter(Boolean).join(', ')) + '&hl=vi&z=15&output=embed'"></iframe>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button @click="showDetail = false" class="btn-cancel">
                            Đóng
                        </button>
                        <span class="approved-label"><i class="bi bi-patch-check-fill"></i> Đã xác minh</span>
                    </div>
                </div>
            </div>

            <!-- Modal Zoom Ảnh -->
            <div v-if="showImageModal" class="modal-overlay" style="z-index:1100; background:rgba(0,0,0,0.85);" @click.self="showImageModal = false" @contextmenu.prevent @dragstart.prevent>
                <div style="position:relative; max-width:90vw; max-height:90vh;" @contextmenu.prevent @dragstart.prevent>
                    <button @click="showImageModal = false" style="position:absolute; top:-40px; right:0; color:#fff; font-size:28px; background:none; border:none; cursor:pointer; z-index:30;">
                        &times;
                    </button>
                    <div style="position:relative; display:inline-block; overflow:hidden; border-radius:8px;" @contextmenu.prevent @dragstart.prevent>
                        <img :src="currentImageUrl" style="max-width:90vw; max-height:85vh; object-fit:contain; border-radius:8px; pointer-events:none; user-select:none;" @contextmenu.prevent @dragstart.prevent />
                        
                        <!-- Watermark Overlay trong Modal Zoom Ảnh -->
                        <div class="watermark-overlay zoom-watermark">
                            <span>STAYWORK CONFIDENTIAL • ADMIN: {{ currentAdminName }}</span>
                        </div>

                        <!-- Lớp transparent overlay đè lên trên để triệt hạ 100% chuột phải -->
                        <div style="position:absolute; inset:0; z-index:25; background:transparent;" @contextmenu.prevent @dragstart.prevent></div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
.page-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.page-sub {
    font-size: 12px;
    color: #94a3b8;
    margin: 2px 0 0;
}

.stats-row {
    display: flex;
    gap: 14px;
    margin-bottom: 18px;
}

.scard {
    background: #fff;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    flex: 1;
}

.scard i {
    font-size: 26px;
    flex-shrink: 0;
}

.snum {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    line-height: 1;
}

.slbl {
    font-size: 11px;
    color: #94a3b8;
    margin: 0;
}

.table-card {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.data-table th {
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 13px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}

.data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}

.trow:last-child td {
    border-bottom: none;
}

.trow:hover td {
    background: #fafbff;
}

.idx {
    color: #cbd5e1;
    font-size: 12px;
    font-weight: 600;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ava {
    width: 34px;
    height: 34px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    flex-shrink: 0;
}

.fw {
    font-weight: 600;
    color: #0f172a;
    margin: 0;
}

.sm {
    font-size: 11px;
    color: #94a3b8;
    margin: 0;
}

.room-badge {
    background: #eff6ff;
    color: #2563eb;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 99px;
}

.plan-paid {
    background: #f0fdf4;
    color: #16a34a;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 99px;
}

.plan-free {
    background: #f1f5f9;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 99px;
}

.ver-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 99px;
}

.ver-ok {
    background: #f0fdf4;
    color: #16a34a;
}

.ver-pending {
    background: #fff7ed;
    color: #ea580c;
}

.act-btn {
    padding: 7px 12px;
    border-radius: 6px;
    border: none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.act-primary {
    background: #7c3aed;
    color: #fff;
}

.act-primary:hover {
    background: #6d28d9;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(3px);
}

.modal-box {
    background: #fff;
    border-radius: 10px;
    width: 440px;
    max-width: 92vw;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid #f1f5f9;
}

.modal-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.modal-close {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: none;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 20px 22px;
    text-align: center;
}

.ll-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    color: #fff;
    font-size: 26px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
}

.ll-name {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.ll-email {
    font-size: 12px;
    color: #94a3b8;
    margin: 4px 0 14px;
}

.info-block {
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px 14px;
    text-align: left;
    margin-bottom: 12px;
}

.ib-row {
    display: flex;
    gap: 12px;
    font-size: 13px;
    padding: 4px 0;
}

.ib-l {
    width: 80px;
    color: #94a3b8;
    font-weight: 500;
    flex-shrink: 0;
}

.ib-v {
    color: #0f172a;
    font-weight: 500;
}

.cccd-preview {
    border: 2px dashed #e2e8f0;
    border-radius: 8px;
    padding: 24px;
    color: #94a3b8;
    font-size: 36px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.cccd-preview span {
    font-size: 12px;
}

.modal-footer {
    display: flex;
    gap: 8px;
    padding: 14px 22px;
    border-top: 1px solid #f1f5f9;
}

.btn-cancel {
    flex: 1;
    padding: 9px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.btn-approve {
    flex: 2;
    padding: 9px;
    border-radius: 6px;
    border: none;
    background: #7c3aed;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-approve:hover {
    background: #6d28d9;
}

.approved-label {
    flex: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 700;
    color: #16a34a;
    background: #f0fdf4;
    border-radius: 6px;
    padding: 9px;
}

.modal-lg { width: 640px; max-width: 95vw; }

.doc-section { margin-top: 16px; text-align: left; }
.doc-title { font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
.doc-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; }
.doc-card { display: flex; flex-direction: column; gap: 4px; }
.doc-lbl { font-size: 11px; font-weight: 600; color: #64748b; }
.doc-img-wrap { position: relative; width: 100%; height: 95px; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; cursor: pointer; background: #f8fafc; }
.doc-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.2s; }
.doc-img-wrap:hover img { transform: scale(1.05); }
.img-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.4); color: #fff; display: flex; align-items: center; justify-content: center; gap: 4px; font-size: 11px; font-weight: 600; opacity: 0; transition: opacity 0.2s; }
.doc-img-wrap:hover .img-overlay { opacity: 1; }

.face-match-badge { display: flex; align-items: center; gap: 6px; background: #f0fdf4; color: #16a34a; font-size: 12px; padding: 8px 12px; border-radius: 6px; border: 1px solid #bbf7d0; margin-bottom: 10px; }

.empty-doc-msg { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #64748b; background: #f8fafc; padding: 12px 14px; border-radius: 8px; border: 1px dashed #e2e8f0; }

.gps-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; }
.gps-info { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
.gps-dec { font-size: 12px; font-weight: 700; color: #065f46; margin: 0; }
.gps-dec span { font-family: monospace; }
.gps-dms { font-size: 11px; color: #047857; margin: 2px 0 0; }
.btn-copy-gps { font-size: 11px; font-weight: 600; background: #fff; border: 1px solid #a7f3d0; color: #065f46; padding: 4px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
.btn-copy-gps:hover { background: #ecfdf5; }

.map-tabs { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; font-size: 11px; }
.map-tabs-lbl { color: #64748b; }
.map-tab-btn { padding: 3px 8px; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: 11px; cursor: pointer; }
.tab-active { background: #059669; border-color: #059669; color: #fff; font-weight: 700; }
.map-frame { border-radius: 6px; overflow: hidden; border: 1px solid #cbd5e1; background: #f1f5f9; }

/* Security Features CSS */
.doc-header-wrap { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px; }
.btn-kyc-toggle { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; transition: all 0.2s; }
.btn-locked { background: #7c3aed; color: #fff; }
.btn-locked:hover { background: #6d28d9; }
.btn-unlocked { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
.btn-unlocked:hover { background: #fca5a5; }

.kyc-blurred img { filter: blur(9px); pointer-events: none; }
.blur-lock-banner { position: absolute; inset: 0; background: rgba(15,23,42,0.35); color: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; font-size: 11px; font-weight: 700; backdrop-filter: blur(2px); cursor: pointer; z-index: 5; }
.blur-lock-banner i { font-size: 18px; color: #facc15; }

.watermark-overlay { position: absolute; inset: 0; pointer-events: none; display: flex; align-items: center; justify-content: center; overflow: hidden; z-index: 10; }
.watermark-overlay span { transform: rotate(-25deg); color: rgba(255, 255, 255, 0.45); font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; text-shadow: 0 0 4px rgba(0,0,0,0.7); white-space: nowrap; user-select: none; }
.zoom-watermark span { font-size: 14px; letter-spacing: 0.12em; text-shadow: 0 0 8px rgba(0,0,0,0.9); }
</style>
