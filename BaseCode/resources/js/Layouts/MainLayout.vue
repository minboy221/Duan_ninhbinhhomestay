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

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) + ' - ' + date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}
const getAvatarUrl = (avatar) => {
    if (!avatar) return '/anh/banner.png';
    if (avatar.startsWith('http') || avatar.startsWith('/') || avatar.startsWith('data:')) {
        return avatar;
    }
    return '/storage/' + avatar;
};
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
                    <button id="userBtn" class="user-btn" @click.stop="toggleDropdown" :style="user.avatar ? 'padding: 0; overflow: hidden; background: transparent;' : ''">
                        <img v-if="user.avatar" :src="getAvatarUrl(user.avatar)" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;" alt="">
                        <i v-else class="bi bi-person"></i>
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
                                <img :src="getAvatarUrl(user.avatar)" class="avatar" alt="">
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
                                    <span>Trang Admin</span>
                                </Link>
                            </li>
                            <li v-if="user.role === 'landlord'">
                                <Link :href="route('landlord.dashboard')"> <i class="bi bi-house-gear"></i>
                                    <span>Trang Chủ Trọ</span>
                                </Link>
                            </li>
                            <li v-if="user.role === 'user' && !auth.has_submitted_verification">
                                <Link :href="route('landlord.verify.create')"> <i class="bi bi-house-add"></i>
                                    <span>Đăng ký làm Chủ Trọ</span>
                                </Link>
                            </li>
                            <li v-if="user.role === 'user' && auth.has_submitted_verification">
                                <Link :href="route('landlord.verify.create')"> <i class="bi bi-hourglass-split"></i>
                                    <span>Hồ sơ Chủ Trọ (Chờ duyệt)</span>
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
    <!-- Popup thông báo góc phải dưới -->
    <Teleport to="body">
        <Transition name="toast-slide">
            <div v-if="showWelcomePopup" style="position: fixed; bottom: 30px; right: 30px; z-index: 99999;">
                <div style="background: white; border-radius: 16px; width: 380px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; overflow: hidden; position: relative;">
                    
                    <!-- Thanh màu báo hiệu (xanh/đỏ) -->
                    <div :style="latestNotification?.type === 'App\\Notifications\\LandlordRejected' ? 'height: 4px; background: linear-gradient(90deg, #ef4444, #f87171);' : 'height: 4px; background: linear-gradient(90deg, #22c55e, #4ade80);'"></div>
                    
                    <div style="padding: 24px;">
                        <!-- Nút tắt (X) -->
                        <button @click="closePopup" style="position: absolute; top: 16px; right: 16px; background: transparent; border: none; color: #94a3b8; cursor: pointer; transition: color 0.2s; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%;" onmouseover="this.style.color='#ef4444'; this.style.background='#fef2f2'" onmouseout="this.style.color='#94a3b8'; this.style.background='transparent'">
                            <i class="bi bi-x-lg"></i>
                        </button>

                        <div style="display: flex; gap: 16px; align-items: flex-start;">
                            <!-- Icon -->
                            <div :style="latestNotification?.type === 'App\\Notifications\\LandlordRejected' ? 'flex-shrink: 0; width: 48px; height: 48px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ef4444;' : 'flex-shrink: 0; width: 48px; height: 48px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #22c55e;'">
                                <i :class="latestNotification?.type === 'App\\Notifications\\LandlordRejected' ? 'bi bi-x-circle-fill' : 'bi bi-check-circle-fill'" style="font-size: 24px;"></i>
                            </div>
                            
                            <!-- Content -->
                            <div style="flex: 1;">
                                <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 8px 0; padding-right: 15px; font-family: 'Inter', sans-serif; line-height: 1.4;">{{ latestNotification?.data?.title }}</h3>
                                
                                <p style="font-size: 14px; color: #475569; margin: 0 0 12px 0; line-height: 1.5;">{{ latestNotification?.data?.message }}</p>
                                
                                <!-- Thời gian -->
                                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #94a3b8; margin-bottom: 16px; font-weight: 500;">
                                    <i class="bi bi-clock"></i>
                                    <span>{{ formatDateTime(latestNotification?.created_at) }}</span>
                                </div>
                                
                                <!-- Hành động -->
                                <div style="display: flex; gap: 10px;">
                                    <Link :href="latestNotification?.data?.url" @click="closePopup" style="flex: 1; text-align: center; padding: 10px 0; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; color: #3b82f6; font-weight: 600; text-decoration: none; font-size: 13px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.color='#2563eb'" onmouseout="this.style.background='#f8fafc'; this.style.color='#3b82f6'">
                                        {{ latestNotification?.type === 'App\\Notifications\\LandlordRejected' ? 'Xem lý do chi tiết' : 'Truy cập trang quản lý' }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style>
/* Animation cho toast */
.toast-slide-enter-active {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.toast-slide-leave-active {
    transition: all 0.3s ease-in;
}
.toast-slide-enter-from {
    transform: translateX(120%);
    opacity: 0;
}
.toast-slide-leave-to {
    transform: translateX(120%);
    opacity: 0;
}
</style>