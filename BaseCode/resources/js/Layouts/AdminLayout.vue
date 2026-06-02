<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const sidebarOpen = ref(true)
const notifOpen = ref(false)

const logout = () => router.post(route('logout'))

const isActive = (path) => page.url === path || page.url.startsWith(path + '/')

const navGroups = [
    {
        label: null,
        items: [
            { label: 'Tổng Quan', path: '/admin/dashboard', icon: 'bi-speedometer2' },
        ]
    },
    {
        label: 'Quản Lý',
        items: [
            { label: 'Người Dùng', path: '/admin/users', icon: 'bi-people-fill' },
            { label: 'Chủ Trọ', path: '/admin/landlords', icon: 'bi-house-check-fill' },
            { label: 'Duyệt Hồ Sơ', path: '/admin/verifications', icon: 'bi-person-badge-fill' },
            { label: 'Phê Duyệt Tin', path: '/admin/approval', icon: 'bi-check-circle-fill' },
            { label: 'Danh Mục', path: '/admin/categories', icon: 'bi-tags-fill' },
        ]
    },
    {
        label: 'Xử Lý',
        items: [
            { label: 'Báo Cáo & Khiếu Nại', path: '/admin/reports', icon: 'bi-flag-fill' },
            { label: 'Đánh Giá', path: '/admin/reviews', icon: 'bi-star-fill' },
        ]
    },
    {
        label: 'Tài Chính',
        items: [
            { label: 'Nguồn Thu', path: '/admin/revenue', icon: 'bi-cash-stack' },
        ]
    },
    {
        label: 'Hệ Thống',
        items: [
            { label: 'Phân Quyền', path: '/admin/roles', icon: 'bi-shield-lock-fill' },
            { label: 'Audit Log', path: '/admin/auditlog', icon: 'bi-journal-text' },
            { label: 'Chỉnh Website', path: '/admin/website', icon: 'bi-brush-fill' },
            { label: 'Quảng Cáo', path: '/admin/ads', icon: 'bi-megaphone-fill' },
        ]
    },
]
</script>

<template>
    <div class="admin-shell">
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'sidebar-expanded' : 'sidebar-collapsed'" class="admin-sidebar">
            <!-- Brand -->
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <i class="bi bi-house-heart-fill"></i>
                </div>
                <div v-if="sidebarOpen" class="brand-text">
                    <span class="brand-name">Ninh Bình</span>
                    <span class="brand-sub">HomeStay Admin</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="sidebar-nav">
                <template v-for="group in navGroups" :key="group.label">
                    <p v-if="group.label && sidebarOpen" class="nav-group-label">{{ group.label }}</p>
                    <div v-else-if="group.label && !sidebarOpen" class="nav-divider"></div>
                    <Link
                        v-for="item in group.items"
                        :key="item.path"
                        :href="item.path"
                        :class="['nav-item', isActive(item.path) ? 'nav-item-active' : '']"
                        :title="!sidebarOpen ? item.label : ''"
                    >
                        <i :class="['bi', item.icon, 'nav-icon']"></i>
                        <span v-if="sidebarOpen" class="nav-label">{{ item.label }}</span>
                    </Link>
                </template>
            </nav>

            <!-- Bottom actions -->
            <div class="sidebar-bottom">
                <button @click="logout" class="nav-item nav-logout">
                    <i class="bi bi-box-arrow-right nav-icon"></i>
                    <span v-if="sidebarOpen" class="nav-label">Đăng Xuất</span>
                </button>
                <button @click="sidebarOpen = !sidebarOpen" class="nav-item nav-toggle">
                    <i :class="['bi', sidebarOpen ? 'bi-arrow-bar-left' : 'bi-arrow-bar-right', 'nav-icon']"></i>
                    <span v-if="sidebarOpen" class="nav-label">Thu gọn</span>
                </button>
            </div>
        </aside>

        <!-- Main area -->
        <div class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-left">
                    <slot name="header-title">
                        <h1 class="header-title">Dashboard</h1>
                    </slot>
                </div>
                <div class="header-right">
                    <!-- Bell -->
                    <button class="header-btn" @click="notifOpen = !notifOpen">
                        <i class="bi bi-bell"></i>
                        <span class="notif-dot"></span>
                    </button>
                    <!-- View site -->
                    <Link href="/" class="header-btn" title="Xem trang web">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </Link>
                    <!-- Admin info -->
                    <div class="header-admin">
                        <div class="admin-avatar">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div class="admin-info">
                            <span class="admin-name">{{ user?.name }}</span>
                            <span class="admin-role">Quản trị viên</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="admin-content">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.admin-shell {
    display: flex;
    height: 100vh;
    overflow: hidden;
    background: #f1f5f9;
}

/* ── Sidebar ── */
.admin-sidebar {
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    background: linear-gradient(180deg, #071828 0%, #0a2d47 100%);
    transition: width 0.3s cubic-bezier(.4,0,.2,1);
    overflow: hidden;
}
.sidebar-expanded  { width: 240px; }
.sidebar-collapsed { width: 64px; }

/* Brand */
.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px 14px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    flex-shrink: 0;
}
.brand-icon {
    width: 36px; height: 36px;
    background: #166ea9;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 17px;
    flex-shrink: 0;
}
.brand-text { display: flex; flex-direction: column; overflow: hidden; }
.brand-name { color: #fff; font-weight: 700; font-size: 13px; line-height: 1.2; white-space: nowrap; }
.brand-sub  { color: #7ab8d9; font-size: 10px; white-space: nowrap; }

/* Nav */
.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    padding: 12px 10px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    scrollbar-width: none;
}
.sidebar-nav::-webkit-scrollbar { display: none; }

.nav-group-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #475569;
    padding: 12px 10px 6px;
    white-space: nowrap;
}
.nav-divider { border-top: 1px solid rgba(255,255,255,0.05); margin: 8px 0; }

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-radius: 10px;
    color: #94a3b8;
    text-decoration: none;
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    transition: background 0.15s, color 0.15s;
    white-space: nowrap;
    font-size: 13.5px;
    font-weight: 500;
}
.nav-item:hover { background: rgba(255,255,255,0.06); color: #e2e8f0; }
.nav-item-active { background: #166ea9 !important; color: #fff !important; box-shadow: 0 4px 14px rgba(22,110,169,0.45); }
.nav-icon { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
.nav-label { flex: 1; overflow: hidden; text-overflow: ellipsis; }

.nav-logout:hover { background: rgba(239,68,68,0.12); color: #f87171; }
.nav-toggle { color: #475569; }
.nav-toggle:hover { background: rgba(255,255,255,0.04); color: #64748b; }

/* Bottom */
.sidebar-bottom {
    flex-shrink: 0;
    padding: 10px;
    border-top: 1px solid rgba(255,255,255,0.07);
    display: flex;
    flex-direction: column;
    gap: 2px;
}

/* ── Header ── */
.admin-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.admin-header {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    z-index: 10;
}
.header-left {}
.header-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }

.header-right { display: flex; align-items: center; gap: 8px; }

.header-btn {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    color: #64748b;
    cursor: pointer;
    position: relative;
    transition: background 0.15s;
    text-decoration: none;
    font-size: 15px;
}
.header-btn:hover { background: #f1f5f9; color: #334155; }
.notif-dot {
    position: absolute; top: 8px; right: 8px;
    width: 7px; height: 7px;
    background: #ef4444; border-radius: 50%;
    border: 1.5px solid #fff;
}

.header-admin { display: flex; align-items: center; gap: 10px; padding-left: 12px; border-left: 1px solid #e2e8f0; }
.admin-avatar {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #166ea9, #0e4f7a);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 15px;
}
.admin-info { display: flex; flex-direction: column; }
.admin-name { font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.2; }
.admin-role { font-size: 11px; color: #94a3b8; }

/* ── Content ── */
.admin-content {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
}
</style>
