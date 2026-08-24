<script setup>
import { Link, usePage, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import MainLayout from "@/Layouts/MainLayout.vue";
import { getAvatarUrl, getRoomImageUrl } from "@/Utils/media";

const { props } = usePage();
const user = computed(() => props.auth.user);

const isVerified = computed(() => {
    if (user.value?.role === "admin" || user.value?.role === "landlord") {
        return true;
    }
    //tài khoản xác thực khi có số điện thoại và số cccd hoặc địa chỉ
    return !!(
        user.value?.phone &&
        (user.value?.cccd_number || user.value?.address)
    );
});

const avatarForm = useForm({
    avatar: null,
});

const uploadAvatar = (e) => {
    if (e.target.files[0]) {
        avatarForm.avatar = e.target.files[0];
        avatarForm.post(route("profile.avatar.update"), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <MainLayout>
        <div class="layout">
            <div class="left_section">
                <div class="bao-user">
                    <div class="img_user">
                        <img id="avatarPreview" :src="getAvatarUrl(user.avatar)" alt="" />

                        <!-- input ẩn -->
                        <input type="file" @change="uploadAvatar" id="avatarInput" accept="image/*" hidden />

                        <!-- nút camera -->
                        <label for="avatarInput" class="change-avatar">
                            <i class="bi bi-camera"></i>
                        </label>
                    </div>
                    <div class="infor_user">
                        <div class="name_user">
                            <p>{{ user.name }}</p>
                        </div>
                        <div class="id_user">
                            <p>ID:{{ user.id.toString().padStart(6, "0") }}</p>
                        </div>
                        <div class="xacthuc_user">
                            <p v-if="isVerified" class="kyc-approved">
                                <i class="bi bi-check2-circle"></i>
                                <span>Tài khoản đã xác thực</span>
                            </p>
                            <p v-else class="kyc-unverified"
                                title="Vui lòng cập nhật Số điện thoại và CCCD để xác thực tài khoản">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Tài khoản chưa xác thực</span>
                            </p>
                        </div>
                    </div>
                    <div class="menu_nguoidung">
                        <ul>
                            <li>
                                <Link :href="route('tranguser')" :class="{
                                    active: route().current('tranguser'),
                                }">
                                    <i class="bi bi-person-circle"></i>
                                    <span>Quản Lý Thông Tin</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('quanlynoio')" :class="{
                                    active: route().current('quanlynoio'),
                                }">
                                    <i class="bi bi-house"></i>
                                    <span>Quản Lý Nơi ở</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('profile.appointments')" :class="{
                                    active: route().current(
                                        'profile.appointments',
                                    ),
                                }">
                                    <i class="bi bi-calendar2-check"></i>
                                    <span>Lịch Hẹn Xem Phòng</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('reports.index')" :class="{
                                    active: route().current(
                                        'reports.index',
                                    ),
                                }">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span>Lịch Sử Báo Cáo</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('caidatuser')" :class="{
                                    active: route().current('caidat'),
                                }">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span>Cài Đặt</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="#">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>Hướng Dẫn</span>
                                </Link>
                            </li>
                            <li class="logout">
                                <Link :href="route('logout')" method="post">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Đăng xuất</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="right_section">
                <slot />
            </div>
        </div>
    </MainLayout>
    <!-- menu app -->
    <nav class="bottom-nav">
        <Link :href="route('tranguser')" :class="{ active: route().current('tranguser') }">
            <i class="bi bi-person-circle"></i>
            <span>Thông Tin</span>
        </Link>
        <Link :href="route('quanlynoio')" :class="{ active: route().current('quanlynoio') }">
            <i class="bi bi-house"></i>
            <span>Nơi ở</span>
        </Link>
        <Link :href="route('profile.appointments')" :class="{ active: route().current('profile.appointments') }">
            <i class="bi bi-calendar2-check"></i>
            <span>Lịch Hẹn</span>
        </Link>
        <Link :href="route('reports.index')" :class="{ active: route().current('reports.index') }">
            <i class="bi bi-exclamation-triangle"></i>
            <span>Báo Cáo</span>
        </Link>
        <Link :href="route('caidatuser')" :class="{ active: route().current('caidatuser') }">
            <i class="bi bi-gear-wide-connected"></i>
            <span>Cài Đặt</span>
        </Link>
        <Link class="logout" :href="route('logout')" method="post">
            <i class="bi bi-box-arrow-right"></i>
            <span>Đăng xuất</span>
        </Link>
    </nav>
</template>

<style scoped>
@import "../css/user.css";
@import "../css/responsive/responsivetranguser.css";
@import "../css/responsive/responsive.css";

.xacthuc_user p.kyc-approved {
    background: #e6f9f2 !important;
    color: #00b894 !important;
}

.xacthuc_user p.kyc-unverified {
    background: #fff9db !important;
    color: #f59f00 !important;
}
</style>
