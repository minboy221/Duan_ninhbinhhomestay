<script setup>
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { ref, reactive, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { showSuccess } from '@/Utils/swal'

const props = defineProps({
    userData: {
        type: Object,
        default: () => ({}),
    },
});

const verifyStatus = ref("verified"); // Landlords are verified

const getFileUrl = (path) => {
    if (!path) return null;
    if (path.startsWith("http") || path.startsWith("data:")) return path;
    const parts = path.split("/");
    const filename = parts[parts.length - 1];
    let type = "";
    if (path.includes("/id_cards/")) type = "id_cards";
    else if (path.includes("/faces/")) type = "faces";
    else if (path.includes("/contracts/")) type = "contracts";
    else if (path.includes("/rooms/")) type = "rooms";

    if (type && filename) return `/files/private/${type}/${filename}`;
    return path;
};

const profile = reactive({
    name: props.userData?.name || "",
    phone: props.userData?.phone || "",
    email: props.userData?.email || "",
    address: props.userData?.boardingHouse?.address_detail || "",
    bio: "",
    bank_name: props.userData?.bank_name || "",
    bank_account_no: props.userData?.bank_account_no || "",
    bank_account_name: props.userData?.bank_account_name || "",
    cccdFront: getFileUrl(props.userData?.verification?.id_card_front),
    cccdBack: getFileUrl(props.userData?.verification?.id_card_back),
    faceAuthImage: getFileUrl(props.userData?.verification?.face_auth_image),
    avatar: props.userData?.avatar ? `/storage/${props.userData.avatar}` : null,
    businessLicense: props.userData?.boardingHouse?.contract_images
        ? getFileUrl(props.userData.boardingHouse.contract_images[0])
        : null,
    roomPhotos: props.userData?.boardingHouse?.room_images
        ? props.userData.boardingHouse.room_images.map((img) => getFileUrl(img))
        : [],
});

const handleFile = (field, e) => {
    const f = e.target.files[0];
    if (!f) return;
    const reader = new FileReader();
    reader.onload = (ev) => (profile[field] = ev.target.result);
    reader.readAsDataURL(f);
};

const form = useForm({
    name: "",
    phone: "",
    email: "",
    bank_name: "",
    bank_account_no: "",
    bank_account_name: "",
});

const saveInfo = () => {
    form.name = profile.name;
    form.phone = profile.phone;
    form.email = profile.email;
    form.bank_name = profile.bank_name;
    form.bank_account_no = profile.bank_account_no;
    form.bank_account_name = profile.bank_account_name;

    form.post(route("landlord.profile.update"), {
        onSuccess: () => {
            alert('Lưu thông tin chủ trọ thành công!')
        }
    })
}

const statusConfig = {
    unverified: {
        label: "Chưa Xác Minh",
        cls: "vs-unverified",
        icon: "bi-x-circle-fill",
        desc: "Vui lòng hoàn tất xác minh để đăng tin.",
    },
    pending: {
        label: "Chờ Duyệt",
        cls: "vs-pending",
        icon: "bi-hourglass-split",
        desc: "Hồ sơ đang được Admin xem xét. Thường 1-2 ngày làm việc.",
    },
    verified: {
        label: "Đã Xác Minh",
        cls: "vs-verified",
        icon: "bi-patch-check-fill",
        desc: "Tài khoản đã được xác minh. Bạn có thể đăng tin thoải mái.",
    },
};
</script>

<template>
    <LandlordLayout>
        <template #header-title>
            <h1 class="ll-header-title">Thông Tin Chủ Trọ</h1>
        </template>

        <div class="prof-wrap">
            <!-- Verify status banner -->
            <div :class="['verify-banner', statusConfig[verifyStatus].cls]">
                <i :class="['bi', statusConfig[verifyStatus].icon]"></i>
                <div class="vb-text">
                    <span class="vb-label">Trạng thái:
                        <strong>{{
                            statusConfig[verifyStatus].label
                            }}</strong></span>
                    <span class="vb-desc">{{
                        statusConfig[verifyStatus].desc
                        }}</span>
                </div>
            </div>

            <div class="prof-cols">
                <!-- Left: basic info -->
                <div class="prof-left">
                    <!-- Avatar -->
                    <div class="prof-card">
                        <div class="avatar-section">
                            <div class="avatar-box">
                                <img v-if="profile.avatar" :src="profile.avatar" class="avatar-img" />
                                <div v-else class="avatar-placeholder">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            </div>
                            <label class="avatar-change">
                                <input type="file" accept="image/*" @change="handleFile('avatar', $event)"
                                    style="display: none" />
                                <i class="bi bi-camera-fill"></i> Đổi ảnh
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Họ và tên *</label>
                            <input v-model="profile.name" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Số điện thoại *</label>
                            <input v-model="profile.phone" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input v-model="profile.email" class="form-input" type="email" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Địa chỉ nhà trọ</label>
                            <input v-model="profile.address" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tên ngân hàng (ví dụ: MBBank,
                                Vietcombank...)</label>
                            <input v-model="profile.bank_name" class="form-input"
                                placeholder="MBBank, Vietcombank, Techcombank..." />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Số tài khoản ngân hàng</label>
                            <input v-model="profile.bank_account_no" class="form-input" placeholder="0912345678" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tên chủ tài khoản ngân hàng (viết hoa không
                                dấu)</label>
                            <input v-model="profile.bank_account_name" class="form-input" placeholder="NGUYEN VAN A" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Giới thiệu bản thân</label>
                            <textarea v-model="profile.bio" class="form-input form-textarea" rows="3"
                                placeholder="Mô tả ngắn về bạn và nhà trọ..."></textarea>
                        </div>
                        <button @click="saveInfo" class="btn-save-info" :disabled="form.processing">
                            {{
                                form.processing
                                    ? "Đang lưu..."
                                    : "Lưu Thông Tin"
                            }}
                        </button>
                    </div>
                </div>

                <!-- Right: verification docs -->
                <div class="prof-right">
                    <div class="prof-card">
                        <h3 class="sec-title">
                            <i class="bi bi-shield-check"></i> Tài Liệu Xác Minh
                        </h3>
                        <p class="sec-desc">
                            Giấy tờ tùy thân và tài liệu đã được xác minh trên
                            hệ thống.
                        </p>

                        <!-- CCCD -->
                        <div class="doc-section">
                            <div class="doc-label">CCCD / CMND</div>
                            <div class="doc-row">
                                <div class="doc-upload" :class="{
                                    'doc-uploaded': profile.cccdFront,
                                }">
                                    <img v-if="profile.cccdFront" :src="profile.cccdFront" class="doc-img" />
                                    <div v-else class="doc-placeholder">
                                        <i class="bi bi-card-heading"></i><span>Mặt trước</span>
                                    </div>
                                </div>
                                <div class="doc-upload" :class="{
                                    'doc-uploaded': profile.cccdBack,
                                }">
                                    <img v-if="profile.cccdBack" :src="profile.cccdBack" class="doc-img" />
                                    <div v-else class="doc-placeholder">
                                        <i class="bi bi-card-heading"></i><span>Mặt sau</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Portrait -->
                        <div class="doc-section">
                            <div class="doc-label">Ảnh chân dung</div>
                            <div class="doc-upload doc-portrait" :class="{
                                'doc-uploaded': profile.faceAuthImage,
                            }">
                                <img v-if="profile.faceAuthImage" :src="profile.faceAuthImage" class="doc-img" />
                                <div v-else class="doc-placeholder">
                                    <i class="bi bi-person-bounding-box"></i><span>Ảnh chân dung rõ mặt</span>
                                </div>
                            </div>
                        </div>

                        <!-- Business license -->
                        <div class="doc-section">
                            <div class="doc-label">
                                Hợp đồng kinh doanh / Sổ đỏ
                            </div>
                            <div class="doc-upload doc-wide" :class="{
                                'doc-uploaded': profile.businessLicense,
                            }">
                                <img v-if="profile.businessLicense" :src="profile.businessLicense" class="doc-img" />
                                <div v-else class="doc-placeholder">
                                    <i class="bi bi-file-earmark-text"></i><span>Chưa có file</span>
                                </div>
                            </div>
                        </div>

                        <!-- Room photos -->
                        <div class="doc-section">
                            <div class="doc-label">Ảnh thực tế phòng trọ</div>
                            <div class="room-photo-grid" v-if="profile.roomPhotos.length > 0">
                                <img v-for="(src, i) in profile.roomPhotos" :key="i" :src="src" class="rp-img" />
                            </div>
                            <div v-else class="doc-placeholder mt-2" style="height: 100px">
                                <i class="bi bi-images"></i><span>Chưa có ảnh phòng</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandlordLayout>
</template>

<style scoped>
.prof-wrap {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Verify Banner */
.verify-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    border-radius: 8px;
    padding: 14px 20px;
    font-size: 14px;
}

.verify-banner i {
    font-size: 24px;
    flex-shrink: 0;
}

.vb-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.vb-label {
    font-weight: 600;
}

.vb-desc {
    font-size: 12px;
    opacity: 0.85;
}

.vs-unverified {
    background: #fef2f2;
    color: #b91c1c;
    border: 1.5px solid #fecaca;
}

.vs-pending {
    background: #fffbeb;
    color: #92400e;
    border: 1.5px solid #fcd34d;
}

.vs-verified {
    background: #f0fdf4;
    color: #065f46;
    border: 1.5px solid #6ee7b7;
}

/* Layout */
.prof-cols {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 20px;
    align-items: flex-start;
}

.prof-left,
.prof-right {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.prof-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid #f0fdf4;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* Avatar */
.avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f0fdf4;
}

.avatar-box {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #6ee7b7;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: #0f766e;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 36px;
}

.avatar-change {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: #f0fdf4;
    color: #0f766e;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid #d1fae5;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-label {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
}

.form-input {
    padding: 9px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 6px;
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

.btn-save-info {
    padding: 10px;
    background: #0f766e;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.btn-save-info:hover {
    background: #0d9488;
}

/* Docs */
.sec-title {
    font-size: 15px;
    font-weight: 700;
    color: #064e3b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 7px;
}

.sec-desc {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}

.doc-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-bottom: 14px;
    border-bottom: 1px solid #f0fdf4;
}

.doc-section:last-of-type {
    border-bottom: none;
}

.doc-label {
    font-size: 12px;
    font-weight: 700;
    color: #374151;
}

.doc-row {
    display: flex;
    gap: 10px;
}

.doc-upload {
    flex: 1;
    border: 2px dashed #d1fae5;
    border-radius: 6px;
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    overflow: hidden;
    transition: border-color 0.15s;
    position: relative;
}

.doc-upload:hover {
    border-color: #0f766e;
}

.doc-uploaded {
    border-color: #0f766e !important;
    border-style: solid !important;
}

.doc-portrait {
    height: 120px;
    max-width: 150px;
}

.doc-wide {
    width: 100%;
    height: 100px;
}

.doc-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    color: #9ca3af;
}

.doc-placeholder i {
    font-size: 28px;
    color: #6ee7b7;
}

.doc-placeholder span {
    font-size: 12px;
    text-align: center;
}

.doc-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.room-photo-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
}

.rp-img {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    border-radius: 6px;
}

.btn-submit-verify {
    padding: 12px;
    background: #0f766e;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-submit-verify:hover {
    background: #0d9488;
}

.pending-info {
    background: #fffbeb;
    color: #92400e;
    border-radius: 6px;
    padding: 12px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.verified-info {
    background: #f0fdf4;
    color: #065f46;
    border-radius: 6px;
    padding: 12px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

@media (max-width: 1024px) {
    .prof-cols {
        grid-template-columns: 1fr;
    }
}
</style>
