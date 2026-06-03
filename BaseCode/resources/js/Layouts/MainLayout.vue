<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
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

const showWelcomePopup = ref(false)
const latestNotification = ref(null)

onMounted(() => {
    if (auth.value.notifications && auth.value.notifications.length > 0) {
        const notif = auth.value.notifications[0]
        const dismissed = sessionStorage.getItem('dismissed_notification_' + notif.id)
        if (!dismissed) {
            latestNotification.value = notif
            showWelcomePopup.value = true
        }
    }
})

const closePopup = () => {
    showWelcomePopup.value = false
    if (latestNotification.value) {
        sessionStorage.setItem('dismissed_notification_' + latestNotification.value.id, 'true')
    }
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
                    <button id="bellBtn" class="thongbao-btn" style="position: relative;" @click.stop="toggleNotification">
                        <i class="bi bi-bell"></i>
                        <span v-if="auth.notifications && auth.notifications.length > 0" style="position: absolute; top: -2px; right: -2px; background: red; color: white; border-radius: 50%; font-size: 10px; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                            {{ auth.notifications.length }}
                        </span>
                    </button>
                    <button id="userBtn" class="user-btn" @click.stop="toggleDropdown">
                        <i class="bi bi-person"></i>
                    </button>

                    <div id="notificationBox" class="notification-box" :class="{ show: showNotification }" @click.stop>
                        <p class="title">Thông báo</p>
                        <ul v-if="auth.notifications && auth.notifications.length > 0">
                            <li v-for="notif in auth.notifications" :key="notif.id" class="group relative" style="padding: 12px; border-bottom: 1px solid #eee;">
                                <Link :href="notif.data.url" style="display: flex; flex-direction: column; color: inherit; text-decoration: none; padding-right: 24px;">
                                    <strong style="color: #0f172a; font-size: 14px; margin-bottom: 4px;">{{ notif.data.title }}</strong>
                                    <span style="font-size: 12px; color: #64748b; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ notif.data.message }}</span>
                                </Link>
                                <button type="button" @click.stop="router.post(route('notifications.read', notif.id), {}, {preserveScroll: true})" 
                                    class="absolute right-3 top-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-blue-100 text-gray-500 hover:text-blue-600 rounded flex items-center justify-center"
                                    style="width: 24px; height: 24px; border: none; cursor: pointer;"
                                    title="Đánh dấu đã đọc">
                                    <i class="bi bi-check2"></i>
                                </button>
                            </li>
                        </ul>
                        <ul v-else style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px 0;">
                            <img src="/anh/thongbao.png" alt="" style="width: 80px; margin-bottom: 10px;">
                            <span style="color: #64748b; font-size: 13px;">Hiện chưa có thông báo nào!</span>
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
                            <li v-if="user.role === 'landlord'">
                                <Link :href="route('landlord.dashboard')"> <i class="bi bi-house-gear"></i>
                                    <span>Trang Chủ Trọ</span>
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
                    <li class="border-t mt-4 pt-4" v-if="user.role === 'admin'">
                        <Link :href="route('admin.dashboard')" @click="closeDrawer"><i class="bi bi-speedometer2"></i> Trang Quản Trị</Link>
                    </li>
                    <li class="border-t mt-4 pt-4" v-else-if="user.role === 'landlord'">
                        <Link :href="route('landlord.dashboard')" @click="closeDrawer"><i class="bi bi-house-gear"></i> Trang Chủ Trọ</Link>
                    </li>
                    <li :class="['border-t mt-4 pt-4', (user.role === 'admin' || user.role === 'landlord') ? '!mt-2 !pt-2 !border-none' : '']">
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
    <!-- Popup thông báo giữa màn hình -->
    <Teleport to="body">
        <div v-if="showWelcomePopup" class="modal-overlay" style="position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);" @click.self="closePopup">
            <div style="background: white; border-radius: 16px; padding: 40px 30px; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: popupIn 0.3s ease-out;">
                <div :style="latestNotification?.type === 'App\\Notifications\\LandlordRejected' ? 'width: 80px; height: 80px; background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 10px 15px -3px rgba(239,68,68,0.3);' : 'width: 80px; height: 80px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 10px 15px -3px rgba(34,197,94,0.3);'">
                    <i :class="latestNotification?.type === 'App\\Notifications\\LandlordRejected' ? 'bi bi-x-circle' : 'bi bi-check2-circle'" style="color: white; font-size: 40px;"></i>
                </div>
                <h3 style="font-size: 24px; font-weight: 800; color: #1e293b; margin-bottom: 12px; font-family: 'Inter', sans-serif;">{{ latestNotification?.data?.title }}</h3>
                <p style="font-size: 15px; color: #64748b; margin-bottom: 30px; line-height: 1.6;">{{ latestNotification?.data?.message }}</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button @click="closePopup" style="flex: 1; padding: 12px 0; border-radius: 10px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">Đóng</button>
                    <Link :href="latestNotification?.data?.url" @click="closePopup" style="flex: 1; padding: 12px 0; border-radius: 10px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; font-weight: 600; text-decoration: none; display: inline-block; box-shadow: 0 4px 6px -1px rgba(59,130,246,0.3); transition: all 0.2s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                        {{ latestNotification?.type === 'App\\Notifications\\LandlordRejected' ? 'Chi tiết' : 'Trang Chủ Trọ' }}
                    </Link>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>
@keyframes popupIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>