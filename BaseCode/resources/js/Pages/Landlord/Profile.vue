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
    address: props.userData?.boardingHouse?.address_detail || props.userData?.boarding_house?.address_detail || "",
    invoice_billing_day: props.userData?.boardingHouse?.invoice_billing_day || props.userData?.boarding_house?.invoice_billing_day || 25,
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
    const f = e.target.files[0]
    if (!f) return
    if (field === 'avatar') {
        profile.avatarFile = f
    }
    const reader = new FileReader()
    reader.onload = (ev) => profile[field] = ev.target.result
    reader.readAsDataURL(f)
}

const form = useForm({
    name: '',
    phone: '',
    email: '',
    invoice_billing_day: 25,
})

const saveInfo = () => {
    form.name = profile.name
    form.phone = profile.phone
    form.email = profile.email
    form.invoice_billing_day = profile.invoice_billing_day
    
    form.post(route('landlord.profile.update'), {
        onSuccess: () => {
            showSuccess('Lưu thông tin chủ trọ thành công!')
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
                            <label class="form-label">Ngày chốt hóa đơn hàng tháng</label>
                            <select v-model="profile.invoice_billing_day" class="form-input">
                                <option v-for="day in 31" :key="day" :value="day">
                                    Ngày {{ day }} hàng tháng
                                </option>
                            </select>
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
                            <div class="doc-label">Hợp đồng kinh doanh / Sổ đỏ</div>
                            <div class="doc-upload doc-wide" :class="{ 'doc-uploaded': profile.businessLicense }">
                                <img v-if="profile.businessLicense" :src="profile.businessLicense" class="doc-img" />
                                <div v-else class="doc-placeholder"><i class="bi bi-file-earmark-text"></i><span>Chưa có file</span></div>
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
@import '../../css/profile_landbord.css';
</style>
