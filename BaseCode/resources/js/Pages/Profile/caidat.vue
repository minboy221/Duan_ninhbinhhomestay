<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
    otp: '',
});

//nhận prop session từ controller 
const props = defineProps({
    sessions: { type: Array, default: () => [] },
});

//quản lý modal & form xoá tài khoản
const showDeleteModal = ref(false);
const deleteForm = useForm({
    password: '',
});

const confirmDeleteAccount = () => {
    deleteForm.delete(route('profile.destroy-account'), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onError: () => {
            deleteForm.reset('password');
        },
    });
};

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
                    <div v-if="passwordSuccess"
                        style="color: #155724; background-color: #d4edda; padding: .75rem 1.25rem; margin-bottom: 1rem; border-radius: 0.375rem; font-size: 14px; font-weight: 500;">
                        Cập nhật mật khẩu thành công!
                    </div>
                    <form @submit.prevent="showOtpStep ? updatePassword() : requestOtp()">
                        <!-- Các trường mật khẩu -->
                        <div v-show="!showOtpStep">
                            <div class="row" style="margin-bottom: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label>Mật khẩu hiện tại:</label>
                                    <input v-model="passwordForm.current_password" type="password"
                                        placeholder="••••••••">
                                    <span v-if="passwordForm.errors.current_password"
                                        style="color: #ef4444; font-size: 12px; display: block; margin-top: 5px;">{{
                                        passwordForm.errors.current_password }}</span>
                                </div>

                                <div class="form-group" style="flex: 1;">
                                    <label>Mật khẩu mới:</label>
                                    <input v-model="passwordForm.password" type="password" placeholder="••••••••">
                                    <span v-if="passwordForm.errors.password"
                                        style="color: #ef4444; font-size: 12px; display: block; margin-top: 5px;">{{
                                        passwordForm.errors.password }}</span>
                                </div>
                            </div>
                            <div class="row" style="margin-bottom: 20px;">
                                <div class="form-group" style="flex: 1;">
                                    <label>Xác nhận mật khẩu mới:</label>
                                    <input v-model="passwordForm.password_confirmation" type="password"
                                        placeholder="••••••••">
                                </div>
                                <div class="form-group" style="flex: 1; visibility: hidden;">
                                    <!-- Empty block to keep flex layout balanced -->
                                </div>
                            </div>
                        </div>

                        <!-- Trường OTP -->
                        <div v-if="showOtpStep"
                            style="margin-bottom: 20px; background-color: #f8fafc; padding: 20px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <div style="margin-bottom: 15px;">
                                <h5 style="color: #0f172a; font-weight: 600; margin-bottom: 5px;">Nhập mã xác minh</h5>
                                <p style="color: #64748b; font-size: 13px; line-height: 1.5;">Hệ thống đã gửi 1 mã OTP
                                    gồm 6 số về email của bạn. Vui lòng kiểm tra và nhập mã để hoàn tất việc đổi mật
                                    khẩu.</p>
                            </div>
                            <div class="form-group" style="width: 100%; max-width: 300px;">
                                <input v-model="passwordForm.otp" type="text" placeholder="Nhập mã 6 số"
                                    style="letter-spacing: 5px; font-weight: bold; font-size: 18px; text-align: center;">
                                <span v-if="passwordForm.errors.otp"
                                    style="color: #ef4444; font-size: 12px; display: block; margin-top: 5px;">{{
                                    passwordForm.errors.otp }}</span>
                            </div>
                            <button type="button" @click="showOtpStep = false"
                                style="background: none; border: none; color: #00628c; font-size: 13px; font-weight: 600; cursor: pointer; margin-top: 10px; padding: 0;">&larr;
                                Quay lại sửa mật khẩu</button>
                        </div>

                        <button class="btn_save" type="submit" :disabled="passwordForm.processing"
                            :style="passwordForm.processing ? 'opacity: 0.7; cursor: not-allowed;' : ''">
                            <span v-if="!showOtpStep">{{ passwordForm.processing ? 'Đang gửi mã...' : 'Tiếp tục'
                                }}</span>
                            <span v-else>{{ passwordForm.processing ? 'Đang cập nhật...' : 'Xác nhận & Cập nhật'
                                }}</span>
                        </button>
                    </form>
                </div>
            </div>
            <!-- phần xem thiết bị đã đăng nhập -->
            <div class="bao_thietbi">
                <h4>Thiết bị đã đăng nhập</h4>

                <div v-if="sessions.length === 0" style="color: #64748b; font-size: 13px; padding: 10px 0;">
                    Chưa có dữ liệu thiết bị khác.
                </div>

                <div v-for="session in sessions" :key="session.id" class="thietbi_item">
                    <i :class="['iOS', 'Android'].includes(session.platform) ? 'bi bi-phone' : 'bi bi-laptop'"></i>
                    <div class="infor_thietbi">
                        <p>
                            {{ session.platform }} - {{ session.browser }}
                            <span v-if="session.is_current_device"
                                style="background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 4px; margin-left: 8px;">
                                Thiết bị này
                            </span>
                        </p>
                        <span>IP: {{ session.ip_address }} • {{ session.last_active }}</span>
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
                        <p>Hành động này không thể hoàn tác. Vui lòng cân nhắc kỹ trước khi xoá.</p>
                    </div>
                    <button class="delete_btn" type="button" @click="showDeleteModal = true">
                        Xoá tài khoản
                    </button>
                </div>
            </div>

            <!-- Modal Popup Xác Nhận Xóa Tài Khoản -->
        </div>
    </UserLayout>
    <div v-if="showDeleteModal"
        style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
        <div
            style="background: #fff; border-radius: 12px; padding: 24px; max-width: 450px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <h3 style="font-size: 18px; font-weight: bold; color: #991b1b; margin-bottom: 8px;">Xác nhận xóa tài khoản
            </h3>
            <p style="font-size: 13px; color: #475569; margin-bottom: 16px; line-height: 1.5;">
                Bạn có chắc chắn muốn xóa tài khoản? Tất cả dữ liệu cá nhân của bạn sẽ bị xóa vĩnh viễn và không thể
                khôi phục. Vui lòng nhập mật khẩu hiện tại để xác nhận.
            </p>

            <form @submit.prevent="confirmDeleteAccount">
                <div style="margin-bottom: 16px;">
                    <label
                        style="display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px;">Mật
                        khẩu hiện tại:</label>
                    <input v-model="deleteForm.password" type="password" placeholder="••••••••"
                        style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px;">
                    <span v-if="deleteForm.errors.password"
                        style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{
                        deleteForm.errors.password }}</span>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" @click="showDeleteModal = false"
                        style="padding: 8px 16px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #475569; font-weight: 600; cursor: pointer;">
                        Hủy
                    </button>
                    <button type="submit" :disabled="deleteForm.processing"
                        style="padding: 8px 16px; border: none; border-radius: 8px; background: #dc2626; color: #fff; font-weight: bold; cursor: pointer;">
                        {{ deleteForm.processing ? 'Đang xóa...' : 'Đồng ý xóa' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
<style scoped>
@import "../../css/caidat.css";
@import '../../css/responsive/responsivetranguser.css';
</style>