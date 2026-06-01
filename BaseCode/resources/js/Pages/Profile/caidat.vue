<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { props } = usePage();
const user = computed(() => props.auth.user);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
    otp: '',
});

const passwordSuccess = ref(false);
const showOtpStep = ref(false);

const requestOtp = () => {
    passwordForm.post(route('password.request-otp'), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.status === 'otp-sent' || page.props.flash?.status === 'otp-sent') {
                showOtpStep.value = true;
            }
            // fallback in case inertia didn't pass status nicely
            else if (!passwordForm.hasErrors) {
                showOtpStep.value = true;
            }
        },
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
};

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showOtpStep.value = false;
            passwordSuccess.value = true;
            setTimeout(() => passwordSuccess.value = false, 3000);
        },
        onError: () => {
            passwordForm.otp = '';
        },
    });
};
</script>

<template>

    <Head title="Cài Đặt Tài Khoản | Ninh Bình HomeStay" />
    <UserLayout>
        <!--phần hiển thị phần cài đặt -->
        <div class="bao_item">
            <div class="infor_noidung">
                <div class="title_noio">
                    <h2>CÀI ĐẶT TÀI KHOẢN</h2>
                    <p>Quản lý bảo mật,thông báo và quyền riêng tư của bạn.</p>
                </div>
                <div class="password-form">
                    <h4>Đổi mật khẩu</h4>
                    <div v-if="passwordSuccess" style="color: #155724; background-color: #d4edda; padding: .75rem 1.25rem; margin-bottom: 1rem; border-radius: .5rem; font-size: 14px; font-weight: 500;">
                        Cập nhật mật khẩu thành công!
                    </div>
                    <form @submit.prevent="showOtpStep ? updatePassword() : requestOtp()">
                        <!-- Các trường mật khẩu -->
                        <div v-show="!showOtpStep">
                            <div class="row" style="margin-bottom: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label>Mật khẩu hiện tại:</label>
                                    <input v-model="passwordForm.current_password" type="password" placeholder="••••••••">
                                    <span v-if="passwordForm.errors.current_password" style="color: #ef4444; font-size: 12px; display: block; margin-top: 5px;">{{ passwordForm.errors.current_password }}</span>
                                </div>

                                <div class="form-group" style="flex: 1;">
                                    <label>Mật khẩu mới:</label>
                                    <input v-model="passwordForm.password" type="password" placeholder="••••••••">
                                    <span v-if="passwordForm.errors.password" style="color: #ef4444; font-size: 12px; display: block; margin-top: 5px;">{{ passwordForm.errors.password }}</span>
                                </div>
                            </div>
                            <div class="row" style="margin-bottom: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label>Xác nhận mật khẩu mới:</label>
                                    <input v-model="passwordForm.password_confirmation" type="password" placeholder="••••••••">
                                </div>
                                <div class="form-group" style="flex: 1; visibility: hidden;">
                                    <!-- Empty block to keep flex layout balanced -->
                                </div>
                            </div>
                        </div>

                        <!-- Trường OTP -->
                        <div v-if="showOtpStep" style="margin-bottom: 20px; background-color: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div style="margin-bottom: 15px;">
                                <h5 style="color: #0f172a; font-weight: 600; margin-bottom: 5px;">Nhập mã xác minh</h5>
                                <p style="color: #64748b; font-size: 13px; line-height: 1.5;">Hệ thống đã gửi 1 mã OTP gồm 6 số về email của bạn. Vui lòng kiểm tra và nhập mã để hoàn tất việc đổi mật khẩu.</p>
                            </div>
                            <div class="form-group" style="width: 100%; max-width: 300px;">
                                <input v-model="passwordForm.otp" type="text" placeholder="Nhập mã 6 số" style="letter-spacing: 5px; font-weight: bold; font-size: 18px; text-align: center;">
                                <span v-if="passwordForm.errors.otp" style="color: #ef4444; font-size: 12px; display: block; margin-top: 5px;">{{ passwordForm.errors.otp }}</span>
                            </div>
                            <button type="button" @click="showOtpStep = false" style="background: none; border: none; color: #00628c; font-size: 13px; font-weight: 600; cursor: pointer; margin-top: 10px; padding: 0;">&larr; Quay lại sửa mật khẩu</button>
                        </div>

                        <button class="btn_save" type="submit" :disabled="passwordForm.processing" :style="passwordForm.processing ? 'opacity: 0.7; cursor: not-allowed;' : ''">
                            <span v-if="!showOtpStep">{{ passwordForm.processing ? 'Đang gửi mã...' : 'Tiếp tục' }}</span>
                            <span v-else>{{ passwordForm.processing ? 'Đang cập nhật...' : 'Xác nhận & Cập nhật' }}</span>
                        </button>
                    </form>
                </div>
                <!-- phần upload ảnh cccd -->
                <div class="upload_cccd">
                    <h3>Xác thực thông tin (CCCD)</h3>
                    <p class="upload-desc">Vui lòng tải lên ảnh rõ nét của hai mặt thẻ Căn cước công dân để xác
                        thực
                        tài khoản.</p>

                    <div class="cccd-grid">
                        <!-- MẶT TRƯỚC -->
                        <div class="cccd-item">
                            <div class="cccd-card">
                                <div class="card-header">
                                    <p>Mặt trước CCCD</p>
                                </div>
                                <div class="upload-container" id="containerFront">
                                    <input type="file" id="cccdFront" accept="image/*" hidden>
                                    <label for="cccdFront" class="upload-label">
                                        <div class="upload-icon-box">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                        </div>
                                        <div class="upload-text">
                                            <span class="main-text">Tải ảnh mặt trước</span>
                                            <span class="sub-text">Hỗ trợ JPG, PNG, WEBP</span>
                                        </div>
                                    </label>
                                    <div class="preview-box" id="previewBoxFront">
                                        <img id="previewFront" src="" alt="Mặt trước CCCD">
                                        <div class="preview-actions">
                                            <button type="button" class="btn-remove" data-target="cccdFront"
                                                title="Xóa ảnh">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <label for="cccdFront" class="btn-change" title="Đổi ảnh">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- MẶT SAU -->
                        <div class="cccd-item">
                            <div class="cccd-card">
                                <div class="card-header">
                                    <p>Mặt sau CCCD</p>
                                </div>
                                <div class="upload-container" id="containerBack">
                                    <input type="file" id="cccdBack" accept="image/*" hidden>
                                    <label for="cccdBack" class="upload-label">
                                        <div class="upload-icon-box">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                        </div>
                                        <div class="upload-text">
                                            <span class="main-text">Tải ảnh mặt sau</span>
                                            <span class="sub-text">Hỗ trợ JPG, PNG, WEBP</span>
                                        </div>
                                    </label>
                                    <div class="preview-box" id="previewBoxBack">
                                        <img id="previewBack" src="" alt="Mặt sau CCCD">
                                        <div class="preview-actions">
                                            <button type="button" class="btn-remove" data-target="cccdBack"
                                                title="Xóa ảnh">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <label for="cccdBack" class="btn-change" title="Đổi ảnh">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn_save" type="submit">
                        Lưu CCCD
                    </button>
                </div>
            </div>
            <!-- phần xem thiết bị đã đăng nhập -->
            <div class="bao_thietbi">
                <h4>Thiết bị đã đăng nhập</h4>
                <div class="thietbi_item">
                    <i class="bi bi-phone"></i>
                    <div class="infor_thietbi">
                        <p>Iphone 15 Pro Max - Ninh Bình</p>
                        <span>2 ngày trước / APP</span>
                    </div>
                </div>
            </div>
            <!-- phần xoá tài khoản -->
            <div class="bao_delete">
                <div class="title_delete">
                    <span><i class="bi bi-x-circle-fill"></i></span>
                    <h4>Xoá tài khoản</h4>
                </div>
                <div class="item_delete">
                    <div class="infor-delete">
                        <h2>Khu vực nguy hiểm</h2>
                        <p>Hành động này không thể hoàn tác. Vui lòng cân nhắc kỹ trước khi xoá</p>
                    </div>
                    <button class="delete_btn">
                        Xoá tài khoản
                    </button>
                </div>
            </div>
        </div>
    </UserLayout>
</template>
<style scoped>
@import "../../css/caidat.css";
@import '../../css/responsive/responsivetranguser.css';
</style>