<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useBackToTop, useDropdownMenu, useMobileDrawer } from '@/composables/main.js'

const { props } = usePage()
const auth = computed(() => props.auth)
const user = computed(() => auth.value.user)

const { showBtn, scrollToTop } = useBackToTop()
const { showDropdown, showNotification, toggleDropdown, toggleNotification } = useDropdownMenu()
const { isOpen, closeDrawer, toggleDrawer } = useMobileDrawer()

const logout = () => {
    router.post(route('logout'))
}
</script>
<template>
    <button id="backToTop" v-show="showBtn" @click="scrollToTop">
        <i class="bi bi-arrow-up"></i>
    </button>
    <header>
        <!-- NAVBAR -->
        <nav class="navbar">

            <div class="logo">
                <img src="/anh/logo.png" alt="logo">
            </div>

            <ul class="nav-menu" id="navMenu">
                <li>
                    <Link :href="route('home')">Trang Chủ</Link>
                </li>
                <li>
                    <Link :href="route('about')">Giới Thiệu</Link>
                </li>
                <li>
                    <Link :href="route('timtro')">Tìm Phòng Trọ</Link>
                </li>
                <li>
                    <Link :href="route('tintuc')">Tin Tức</Link>
                </li>
                <li>
                    <Link :href="route('lienhe')">Liên Hệ</Link>
                </li>
            </ul>

            <!-- Hamburger button (chỉ hiện trên mobile) -->


            <!-- phần dropdown -->
            <div class="user-menu">
                <!-- Hamburger button (chỉ hiện trên mobile) -->
                <button class="hamburger-btn" id="hamburgerBtn" @click="toggleDrawer">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <template v-if="user">
                    <button id="bellBtn" class="thongbao-btn" @click.stop="toggleNotification">
                        <i class="bi bi-bell"></i>
                    </button>
                    <button id="userBtn" class="user-btn" @click.stop="toggleDropdown">
                        <i class="bi bi-person"></i>
                    </button>

                    <div id="notificationBox" class="notification-box" :class="{ show: showNotification }" @click.stop>
                        <p class="title">Thông báo</p>
                        <ul>
                            <img src="/anh/thongbao.png" alt="">
                            <span>Hiện chưa có thông báo nào!</span>
                        </ul>
                    </div>

                    <div id="dropdown" class="dropdown" :class="{ show: showDropdown }" @click.stop>
                        <!-- PROFILE -->
                        <div class="dropdown-header">
                            <Link :href="route('tranguser')" class="profile-card">
                                <img src="/anh/banner.png" class="avatar" alt="">
                                <div class="info">
                                    <p class="name">{{ user.name }}</p>
                                    <p class="status">
                                        <i class="bi bi-check2"></i>
                                        <span>Tài khoản đã xác thực</span>
                                    </p>
                                    <p class="meta">{{ user.email }}</p>
                                </div>
                            </Link>
                        </div>

                        <!-- MENU -->
                        <ul>
                            <li v-if="user.role === 'admin'">
                                <Link :href="route('admin.dashboard')"> <i class="bi bi-speedometer2"></i>
                                    <span>Trang Quản Trị</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('tranguser')"> <i class="bi bi-person-circle"></i>
                                    <span>Trang Cá Nhân</span>
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('quanlynoio')"><i class="bi bi-house"></i>
                                    <span>Quản Lý Nơi Ở</span>
                                </Link>
                            </li>
                            <li>
                                <a :href="route('caidatuser')"><i class="bi bi-gear-wide-connected"></i>
                                    <span>Cài Đặt</span>
                                </a>
                            </li>
                            <li class="logout">
                                <button @click="logout">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Đăng xuất</span>
                                </button>
                            </li>
                        </ul>

                    </div>
                </template>
                <template v-else>
                    <div class="auth-actions">
                        <Link :href="route('register')" class="btn-register">
                            Đăng ký
                        </Link>
                    </div>
                </template>
            </div>
        </nav>

        <!-- Mobile Menu Drawer -->
        <div class="mobile-overlay" id="mobileOverlay" :class="{ show: isOpen }" @click="closeDrawer"></div>
        <div class="mobile-drawer" id="mobileDrawer" :class="{ open: isOpen }">
            <button class="drawer-close" id="drawerClose" @click="closeDrawer"><i class="bi bi-x-lg"></i></button>
            <div class="drawer-logo">
                <img src="/anh/logo.png" alt="logo">
            </div>
            <ul class="drawer-menu">
                <li>
                    <Link :href="route('home')" @click="closeDrawer"><i class="bi bi-house-door"></i> Trang Chủ</Link>
                </li>
                <li>
                    <Link :href="route('about')" @click="closeDrawer"><i class="bi bi-info-circle"></i> Giới Thiệu
                    </Link>
                </li>
                <li>
                    <Link :href="route('timtro')" @click="closeDrawer"><i class="bi bi-search"></i> Tìm Phòng Trọ</Link>
                </li>
                <li>
                    <Link :href="route('tintuc')" @click="closeDrawer"><i class="bi bi-newspaper"></i> Tin Tức</Link>
                </li>
                <li>
                    <Link :href="route('lienhe')" @click="closeDrawer"><i class="bi bi-telephone"></i> Liên Hệ</Link>
                </li>
                <template v-if="!user">
                    <li class="border-t mt-4 pt-4">
                        <Link :href="route('login')" @click="closeDrawer"><i class="bi bi-box-arrow-in-right"></i> Đăng
                            Nhập</Link>
                    </li>
                    <li>
                        <Link :href="route('register')" @click="closeDrawer"><i class="bi bi-person-plus"></i> Đăng Ký
                        </Link>
                    </li>
                </template>
                <template v-else>
                    <li class="border-t mt-4 pt-4">
                        <button @click="logout(); closeDrawer()"
                            style="background: none; border: none; color: inherit; padding: 0; font: inherit; cursor: pointer; display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-box-arrow-right"></i> Đăng Xuất
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </header>
    <main>
        <slot />
    </main>
    <!-- phần đăng ký -->
    <section class="dangky">
        <div class="baodangky">
            <div class="infor_dangky">
                <h2>Bạn đã sẵn sàng</h2>
                <p v-if="!user">Chỉ mất vài phút để khám phá và quản lý tài khoản Ninh Bình Home Stay MIỄN PHÍ.</p>
                <p v-else>Chỉ mất vài phút để khám phá và tìm kiếm phòng trọ phù hợp với Ninh Bình Home Stay MIỄN PHÍ.
                </p>
                <Link v-if="!user" class="btn_dangky" :href="route('register')">
                    ĐĂNG KÝ TÀI KHOẢN
                </Link>
                <Link v-else class="btn_dangky" :href="route('timtro')">
                    KHÁM PHÁ NGAY
                </Link>
            </div>

            <div class="image_dangky">
                <img src="/anh/dangky.png" alt="">
            </div>
        </div>
    </section>
    <!-- phần footer -->
    <footer class="footer">
        <div class="footer_container">

            <!-- cột 1 -->
            <div class="footer_col logo_col">
                <h2 class="title_foot">Ninh Bình HomeStay</h2>
                <p>
                    Chào mừng bạn đến với nền tảng tìm trọ.
                    Trải nghiệm nhanh chóng, tiện lợi và đáng tin cậy.
                </p>

                <div class="socials">
                    <a href="#">f</a>
                    <a href="#">t</a>
                    <a href="#">i</a>
                    <a href="#">p</a>
                </div>
            </div>

            <!-- cột 2 -->
            <div class="footer_col">
                <h3>Điều hướng</h3>
                <ul>
                    <li><Link :href="route('home')">Trang Chủ</Link></li>
                    <li><a href="timtro.html">Phòng trọ</a></li>
                    <li><a href="tintuc.html">Tin Tức</a></li>
                    <li><a href="lienhe.html">Liên hệ</a></li>
                </ul>
            </div>
            <!-- cột 4 -->
            <div class="footer_col">
                <h3>Theo dõi</h3>
                <p>Đăng ký để nhận tin mới nhất</p>

                <input type="text" placeholder="Email...">
                <button class="btn_sub">ĐĂNG KÝ</button>
            </div>

        </div>

        <!-- bottom -->
        <div class="footer_bottom">
            <p>© 2026 Ninh Bình Home Stay</p>
            <div>
                <Link :href="route('chitietdieukhoan')">Chính Sách</Link>
                <span>|</span>
                <Link :href="route('chitietdieukhoan')">Điều Khoản</Link>
            </div>
        </div>
    </footer>
</template>