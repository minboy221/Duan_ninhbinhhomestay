<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const sidebarOpen = ref(true)
const drawerOpen  = ref(false)   // mobile drawer

const logout = () => router.post(route('logout'))
const isActive = (path) => page.url === path || page.url.startsWith(path + '/')
const closeDrawer = () => { drawerOpen.value = false }

const navGroups = [
    {
        label: null,
        items: [
            { label: 'Tổng Quan', path: '/landlord/dashboard', icon: 'bi-speedometer2' },
        ]
    },
    {
        label: 'Quản Lý',
        items: [
            { label: 'Quản Lý Trọ',   path: '/landlord/rooms',        icon: 'bi-building' },
            { label: 'Tin Đăng',       path: '/landlord/listings',     icon: 'bi-megaphone-fill' },
            { label: 'Lịch Hẹn',      path: '/landlord/appointments', icon: 'bi-calendar-check-fill' },
        ]
    },
    {
        label: 'Người Thuê',
        items: [
            { label: 'Người Thuê Trọ', path: '/landlord/tenants',   icon: 'bi-people-fill' },
            { label: 'Hợp Đồng',       path: '/landlord/contracts', icon: 'bi-file-earmark-text-fill' },
        ]
    },
    {
        label: 'Tài Chính',
        items: [
            { label: 'Hoá Đơn',  path: '/landlord/invoices', icon: 'bi-receipt' },
            { label: 'Tài Chính', path: '/landlord/finance',  icon: 'bi-cash-coin' },
        ]
    },
    {
        label: 'Tài Khoản',
        items: [
            { label: 'Thông Tin CĐT', path: '/landlord/profile', icon: 'bi-person-badge-fill' },
        ]
    },
]

// Bottom tab bar mobile — 5 mục quan trọng nhất
const bottomTabs = [
    { label: 'Tổng Quan', path: '/landlord/dashboard',  icon: 'bi-speedometer2' },
    { label: 'Phòng',     path: '/landlord/rooms',       icon: 'bi-building' },
    { label: 'Hoá Đơn',  path: '/landlord/invoices',    icon: 'bi-receipt' },
    { label: 'Hợp Đồng', path: '/landlord/contracts',   icon: 'bi-file-earmark-text-fill' },
    { label: 'Menu',      path: null,                    icon: 'bi-list', action: () => { drawerOpen.value = true } },
]
</script>

<template>
    <div class="ll-shell">
        <!-- Sidebar (desktop only) -->
        <aside :class="sidebarOpen ? 'll-expanded' : 'll-collapsed'" class="ll-sidebar ll-sidebar-desktop">
            <div class="ll-brand">
                <div class="ll-brand-icon"><i class="bi bi-house-heart-fill"></i></div>
                <div v-if="sidebarOpen" class="ll-brand-text">
                    <span class="ll-brand-name">Ninh Bình</span>
                    <span class="ll-brand-sub">HomeStay · Chủ Trọ</span>
                </div>
            </div>

            <nav class="ll-nav">
                <template v-for="group in navGroups" :key="group.label">
                    <p v-if="group.label && sidebarOpen" class="ll-nav-label">{{ group.label }}</p>
                    <div v-else-if="group.label && !sidebarOpen" class="ll-nav-divider"></div>
                    <Link
                        v-for="item in group.items"
                        :key="item.path"
                        :href="item.path"
                        :class="['ll-nav-item', isActive(item.path) ? 'll-active' : '']"
                        :title="!sidebarOpen ? item.label : ''"
                    >
                        <i :class="['bi', item.icon, 'll-nav-icon']"></i>
                        <span v-if="sidebarOpen" class="ll-nav-text">{{ item.label }}</span>
                    </Link>
                </template>
            </nav>

            <div class="ll-bottom">
                <button @click="logout" class="ll-nav-item ll-logout">
                    <i class="bi bi-box-arrow-right ll-nav-icon"></i>
                    <span v-if="sidebarOpen" class="ll-nav-text">Đăng Xuất</span>
                </button>
                <button @click="sidebarOpen = !sidebarOpen" class="ll-nav-item ll-toggle">
                    <i :class="['bi', sidebarOpen ? 'bi-arrow-bar-left' : 'bi-arrow-bar-right', 'll-nav-icon']"></i>
                    <span v-if="sidebarOpen" class="ll-nav-text">Thu gọn</span>
                </button>
            </div>
        </aside>

        <!-- Main -->
        <div class="ll-main">
            <header class="ll-header">
                <div class="ll-header-left">
                    <!-- Hamburger (mobile only) -->
                    <button class="ll-hamburger" @click="drawerOpen = true">
                        <i class="bi bi-list"></i>
                    </button>
                    <slot name="header-title">
                        <h1 class="ll-header-title">Dashboard</h1>
                    </slot>
                </div>
                <div class="ll-header-right">
                    <button class="ll-hbtn">
                        <i class="bi bi-bell"></i>
                        <span class="ll-notif-dot"></span>
                    </button>
                    <Link href="/" class="ll-hbtn ll-hbtn-hide-sm" title="Xem trang web">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </Link>
                    <div class="ll-user-info">
                        <div class="ll-avatar"><i class="bi bi-person-fill"></i></div>
                        <div class="ll-udetail ll-udetail-hide-sm">
                            <span class="ll-uname">{{ user?.name }}</span>
                            <span class="ll-urole">Chủ Trọ</span>
                        </div>
                    </div>
                </div>
            </header>

            <main class="ll-content">
                <slot />
            </main>
        </div>
    </div>

    <!-- ── Mobile Drawer Overlay ── -->
    <Teleport to="body">
        <div v-if="drawerOpen" class="ll-drawer-overlay" @click.self="closeDrawer">
            <div class="ll-drawer">
                <!-- Drawer header -->
                <div class="ll-drawer-head">
                    <div class="ll-brand-icon"><i class="bi bi-house-heart-fill"></i></div>
                    <div class="ll-brand-text">
                        <span class="ll-brand-name">Ninh Bình HomeStay</span>
                        <span class="ll-brand-sub">Chủ Trọ</span>
                    </div>
                    <button class="ll-drawer-close" @click="closeDrawer">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Drawer nav -->
                <nav class="ll-drawer-nav">
                    <template v-for="group in navGroups" :key="group.label">
                        <p v-if="group.label" class="ll-nav-label">{{ group.label }}</p>
                        <Link
                            v-for="item in group.items"
                            :key="item.path"
                            :href="item.path"
                            :class="['ll-nav-item', isActive(item.path) ? 'll-active' : '']"
                            @click="closeDrawer"
                        >
                            <i :class="['bi', item.icon, 'll-nav-icon']"></i>
                            <span class="ll-nav-text">{{ item.label }}</span>
                        </Link>
                    </template>
                </nav>

                <!-- Drawer footer -->
                <div class="ll-drawer-foot">
                    <button @click="logout" class="ll-nav-item ll-logout" style="width:100%">
                        <i class="bi bi-box-arrow-right ll-nav-icon"></i>
                        <span class="ll-nav-text">Đăng Xuất</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- ── Mobile Bottom Tab Bar ── -->
    <nav class="ll-bottom-tabs">
        <template v-for="tab in bottomTabs" :key="tab.label">
            <button v-if="tab.action" class="ll-tab-item" @click="tab.action()">
                <i :class="['bi', tab.icon, 'll-tab-icon']"></i>
                <span class="ll-tab-label">{{ tab.label }}</span>
            </button>
            <Link v-else :href="tab.path" :class="['ll-tab-item', isActive(tab.path) ? 'll-tab-active' : '']">
                <i :class="['bi', tab.icon, 'll-tab-icon']"></i>
                <span class="ll-tab-label">{{ tab.label }}</span>
            </Link>
        </template>
    </nav>
</template>

<style scoped>
/* ── Shell ── */
.ll-shell {
    display: flex;
    height: 100vh;
    overflow: hidden;
    background: #f1f5f9;
    font-family: 'Inter', sans-serif;
}

/* ── Sidebar (desktop) ── */
.ll-sidebar {
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    background: linear-gradient(180deg, #071828 0%, #0a2d47 100%);
    transition: width 0.3s cubic-bezier(.4,0,.2,1);
    overflow: hidden;
}
.ll-expanded  { width: 240px; }
.ll-collapsed { width: 64px; }

.ll-brand { display: flex; align-items: center; gap: 12px; padding: 20px 14px; border-bottom: 1px solid rgba(255,255,255,0.07); flex-shrink: 0; }
.ll-brand-icon { width: 36px; height: 36px; background: #166ea9; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 17px; flex-shrink: 0; }
.ll-brand-text { display: flex; flex-direction: column; overflow: hidden; }
.ll-brand-name { color: #fff; font-weight: 700; font-size: 13px; line-height: 1.2; white-space: nowrap; }
.ll-brand-sub  { color: #7ab8d9; font-size: 10px; white-space: nowrap; }

.ll-nav { flex: 1; overflow-y: auto; padding: 12px 10px; display: flex; flex-direction: column; gap: 2px; scrollbar-width: none; }
.ll-nav::-webkit-scrollbar { display: none; }

.ll-nav-label { font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #475569; padding: 12px 10px 6px; white-space: nowrap; }
.ll-nav-divider { border-top: 1px solid rgba(255,255,255,0.05); margin: 8px 0; }

.ll-nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 10px; color: #94a3b8; text-decoration: none; cursor: pointer; border: none; background: none; width: 100%; text-align: left; transition: background 0.15s, color 0.15s; white-space: nowrap; font-size: 13.5px; font-weight: 500; }
.ll-nav-item:hover { background: rgba(255,255,255,0.06); color: #e2e8f0; }
.ll-active { background: #166ea9 !important; color: #fff !important; box-shadow: 0 4px 14px rgba(22,110,169,0.45); }
.ll-nav-icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
.ll-nav-text { flex: 1; overflow: hidden; text-overflow: ellipsis; }
.ll-logout:hover { background: rgba(239,68,68,0.12); color: #f87171; }
.ll-toggle { color: #475569; }
.ll-toggle:hover { background: rgba(255,255,255,0.04); color: #64748b; }

.ll-bottom { flex-shrink: 0; padding: 10px; border-top: 1px solid rgba(255,255,255,0.07); display: flex; flex-direction: column; gap: 2px; }

/* ── Header ── */
.ll-main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.ll-header { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); z-index: 10; }
.ll-header-left { display: flex; align-items: center; gap: 12px; }
.ll-header-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }
.ll-header-right { display: flex; align-items: center; gap: 8px; }

.ll-hamburger { display: none; width: 38px; height: 38px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; align-items: center; justify-content: center; font-size: 20px; color: #334155; cursor: pointer; }

.ll-hbtn { width: 38px; height: 38px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer; position: relative; transition: background 0.15s; text-decoration: none; font-size: 15px; }
.ll-hbtn:hover { background: #f1f5f9; color: #334155; }
.ll-notif-dot { position: absolute; top: 8px; right: 8px; width: 7px; height: 7px; background: #ef4444; border-radius: 50%; border: 1.5px solid #fff; }

.ll-user-info { display: flex; align-items: center; gap: 10px; padding-left: 12px; border-left: 1px solid #e2e8f0; }
.ll-avatar { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #166ea9, #0e4f7a); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px; }
.ll-udetail { display: flex; flex-direction: column; }
.ll-uname { font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.2; }
.ll-urole { font-size: 11px; color: #94a3b8; }

/* ── Content ── */
.ll-content { flex: 1; overflow-y: auto; padding: 24px; background: #f1f5f9; }

/* ── Mobile Drawer ── */
.ll-drawer-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; display: flex; }
.ll-drawer {
    width: 280px;
    height: 100%;
    background: linear-gradient(180deg, #071828 0%, #0a2d47 100%);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideIn 0.25s ease;
}
@keyframes slideIn { from { transform: translateX(-100%); } to { transform: translateX(0); } }

.ll-drawer-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 14px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    flex-shrink: 0;
}
.ll-drawer-close { margin-left: auto; background: none; border: none; color: #94a3b8; font-size: 18px; cursor: pointer; padding: 4px; }
.ll-drawer-close:hover { color: #fff; }
.ll-drawer-nav  { flex: 1; overflow-y: auto; padding: 12px 10px; display: flex; flex-direction: column; gap: 2px; scrollbar-width: none; }
.ll-drawer-nav::-webkit-scrollbar { display: none; }
.ll-drawer-foot { padding: 10px; border-top: 1px solid rgba(255,255,255,0.1); }

/* ── Mobile Bottom Tab Bar ── */
.ll-bottom-tabs {
    display: none;
    position: fixed;
    bottom: 0; left: 0; right: 0;
    height: 60px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
    z-index: 100;
}
.ll-tab-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    text-decoration: none;
    color: #94a3b8;
    background: none;
    border: none;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: color 0.15s;
    padding: 6px 2px;
}
.ll-tab-item:hover { color: #166ea9; }
.ll-tab-active { color: #166ea9 !important; }
.ll-tab-active .ll-tab-icon { color: #166ea9; }
.ll-tab-icon  { font-size: 20px; }
.ll-tab-label { font-size: 10px; font-weight: 600; white-space: nowrap; }

/* ── Responsive ── */
@media (max-width: 768px) {
    /* Ẩn sidebar desktop */
    .ll-sidebar-desktop { display: none; }

    /* Hiện hamburger */
    .ll-hamburger { display: flex; }

    /* Ẩn bớt header items */
    .ll-hbtn-hide-sm { display: none; }
    .ll-udetail-hide-sm { display: none; }

    /* Header nhỏ hơn */
    .ll-header { padding: 12px 16px; }
    .ll-header-title { font-size: 16px; }

    /* Content padding nhỏ + chừa chỗ tab bar */
    .ll-content { padding: 16px 12px; padding-bottom: 72px; }

    /* Hiện bottom tab bar */
    .ll-bottom-tabs { display: flex; }
}
</style>
