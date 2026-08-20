<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useBackToTop, useDropdownMenu, useMobileDrawer } from '@/composables/main.js'
import axios from 'axios'
import AppointmentCountdown from '@/Components/AppointmentCountdown.vue';
import AiChatAssistant from '@/Components/AiChatAssistant.vue';
import { useFcm } from '@/composables/useFcm';

const page = usePage()
const auth = computed(() => page.props.auth)
const user = computed(() => auth.value.user)
const {registerFcmToken} = useFcm();
const isVerified = computed(() => {
    if (user.value?.role === 'admin' || user.value?.role === 'landlord') {
        return true;
    }
    return !!(user.value?.phone && (user.value?.cccd_number || user.value?.address));
});

const { showBtn, scrollToTop } = useBackToTop()
const { showDropdown, showNotification, toggleDropdown, toggleNotification } = useDropdownMenu()
const { isOpen, closeDrawer, toggleDrawer } = useMobileDrawer()

const logout = () => {
    router.post(route('logout'))
}

const showWelcomePopup = ref(false)
const latestNotification = ref(null)
let pingInterval = null;
const showPropertyPrompt = ref(false)
const handleLandlordClick = (e) => {
    e.preventDefault()
    closeDrawer()
    const houses = auth.value.boarding_houses || []
    if (houses.length >= 2) {
        showPropertyPrompt.value = true
    } else {
        router.visit(route('landlord.dashboard'))
    }
}
const selectPropertyFromPrompt = (prop) => {
    showPropertyPrompt.value = false
    router.post(route('landlord.select-boarding-house'), {
        id: prop.id,
        redirect_to: route('landlord.dashboard')
    })
}

onMounted(() => {
    //tự động đăng ký FCM token cho khách thuê, người dùng
    if (user.value) {
        registerFcmToken();
    }
    // 1. Kiểm tra hiển thị popup thông báo
    if (auth.value.notifications && auth.value.notifications.length > 0) {
        const notif = auth.value.notifications[0]
        const dismissed = sessionStorage.getItem('dismissed_notification_' + notif.id)
        if (!dismissed) {
            latestNotification.value = notif
            showWelcomePopup.value = true
        }
    }

    // 2. Gửi tín hiệu Heartbeat ping
    if (user.value) {
        // Gửi ping ngay lập tức lúc vừa tải trang xong
        axios.post(route('user.ping')).catch(err => console.error("Heartbeat error:", err));

        // Thiết lập gửi ping định kỳ mỗi 1 phút
        pingInterval = setInterval(() => {
            axios.post(route('user.ping')).catch(err => console.error("Heartbeat error:", err));
        }, 60000);
    }

    if (user.value) {
        window.Echo.private(`App.Models.User.${user.value.id}`)
            .notification((notification) => {
                if (auth.value.notifications) {
                    auth.value.notifications.unshift({
                        id: notification.id,
                        data: {
                            title: notification.data.title,
                            message: notification.data.message,
                            type: notification.data.type,
                            url: notification.data.url
                        },
                        created_at: notification.created_at
                    });
                }
                //ghi nhận thông báo mới nhất để hiển thị popup
                latestNotification.value = {
                    id: notification.id,
                    type: notification.data.type,
                    data: {
                        title: notification.data.title,
                        message: notification.data.message,
                        url: notification.data.url
                    },
                    created_at: notification.created_at
                };
                showWelcomePopup.value = true;
                //âm thanh thông báo 
                try {
                    const audio = new Audio("https://assets.mixkit.co/active_storage/sfx/2869/2869-600.wav");
                    audio.volume = 0.5;
                    audio.play();
                } catch (e) {
                    console.log("Autoplay audio bloked");
                }
            });
    }
});

onUnmounted(() => {
    if (pingInterval) {
        clearInterval(pingInterval);
    }
    if (user.value) {
        window.Echo.leave(`App.Models.User.${user.value.id}`);
    }
});

const closePopup = () => {
    showWelcomePopup.value = false
    if (latestNotification.value) {
        sessionStorage.setItem('dismissed_notification_' + latestNotification.value.id, 'true')
    }
}

//phần kiểm tra thông báo có phải là từ chối/ huỷ lịch hẹn
const isRejection = computed(() => {
    if (!latestNotification.value)
        return false;
    const type = latestNotification.value.type;
    const message = latestNotification.value.data?.message || '';
    return type === 'App\\Notifications\\LandlordRejected' || (type === 'App\\Notifications\\AppointmentStatusUpdated' && (message.includes('từ chối') || message.includes('huỷ') || message.includes('hết hạn') || message.includes('quá giờ')));
});

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
                    <button id="bellBtn" class="thongbao-btn" style="position: relative;"
                        @click.stop="toggleNotification">
                        <i class="bi bi-bell"></i>
                        <span v-if="auth.notifications && auth.notifications.length > 0"
                            style="position: absolute; top: -2px; right: -2px; background: red; color: white; border-radius: 50%; font-size: 10px; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                            {{ auth.notifications.length }}
                        </span>
                    </button>
                    <button id="userBtn" class="user-btn" @click.stop="toggleDropdown"
                        :style="user.avatar ? 'padding: 0; overflow: hidden; background: transparent;' : ''">
                        <img v-if="user.avatar" :src="getAvatarUrl(user.avatar)"
                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;"
                            alt="">
                        <i v-else class="bi bi-person"></i>
                    </button>

                    <transition name="slide-fade">
                        <div v-if="showNotification" id="notificationBox" class="notification-box"
                            :class="{ show: true }" @click.stop>
                            <div class="flex items-center justify-between px-3 py-2 border-b">
                                <p class="title mb-0">Thông báo</p>
                                <button v-if="auth.notifications && auth.notifications.length > 0"
                                    @click.stop="router.post(route('notifications.read-all'), {}, { preserveScroll: true })"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                    Đọc tất cả
                                </button>
                            </div>
                            <ul v-if="auth.notifications && auth.notifications.length > 0">
                                <li v-for="notif in auth.notifications" :key="notif.id" class="group relative"
                                    style="padding: 12px; border-bottom: 1px solid #eee;">
                                    <Link :href="notif.data.url"
                                        style="display: flex; flex-direction: column; color: inherit; text-decoration: none; padding-right: 24px;">
                                        <strong style="color: #0f172a; font-size: 14px; margin-bottom: 4px;">{{
                                            notif.data.title }}</strong>
                                        <span
                                            style="font-size: 12px; color: #64748b; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{
                                                notif.data.message }}</span>
                                    </Link>
                                    <button type="button"
                                        @click.stop="router.post(route('notifications.read', notif.id), {}, { preserveScroll: true })"
                                        class="absolute right-3 top-3 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-100 hover:bg-blue-100 text-gray-500 hover:text-blue-600 rounded flex items-center justify-center"
                                        style="width: 24px; height: 24px; border: none; cursor: pointer;"
                                        title="Đánh dấu đã đọc">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                </li>
                            </ul>
                            <ul v-else
                                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px 0;">
                                <img src="/anh/thongbao.png" alt="" style="width: 80px; margin-bottom: 10px;">
                                <span style="color: #64748b; font-size: 13px;">Hiện chưa có thông báo nào!</span>
                            </ul>
                        </div>
                    </transition>

                    <div id="dropdown" class="dropdown" :class="{ show: showDropdown }" @click.stop>
                        <!-- PROFILE -->
                        <div class="dropdown-header">
                            <Link :href="route('tranguser')" class="profile-card">
                                <img :src="getAvatarUrl(user.avatar)" class="avatar" alt="">
                                <div class="info">
                                    <p class="name">{{ user.name }}</p>
                                    <p :class="['status', isVerified ? 'verified' : 'unverified']">
                                        <i
                                            :class="isVerified ? 'bi bi-check2-circle' : 'bi bi-exclamation-triangle'"></i>
                                        <span>
                                            {{ isVerified ? 'Tài khoản đã xác thực' : 'Tài khoản chưa xác thực' }}
                                        </span>
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
                                <a href="#" @click="handleLandlordClick"> <i class="bi bi-house-gear"></i>
                                    <span>Trang Chủ Trọ</span>
                                </a>
                            </li>
                            <li v-if="user.role === 'user' && !auth.has_active_contract && !auth.has_submitted_verification">
                                <Link :href="route('landlord.verify.create')"> <i class="bi bi-house-add"></i>
                                    <span>Đăng ký làm Chủ Trọ</span>
                                </Link>
                            </li>
                            <li v-if="user.role === 'user' && !auth.has_active_contract && auth.has_submitted_verification">
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
                                <Link :href="route('profile.appointments')"><i class="bi bi-calendar-check"></i>
                                    <span>Lịch Hẹn Xem Phòng</span>
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
                        <Link :href="route('admin.dashboard')" @click="closeDrawer"><i class="bi bi-speedometer2"></i>
                            Trang Quản Trị</Link>
                    </li>
                    <li class="border-t mt-4 pt-4" v-else-if="user.role === 'landlord'">
                        <a href="#" @click="handleLandlordClick"><i class="bi bi-house-gear"></i> Trang Chủ Trọ</a>
                    </li>
                    <li
                        :class="['border-t mt-4 pt-4', (user.role === 'admin' || user.role === 'landlord') ? '!mt-2 !pt-2 !border-none' : '']">
                        <Link :href="route('tranguser')" @click="closeDrawer"><i class="bi bi-person-circle"></i> Trang
                            Cá Nhân</Link>
                    </li>
                    <li class="mt-2">
                        <Link :href="route('profile.appointments')" @click="closeDrawer"><i
                                class="bi bi-calendar-check"></i> Lịch Hẹn Xem Phòng</Link>
                    </li>
                    <li class="mt-2">
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
        <AppointmentCountdown v-if="user" />
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
                    <li>
                        <Link :href="route('home')">Trang Chủ</Link>
                    </li>
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
                <div
                    style="background: white; border-radius: 8px; width: 380px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; overflow: hidden; position: relative;">

                    <!-- Thanh màu báo hiệu (xanh/đỏ) -->
                    <div
                        :style="isRejection ? 'height: 4px; background: linear-gradient(90deg, #ef4444, #f87171);' : 'height: 4px; background: linear-gradient(90deg, #22c55e, #4ade80);'">
                    </div>

                    <div style="padding: 24px;">
                        <!-- Nút tắt (X) -->
                        <button @click="closePopup"
                            style="position: absolute; top: 16px; right: 16px; background: transparent; border: none; color: #94a3b8; cursor: pointer; transition: color 0.2s; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%;"
                            onmouseover="this.style.color='#ef4444'; this.style.background='#fef2f2'"
                            onmouseout="this.style.color='#94a3b8'; this.style.background='transparent'">
                            <i class="bi bi-x-lg"></i>
                        </button>

                        <div style="display: flex; gap: 16px; align-items: flex-start;">
                            <!-- Icon -->
                            <div
                                :style="isRejection ? 'flex-shrink: 0; width: 48px; height: 48px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ef4444;' : 'flex-shrink: 0; width: 48px; height: 48px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #22c55e;'">
                                <i :class="isRejection ? 'bi bi-x-circle-fill' : 'bi bi-check-circle-fill'"
                                    style="font-size: 24px;"></i>
                            </div>


                            <!-- Content -->
                            <div style="flex: 1;">
                                <h3
                                    style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 8px 0; padding-right: 15px; font-family: 'Inter', sans-serif; line-height: 1.4;">
                                    {{ latestNotification?.data?.title }}</h3>

                                <p style="font-size: 14px; color: #475569; margin: 0 0 12px 0; line-height: 1.5;">{{
                                    latestNotification?.data?.message }}</p>

                                <!-- Thời gian -->
                                <div
                                    style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #94a3b8; margin-bottom: 16px; font-weight: 500;">
                                    <i class="bi bi-clock"></i>
                                    <span>{{ formatDateTime(latestNotification?.created_at) }}</span>
                                </div>

                                <!-- Hành động -->
                                <div class="popup-action">
                                    <Link :href="latestNotification?.data?.url" @click="closePopup" class="popup-btn">
                                        {{
                                            isRejection
                                                ? 'Xem lý do chi tiết'
                                                : 'Truy cập trang quản lý'
                                        }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Property Selection Modal (Every Time Landlord Enters Dashboard) -->
        <Transition name="fade">
            <div v-if="showPropertyPrompt" class="fixed inset-0 z-[99999] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showPropertyPrompt = false">
                </div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-slide-up">
                    <div
                        class="bg-gradient-to-r from-blue-500 to-cyan-500 p-6 text-white text-center relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-20 transform translate-x-4 -translate-y-4">
                            <i class="bi bi-buildings text-6xl"></i>
                        </div>
                        <h3 class="text-xl font-extrabold mb-2 relative z-10">Chọn Cơ Sở Quản Lý</h3>
                        <p class="text-blue-50 text-sm relative z-10">Bạn đang có nhiều hơn 1 cơ sở. Vui lòng chọn cơ sở
                            bạn
                            muốn thao tác lúc này.</p>
                        <button @click="showPropertyPrompt = false"
                            class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center rounded-full bg-black/10 hover:bg-black/20 text-white transition-colors z-20">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 max-h-[60vh] overflow-y-auto">
                        <div class="space-y-3">
                            <button v-for="prop in (auth?.boarding_houses || [])" :key="prop.id"
                                @click="selectPropertyFromPrompt(prop)"
                                class="w-full group flex items-center p-4 rounded-xl border-2 transition-all duration-200 text-left border-slate-100 hover:border-blue-200 hover:bg-slate-50">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center mr-4 transition-colors bg-slate-100 text-slate-500 group-hover:bg-blue-50 group-hover:text-blue-500">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-slate-800 group-hover:text-blue-600">{{ prop.name }}</h4>
                                    <p class="text-xs text-slate-500 mt-0.5" v-if="prop.address_detail">{{
                                        prop.address_detail }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5" v-else>Cơ sở hợp lệ</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Trợ lý AI Mascot Chatbot nổi toàn website -->
        <AiChatAssistant />
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

.popup-action {
    display: flex;
    gap: 10px;
}

.popup-btn {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 10px 16px;
    border-radius: 8px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #3b82f6;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s ease;
}

.popup-btn:hover {
    background: #f1f5f9;
    color: #2563eb;
    border-color: #bfdbfe;
}

.dropdown .profile-card .status.verified {
    background: #e6f9f2 !important;
    color: #00b894 !important;
}

.dropdown .profile-card .status.unverified {
    background: #fff9db !important;
    color: #f59f00 !important;
}

/* Transitions */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
}
</style>