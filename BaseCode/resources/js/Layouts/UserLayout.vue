<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue';

const { props } = usePage()
const user = computed(() => props.auth.user);
</script>

<template>
    <MainLayout>
        <div class="layout">
            <div class="left_section">
                <div class="bao-user">
                    <div class="img_user">
                        <img id="avatarPreview" src="/anh/banner.png" alt="">

                        <!-- input ẩn -->
                        <input type="file" id="avatarInput" accept="image/*" hidden>

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
                            <p>ID:{{ user.id.toString().padStart(6, '0') }}</p>
                        </div>
                        <div class="xacthuc_user">
                            <p><i class="bi bi-check2-circle"></i> <span>tài khoản đã được xác thực</span></p>
                        </div>
                    </div>
                    <div class="menu_nguoidung">
                        <ul>
                            <li>
                                <Link :href="route('tranguser')" :class="{ active: route().current('tranguser') }">
                                    <i class="bi bi-person-circle"></i>
                                    <span>Quản Lý Thông Tin</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('quanlynoio')" :class="{ active: route().current('quanlynoio') }">
                                    <i class="bi bi-house"></i>
                                    <span>Quản Lý Nơi ở</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('caidatuser')" :class="{ active: route().current('caidat') }">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    <span>Cài Đặt</span>
                                </Link>
                            </li>
                            <li>
                                <Link href="#"> <i class="bi bi-info-circle-fill"></i>
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
            <span>Quản Lý Thông Tin</span>
        </Link>
        <Link :href="route('quanlynoio')" :class="{ active: route().current('quanlynoio') }">
            <i class="bi bi-house"></i>
            <span>Quản Lý Nơi ở</span>
        </Link>
        <Link :href="route('caidatuser')" :class="{ active: route().current('caidatuser') }">
            <i class="bi bi-gear-wide-connected"></i>
            <span>Cài Đặt</span>
        </Link>
        <Link href="#"> <i class="bi bi-info-circle-fill"></i>
            <span>Hướng Dẫn</span>
        </Link>
        <Link class="logout" :href="route('logout')" method="post">
            <i class="bi bi-box-arrow-right"></i>
            <span>Đăng xuất</span>
        </Link>
    </nav>
</template>

<style scoped>
@import "../css/user.css";
@import '../css/responsive/responsivetranguser.css';
@import '../css/responsive/responsive.css';
</style>
