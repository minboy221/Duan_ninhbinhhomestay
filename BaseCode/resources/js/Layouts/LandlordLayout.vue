<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref, watch, onMounted } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const sidebarOpen = ref(true)
const drawerOpen = ref(false)   // mobile drawer
const propertyDropdownOpen = ref(false)
const notifOpen = ref(false)

const flashMessage = ref('')
const showFlash = ref(false)

watch(() => page.props.flash?.success, (newVal) => {
    if (newVal) {
        flashMessage.value = newVal
        showFlash.value = true
        setTimeout(() => {
            showFlash.value = false
        }, 3000)
    }
}, { immediate: true })

const logout = () => router.post(route('logout'))
const isActive = (path) => {
    if (path === '#') return false
    return page.url === path || page.url.startsWith(path + '/')
}
const closeDrawer = () => { drawerOpen.value = false }

const properties = computed(() => usePage().props.auth.boarding_houses || [])
const selectedPropertyId = computed(() => usePage().props.auth.selected_boarding_house_id)
const selectedProperty = computed(() => properties.value.find(p => p.id === selectedPropertyId.value) || properties.value[0] || { name: 'Chưa có cơ sở' })

const selectProperty = (prop) => {
    propertyDropdownOpen.value = false
    router.post(route('landlord.select-boarding-house'), { id: prop.id }, { preserveScroll: true })
}

const openSubmenus = ref(['Lịch Hẹn'])
const toggleSubmenu = (label) => {
    const index = openSubmenus.value.indexOf(label)
    if (index > -1) {
        openSubmenus.value.splice(index, 1)
    } else {
        openSubmenus.value.push(label)
    }
}
const isChildActive = (item) => {
    if (!item.children) return false
    return item.children.some(child => isActive(child.path))
}
const isSubmenuOpen = (item) => {
    return openSubmenus.value.includes(item.label)
}

const navGroups = [
    {
        label: 'NGHIỆP VỤ',
        items: [
            { label: 'Tổng Quan', path: '/landlord/dashboard', icon: 'bi-grid-1x2-fill' },
            { label: 'Hóa Đơn', path: '/landlord/invoices', icon: 'bi-receipt' },
            { label: 'Tin Đăng', path: '/landlord/listings', icon: 'bi-megaphone' },
            { 
                label: 'Lịch Hẹn', 
                icon: 'bi-calendar-event',
                children: [
                    { label: 'Lịch Đặt Hẹn', path: '/landlord/appointments', icon: 'bi-calendar-check' },
                    { label: 'Khung Giờ Rảnh', path: '/landlord/appointments/availabilities', icon: 'bi-clock-history' },
                ]
            },
        ]
    },
    {
        label: 'DỮ LIỆU',
        items: [
            { label: 'Nhà & Phòng', path: '/landlord/rooms', icon: 'bi-house' },
            { label: 'Dịch Vụ', path: '/landlord/services', icon: 'bi-tools' },
            { label: 'Khách Hàng', path: '/landlord/tenants', icon: 'bi-people' },
            { label: 'Hợp Đồng', path: '/landlord/contracts', icon: 'bi-file-earmark-text' },
        ]
    },
    {
        label: 'NHÀ CỦA TÔI',
        items: [
            { label: 'Thông Tin CĐT', path: '/landlord/profile', icon: 'bi-person-gear' },
            { label: 'Quản Lý Cơ Sở', path: '/landlord/boarding-houses', icon: 'bi-buildings' },
            { label: 'Hồ Sơ Xét Duyệt', path: '/landlord/boarding-houses/history', icon: 'bi-file-earmark-check' },
        ]
    }
]

// Bottom tab bar mobile
const bottomTabs = [
    { label: 'Tổng Quan', path: '/landlord/dashboard', icon: 'bi-grid-1x2-fill' },
    { label: 'Nhà', path: '/landlord/rooms', icon: 'bi-house' },
    { label: 'Hoá Đơn', path: '/landlord/invoices', icon: 'bi-receipt' },
    { label: 'Hợp Đồng', path: '/landlord/contracts', icon: 'bi-file-earmark-text' },
    { label: 'Menu', path: null, icon: 'bi-list', action: () => { drawerOpen.value = true } },
]

const showWelcomePopup = ref(false)
const latestNotification = ref(null)

onMounted(() => {
    // Tự động mở các menu con nếu có trang con đang active lúc load trang
    navGroups.forEach(group => {
        group.items.forEach(item => {
            if (item.children && isChildActive(item)) {
                if (!openSubmenus.value.includes(item.label)) {
                    openSubmenus.value.push(item.label)
                }
            }
        })
    })

    if (page.props.auth?.notifications && page.props.auth.notifications.length > 0) {
        const notif = page.props.auth.notifications[0]
        const dismissed = sessionStorage.getItem('dismissed_landlord_notification_' + notif.id)
        if (!dismissed) {
            latestNotification.value = notif
            showWelcomePopup.value = true
        }
    }
})

const closePopup = () => {
    showWelcomePopup.value = false
    if (latestNotification.value) {
        sessionStorage.setItem('dismissed_landlord_notification_' + latestNotification.value.id, 'true')
    }
}
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-[#fafbfe] font-sans text-slate-700 antialiased">
        <!-- Sidebar (desktop only) -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'"
            class="hidden md:flex flex-col flex-shrink-0 bg-[#f1f5f9] border-r border-slate-100/80 transition-all duration-300 ease-in-out z-20 shadow-[4px_0_24px_rgba(0,0,0,0.015)]">
            <!-- Brand logo -->
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100/60 h-16 flex-shrink-0">
                <div
                    class="w-10 h-10 bg-gradient-to-tr from-emerald-600 to-teal-400 text-white rounded-xl flex items-center justify-center font-black text-xl shadow-lg shadow-emerald-500/10">
                    N
                </div>
                <div v-if="sidebarOpen" class="flex flex-col overflow-hidden transition-all duration-300">
                    <span class="font-extrabold text-slate-900 text-sm tracking-tight whitespace-nowrap">Ninh Bình
                        Home</span>
                    <span
                        class="text-emerald-600 text-[10px] font-extrabold tracking-wide uppercase whitespace-nowrap">Chủ
                        Trọ Panel</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-6 space-y-7 scrollbar-thin scrollbar-thumb-slate-100">
                <div v-for="group in navGroups" :key="group.label" class="space-y-2">
                    <p v-if="group.label && sidebarOpen"
                        class="px-3 text-[9px] font-bold text-slate-400/80 tracking-widest uppercase">
                        {{ group.label }}
                    </p>
                    <div v-else-if="group.label && !sidebarOpen" class="h-px bg-slate-100/80 my-4 mx-2"></div>

                    <div class="space-y-1">
                        <div v-for="item in group.items" :key="item.label" class="space-y-1">
                            <!-- Parent Item (No Children) -->
                            <component v-if="!item.children" :is="item.path === '#' ? 'div' : Link"
                                :href="item.path !== '#' ? item.path : undefined" :class="[
                                    'flex items-center transition-all duration-300 group relative w-full rounded-xl',
                                    sidebarOpen ? 'gap-3.5 px-4 py-3' : 'justify-center py-3 px-2',
                                    item.path === '#' ? 'cursor-not-allowed opacity-70' : 'cursor-pointer hover:translate-x-0.5',
                                    isActive(item.path)
                                        ? 'bg-emerald-50/70 border border-emerald-100/50 text-emerald-700 font-bold shadow-[0_2px_12px_rgba(16,185,129,0.04)] before:absolute before:left-0 before:top-2.5 before:bottom-2.5 before:w-1 before:bg-emerald-500 before:rounded-full'
                                        : 'text-slate-500 hover:bg-slate-50/80 hover:text-slate-900'
                                ]" :title="!sidebarOpen ? item.label : ''">
                                <i :class="['bi', item.icon, 'text-2xl transition-colors duration-300', isActive(item.path) ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-700']"></i>
                                <span v-if="sidebarOpen" class="text-base font-bold tracking-tight truncate">{{ item.label }}</span>
                                <span v-if="item.isPro && sidebarOpen" class="ml-auto px-1.5 py-0.5 text-[8px] font-bold bg-amber-50 text-amber-600 border border-amber-200/60 rounded-md uppercase">PRO</span>
                                <div v-if="!sidebarOpen" class="absolute left-16 bg-slate-900 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                    {{ item.label }}
                                </div>
                            </component>

                            <!-- Parent Item (Has Children) -->
                            <div v-else class="space-y-1">
                                <button type="button" @click="toggleSubmenu(item.label)" :class="[
                                    'flex items-center transition-all duration-300 group relative w-full rounded-xl',
                                    sidebarOpen ? 'gap-3.5 px-4 py-3' : 'justify-center py-3 px-2',
                                    isChildActive(item)
                                        ? 'bg-emerald-50/20 text-emerald-700 font-bold'
                                        : 'text-slate-500 hover:bg-slate-50/80 hover:text-slate-900'
                                ]" :title="!sidebarOpen ? item.label : ''">
                                    <i :class="['bi', item.icon, 'text-2xl transition-colors duration-300', isChildActive(item) ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-700']"></i>
                                    <span v-if="sidebarOpen" class="text-base font-bold tracking-tight truncate">{{ item.label }}</span>
                                    
                                    <!-- Chấm đỏ/Badge số lượng cho menu Lịch Hẹn -->
                                    <span v-if="item.label === 'Lịch Hẹn' && page.props.auth?.pending_appointments_count > 0" 
                                        :class="sidebarOpen ? 'ml-auto mr-2 px-1.5 py-0.5 text-[9px] font-bold bg-rose-500 text-white rounded-full leading-none flex items-center justify-center min-w-[18px] h-[18px]' : 'absolute top-2 right-2 w-2.5 h-2.5 bg-rose-500 rounded-full border border-white'">
                                        {{ sidebarOpen ? page.props.auth.pending_appointments_count : '' }}
                                    </span>

                                    <i v-if="sidebarOpen" :class="['bi text-xs transition-transform duration-200', isSubmenuOpen(item) ? 'bi-chevron-up' : 'bi-chevron-down', item.label === 'Lịch Hẹn' && page.props.auth?.pending_appointments_count > 0 ? '' : 'ml-auto']"></i>
                                    
                                    <div v-if="!sidebarOpen" class="absolute left-16 bg-slate-900 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                        {{ item.label }}
                                    </div>
                                </button>

                                <!-- Children submenu links -->
                                <div v-if="sidebarOpen && isSubmenuOpen(item)" class="pl-6 space-y-1">
                                    <Link v-for="child in item.children" :key="child.label" :href="child.path" :class="[
                                        'flex items-center gap-3 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-200 border border-transparent',
                                        isActive(child.path)
                                            ? 'text-emerald-700 font-bold bg-emerald-50/50'
                                            : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50/50'
                                    ]">
                                        <i :class="['bi', child.icon, 'text-base', isActive(child.path) ? 'text-emerald-600' : 'text-slate-400']"></i>
                                        <span>{{ child.label }}</span>
                                        <!-- Badge số lượng cho menu con Lịch Đặt Hẹn -->
                                        <span v-if="child.label === 'Lịch Đặt Hẹn' && page.props.auth?.pending_appointments_count > 0" 
                                            class="ml-auto px-1.5 py-0.5 text-[9px] font-bold bg-rose-500 text-white rounded-full leading-none flex items-center justify-center min-w-[16px] h-[16px]">
                                            {{ page.props.auth.pending_appointments_count }}
                                        </span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-slate-100/80 flex flex-col gap-1.5">
                <Link href="/"
                    :class="[
                        'flex items-center text-slate-600 hover:bg-slate-50 w-full transition-all duration-300 font-bold rounded-xl',
                        sidebarOpen ? 'gap-3.5 px-4 py-3 text-base' : 'justify-center py-3 px-2 text-base'
                    ]"
                    title="Về trang chủ">
                    <i class="bi bi-globe text-2xl text-slate-400"></i>
                    <span v-if="sidebarOpen">Về Trang Chủ</span>
                </Link>
                <button @click="logout"
                    :class="[
                        'flex items-center text-rose-600 hover:bg-rose-50/50 w-full transition-all duration-300 font-bold rounded-xl',
                        sidebarOpen ? 'gap-3.5 px-4 py-3 text-base' : 'justify-center py-3 px-2 text-base'
                    ]">
                    <i class="bi bi-box-arrow-right text-2xl"></i>
                    <span v-if="sidebarOpen">Đăng Xuất</span>
                </button>
                <button @click="sidebarOpen = !sidebarOpen"
                    :class="[
                        'flex items-center text-slate-400 hover:text-slate-700 w-full transition-all duration-300 font-bold rounded-xl',
                        sidebarOpen ? 'gap-3.5 px-4 py-3 text-base' : 'justify-center py-3 px-2 text-base'
                    ]">
                    <i :class="['bi', sidebarOpen ? 'bi-arrow-bar-left' : 'bi-arrow-bar-right', 'text-2xl']"></i>
                    <span v-if="sidebarOpen">Thu gọn menu</span>
                </button>
            </div>
        </aside>

        <!-- Main content container -->
        <div class="flex flex-col flex-1 overflow-hidden min-w-0">
            <!-- Header -->
            <header
                class="bg-[#f1f5f9] border-b border-slate-100/80 h-16 flex items-center justify-between px-6 flex-shrink-0 z-10 shadow-[0_2px_12px_rgba(0,0,0,0.005)]">
                <div class="flex items-center gap-4">
                    <!-- Hamburger menu (mobile only) -->
                    <button class="md:hidden text-slate-500 hover:bg-slate-50 p-2 rounded-xl"
                        @click="drawerOpen = true">
                        <i class="bi bi-list text-2xl"></i>
                    </button>

                    <!-- Search input (fake) -->
                    <div
                        class="hidden md:flex items-center bg-slate-50/60 border border-slate-100/80 rounded-xl px-3 py-1.5 w-64 text-slate-400 gap-2 hover:bg-slate-50 transition-colors duration-300">
                        <i class="bi bi-search text-xs"></i>
                        <span class="text-xs font-medium text-slate-400">Tìm kiếm...</span>
                        <kbd
                            class="ml-auto bg-white border border-slate-200/60 rounded px-1.5 py-0.5 text-[9px] font-mono text-slate-400/80 shadow-sm">⌘K</kbd>
                    </div>
                </div>

                <!-- Right header tools -->
                <div class="flex items-center gap-4">
                    <!-- Property selector Dropdown -->
                    <div class="relative">
                        <button @click="propertyDropdownOpen = !propertyDropdownOpen"
                            class="flex items-center gap-2 border border-slate-200/80 hover:bg-slate-50/60 rounded-xl px-3.5 py-1.5 text-xs font-bold text-slate-700 bg-[#f1f5f9] transition-all duration-300 shadow-[0_2px_8px_rgba(0,0,0,0.015)]">
                            <i class="bi bi-building text-emerald-500"></i>
                            <span>{{ selectedProperty.name }}</span>
                            <i class="bi bi-chevron-down text-[9px] text-slate-400 ml-1"></i>
                        </button>

                        <!-- Dropdown menu -->
                        <div v-if="propertyDropdownOpen"
                            class="absolute right-0 mt-2 w-56 bg-white border border-slate-100 rounded-2xl shadow-xl py-2 z-50 animate-fade-in">
                            <div class="px-4 py-1.5 text-[9px] font-bold text-slate-400/80 tracking-widest uppercase">
                                Chọn Cơ Sở
                            </div>
                            <button v-for="prop in properties" :key="prop.id" @click="selectProperty(prop)"
                                class="flex items-center w-full px-4 py-2 text-left text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                                <i class="bi bi-geo-alt-fill text-emerald-500 mr-2"></i>
                                {{ prop.name }}
                            </button>
                        </div>
                    </div>



                    <!-- Notifications -->
                    <div class="relative">
                        <button @click="notifOpen = !notifOpen"
                            class="relative w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 border border-slate-100 text-slate-500 hover:bg-slate-100/80 hover:text-slate-800 transition-all shadow-[0_2px_6px_rgba(0,0,0,0.005)]">
                            <i class="bi bi-bell text-sm"></i>
                            <span v-if="page.props.auth?.notifications?.length > 0"
                                class="absolute top-1 right-1 w-3.5 h-3.5 bg-rose-500 rounded-full border border-white text-[8px] font-bold text-white flex items-center justify-center">
                                {{ page.props.auth.notifications.length > 9 ? "9+" : page.props.auth.notifications.length }}
                            </span>
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div v-if="notifOpen" class="fixed md:absolute left-4 right-4 md:left-auto md:right-0 top-16 md:top-auto md:mt-2 w-auto md:w-80 bg-white rounded-xl shadow-lg border border-slate-100 overflow-hidden z-50">
                            <div class="px-4 py-3 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                                <h3 class="text-sm font-bold text-slate-800">Thông báo</h3>
                                <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">
                                    {{ page.props.auth?.notifications?.length || 0 }} mới
                                </span>
                            </div>
                            <div style="max-height: 400px; overflow-y: auto;" class="scrollbar-thin scrollbar-thumb-slate-200">
                                <div v-if="page.props.auth?.notifications?.length > 0">
                                    <div v-for="notification in page.props.auth.notifications" :key="notification.id"
                                        class="block px-4 py-3 hover:bg-slate-50 border-b border-slate-50 transition-colors relative group">
                                        <div class="flex gap-3 items-start">
                                            <div class="flex-shrink-0 mt-1">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                                    :class="notification.type === 'App\\Notifications\\LandlordRejected' || notification.type === 'listing_rejected' ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'">
                                                    <i :class="notification.type === 'App\\Notifications\\LandlordRejected' || notification.type === 'listing_rejected' ? 'bi bi-x-circle' : 'bi bi-info-circle'"></i>
                                                </div>
                                            </div>
                                            <Link :href="notification.data.url || '#'" class="flex-1 min-w-0" @click="notifOpen = false">
                                                <p class="text-[13px] font-semibold text-slate-800 mb-0.5 leading-snug">{{ notification.data.title || 'Thông báo mới' }}</p>
                                                <p class="text-xs text-slate-500 mb-1 leading-relaxed">{{ notification.data.message || notification.data.content }}</p>
                                                <p class="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                                                    <i class="bi bi-clock"></i> 
                                                    {{ new Date(notification.created_at).toLocaleDateString('vi-VN') }}
                                                </p>
                                            </Link>
                                            <button type="button"
                                                @click.stop="router.post(route('notifications.read', notification.id), {}, { preserveScroll: true })"
                                                class="opacity-0 group-hover:opacity-100 transition-opacity text-[10px] bg-white border border-slate-200 hover:bg-emerald-50 text-slate-500 hover:text-emerald-600 px-2 py-1 rounded font-bold absolute right-3 top-3 shadow-sm z-10"
                                                title="Đánh dấu đã đọc">
                                                <i class="bi bi-check2"></i> Đã đọc
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="px-4 py-8 text-center flex flex-col items-center justify-center gap-2">
                                    <i class="bi bi-bell-slash text-slate-300 text-3xl"></i>
                                    <p class="text-sm font-medium text-slate-400">Bạn không có thông báo mới nào</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile info -->
                    <div class="flex items-center gap-3 pl-3 border-l border-slate-100">
                        <div
                            class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center font-extrabold shadow-md shadow-emerald-500/10">
                            {{ user?.name ? user.name.charAt(0).toUpperCase() : 'L' }}
                        </div>
                        <div class="hidden lg:flex flex-col">
                            <span class="text-xs font-bold text-slate-900 leading-none">{{ user?.name || 'Chủ trọ'
                                }}</span>
                            <span class="text-[9px] font-extrabold text-emerald-600 mt-1 uppercase tracking-wide">Chủ
                                Trọ</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main view screen -->
            <main class="flex-1 overflow-y-auto px-6 pt-6 pb-28 md:p-8 bg-[#f8fafc] text-sm">
                <slot />
            </main>
        </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <Teleport to="body">
        <div v-if="drawerOpen" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex"
            @click.self="closeDrawer">
            <div class="w-72 h-full bg-[#f1f5f9] flex flex-col shadow-2xl animate-slide-in">
                <!-- Drawer Head -->
                <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 h-16 flex-shrink-0">
                    <div
                        class="w-10 h-10 bg-emerald-500 text-white rounded-xl flex items-center justify-center font-bold text-2xl">
                        R
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-800 text-sm">Ninh Bình Stay</span>
                        <span class="text-emerald-500 text-xs font-semibold">Chủ Trọ Panel</span>
                    </div>
                    <button class="ml-auto text-slate-400 hover:text-slate-600 p-2" @click="closeDrawer">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <!-- Drawer Navigation -->
                <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-6">
                    <div v-for="group in navGroups" :key="group.label" class="space-y-1">
                        <p v-if="group.label"
                            class="px-3 text-[10px] font-bold text-slate-400 tracking-wider uppercase">
                            {{ group.label }}
                        </p>
                        <div class="space-y-0.5">
                            <div v-for="item in group.items" :key="item.label" class="space-y-0.5">
                                <component v-if="!item.children" :is="item.path === '#' ? 'div' : Link"
                                    :href="item.path !== '#' ? item.path : undefined" :class="[
                                        'flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200',
                                        item.path === '#' ? 'cursor-not-allowed opacity-75' : 'cursor-pointer',
                                        isActive(item.path)
                                            ? 'bg-emerald-50 text-emerald-600 font-semibold shadow-sm'
                                            : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'
                                    ]" @click="closeDrawer">
                                    <i
                                        :class="['bi', item.icon, 'text-xl', isActive(item.path) ? 'text-emerald-500' : 'text-slate-400']"></i>
                                    <span class="text-sm font-bold">{{ item.label }}</span>
                                    <span v-if="item.isPro"
                                        class="ml-auto px-1.5 py-0.5 text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-200 rounded uppercase">PRO</span>
                                </component>

                                 <div v-else class="space-y-0.5">
                                    <button type="button" @click="toggleSubmenu(item.label)" class="flex items-center gap-3 px-3 py-2.5 rounded-xl w-full transition-all duration-200 text-slate-500 hover:bg-slate-50 hover:text-slate-800">
                                        <i :class="['bi', item.icon, 'text-xl', isChildActive(item) ? 'text-emerald-500' : 'text-slate-400']"></i>
                                        <span class="text-sm font-bold text-left">{{ item.label }}</span>
                                        
                                        <!-- Chấm đỏ/Badge số lượng cho mobile Lịch Hẹn -->
                                        <span v-if="item.label === 'Lịch Hẹn' && page.props.auth?.pending_appointments_count > 0" 
                                            class="ml-auto px-1.5 py-0.5 text-[9px] font-bold bg-rose-500 text-white rounded-full leading-none flex items-center justify-center min-w-[18px] h-[18px]">
                                            {{ page.props.auth.pending_appointments_count }}
                                        </span>

                                        <i :class="['bi text-xs transition-transform duration-200', isSubmenuOpen(item) ? 'bi-chevron-up' : 'bi-chevron-down', item.label === 'Lịch Hẹn' && page.props.auth?.pending_appointments_count > 0 ? '' : 'ml-auto']"></i>
                                    </button>

                                    <div v-if="isSubmenuOpen(item)" class="pl-6 space-y-0.5">
                                        <Link v-for="child in item.children" :key="child.label" :href="child.path"
                                            class="flex items-center gap-3 px-3 py-2 rounded-xl transition-all duration-200"
                                            :class="[
                                                isActive(child.path)
                                                    ? 'bg-emerald-50 text-emerald-600 font-semibold shadow-sm'
                                                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'
                                            ]" @click="closeDrawer">
                                            <i :class="['bi', child.icon, 'text-lg', isActive(child.path) ? 'text-emerald-500' : 'text-slate-400']"></i>
                                            <span class="text-xs font-bold">{{ child.label }}</span>
                                            
                                            <!-- Badge số lượng cho mobile Lịch Đặt Hẹn -->
                                            <span v-if="child.label === 'Lịch Đặt Hẹn' && page.props.auth?.pending_appointments_count > 0" 
                                                class="ml-auto px-1.5 py-0.5 text-[9px] font-bold bg-rose-500 text-white rounded-full leading-none flex items-center justify-center min-w-[16px] h-[16px]">
                                                {{ page.props.auth.pending_appointments_count }}
                                            </span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Drawer Footer -->
                <div class="p-4 border-t border-slate-100 flex flex-col gap-1">
                    <Link href="/"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-600 hover:bg-slate-50 w-full transition-all font-medium text-sm">
                        <i class="bi bi-globe text-lg text-slate-400"></i>
                        <span>Về Trang Chủ</span>
                    </Link>
                    <button @click="logout"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-rose-500 hover:bg-rose-50 w-full transition-all font-medium text-sm">
                        <i class="bi bi-box-arrow-right text-lg"></i>
                        <span>Đăng Xuất</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Mobile Bottom Tab Bar -->
    <nav
        class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-[#f1f5f9] border-t border-slate-100 flex items-center justify-around z-30 pb-safe shadow-lg">
        <template v-for="tab in bottomTabs" :key="tab.label">
            <button v-if="tab.action"
                class="flex flex-col items-center gap-1 text-slate-400 hover:text-emerald-500 bg-none border-none p-2"
                @click="tab.action()">
                <i :class="['bi', tab.icon, 'text-xl']"></i>
                <span class="text-[9px] font-bold uppercase tracking-wider">{{ tab.label }}</span>
            </button>
            <Link v-else :href="tab.path" class="flex flex-col items-center gap-1 p-2"
                :class="isActive(tab.path) ? 'text-emerald-500' : 'text-slate-400 hover:text-slate-800'">
                <i :class="['bi', tab.icon, 'text-xl']"></i>
                <span class="text-[9px] font-bold uppercase tracking-wider">{{ tab.label }}</span>
            </Link>
        </template>
    </nav>

        <Teleport to="body">
        <Transition name="toast-slide">
            <div v-if="showFlash" style="position: fixed; top: 30px; right: 30px; z-index: 99999;">
                <div style="background: white; border-radius: 12px; width: 320px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; overflow: hidden; display: flex; align-items: center; padding: 16px;">
                    <div style="width: 4px; height: 100%; background: #10b981; position: absolute; left: 0; top: 0;"></div>
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #d1fae5; color: #10b981; display: flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0;">
                        <i class="bi bi-check-lg" style="font-size: 18px; font-weight: bold;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0; font-size: 14px; font-weight: bold; color: #1e293b;">Thành công</h4>
                        <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">{{ flashMessage }}</p>
                    </div>
                    <button @click="showFlash = false" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Popup thông báo góc phải dưới -->
        <Transition name="toast-slide">
            <div v-if="showWelcomePopup" style="position: fixed; bottom: 30px; right: 30px; z-index: 99999;">
                <div style="background: white; border-radius: 8px; width: 380px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border: 1px solid #f1f5f9; overflow: hidden; position: relative;">
                    
                    <!-- Thanh màu báo hiệu (xanh/đỏ) -->
                    <div :style="latestNotification?.type === 'listing_rejected' || latestNotification?.type === 'App\\Notifications\\LandlordRejected' ? 'height: 4px; background: linear-gradient(90deg, #ef4444, #f87171);' : 'height: 4px; background: linear-gradient(90deg, #22c55e, #4ade80);'"></div>
                    
                    <div style="padding: 24px;">
                        <!-- Nút tắt (X) -->
                        <button @click="closePopup" style="position: absolute; top: 16px; right: 16px; background: transparent; border: none; color: #94a3b8; cursor: pointer; transition: color 0.2s; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%;" onmouseover="this.style.color='#ef4444'; this.style.background='#fef2f2'" onmouseout="this.style.color='#94a3b8'; this.style.background='transparent'">
                            <i class="bi bi-x-lg"></i>
                        </button>

                        <div style="display: flex; gap: 16px; align-items: flex-start;">
                            <!-- Icon -->
                            <div :style="latestNotification?.type === 'listing_rejected' || latestNotification?.type === 'App\\Notifications\\LandlordRejected' ? 'flex-shrink: 0; width: 48px; height: 48px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ef4444;' : 'flex-shrink: 0; width: 48px; height: 48px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #22c55e;'">
                                <i :class="latestNotification?.type === 'listing_rejected' || latestNotification?.type === 'App\\Notifications\\LandlordRejected' ? 'bi bi-x-circle-fill text-2xl' : 'bi bi-check-circle-fill text-2xl'"></i>
                            </div>

                            <!-- Nội dung -->
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #1e293b; line-height: 1.4;">
                                    {{ latestNotification?.data?.title || 'Thông báo mới' }}
                                </h4>
                                <p style="margin: 0 0 12px 0; font-size: 13.5px; color: #64748b; line-height: 1.5;">
                                    {{ latestNotification?.data?.message || latestNotification?.data?.content || '' }}
                                </p>
                                <div style="display: flex; gap: 10px;">
                                    <Link v-if="latestNotification?.data?.url" :href="latestNotification.data.url" @click="closePopup" style="flex: 1; text-align: center; padding: 10px 0; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; color: #3b82f6; font-weight: 600; text-decoration: none; font-size: 13px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.color='#2563eb'" onmouseout="this.style.background='#f8fafc'; this.style.color='#3b82f6'">
                                        Xem chi tiết
                                    </Link>
                                    <Link v-else href="/landlord/dashboard" @click="closePopup" style="flex: 1; text-align: center; padding: 10px 0; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; color: #3b82f6; font-weight: 600; text-decoration: none; font-size: 13px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.color='#2563eb'" onmouseout="this.style.background='#f8fafc'; this.style.color='#3b82f6'">
                                        Đóng
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

<style scoped>
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
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}

@keyframes slideIn {
    from {
        transform: translateX(-100%);
    }

    to {
        transform: translateX(0);
    }
}

.animate-slide-in {
    animation: slideIn 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
