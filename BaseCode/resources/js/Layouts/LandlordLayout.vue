<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const sidebarOpen = ref(true)
const drawerOpen  = ref(false)   // mobile drawer
const propertyDropdownOpen = ref(false)

const logout = () => router.post(route('logout'))
const isActive = (path) => {
    if (path === '#') return false
    return page.url === path || page.url.startsWith(path + '/')
}
const closeDrawer = () => { drawerOpen.value = false }

// Mock properties list for the dropdown
const properties = ref([
    { id: 1, name: 'Nhà Trọ Thanh Hóa' },
    { id: 2, name: 'Homestay Hoa Lư View' }
])
const selectedProperty = ref(properties.value[0])

const selectProperty = (prop) => {
    selectedProperty.value = prop
    propertyDropdownOpen.value = false
    // You can fire an event or redirect to reload rooms under this property
}

const navGroups = [
    {
        label: 'NGHIỆP VỤ',
        items: [
            { label: 'Tổng Quan', path: '/landlord/dashboard', icon: 'bi-grid-1x2-fill' },
            { label: 'Hóa Đơn', path: '/landlord/invoices', icon: 'bi-receipt' },
            { label: 'Tin Đăng', path: '/landlord/listings', icon: 'bi-megaphone' },
            { label: 'Lịch Hẹn', path: '/landlord/appointments', icon: 'bi-calendar-event' },
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
            { label: 'Xác Minh KYC', path: '/landlord/verify', icon: 'bi-shield-check' },
        ]
    }
]

// Bottom tab bar mobile
const bottomTabs = [
    { label: 'Tổng Quan', path: '/landlord/dashboard',  icon: 'bi-grid-1x2-fill' },
    { label: 'Nhà',     path: '/landlord/rooms',       icon: 'bi-house' },
    { label: 'Hoá Đơn',  path: '/landlord/invoices',    icon: 'bi-receipt' },
    { label: 'Hợp Đồng', path: '/landlord/contracts',   icon: 'bi-file-earmark-text' },
    { label: 'Menu',      path: null,                    icon: 'bi-list', action: () => { drawerOpen.value = true } },
]
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-slate-50 font-sans text-slate-800">
        <!-- Sidebar (desktop only) -->
        <aside 
            :class="sidebarOpen ? 'w-64' : 'w-20'" 
            class="hidden md:flex flex-col flex-shrink-0 bg-white border-r border-slate-100 transition-all duration-300 z-20"
        >
            <!-- Brand logo -->
            <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 h-16 flex-shrink-0">
                <div class="w-10 h-10 bg-emerald-500 text-white rounded-xl flex items-center justify-center font-bold text-2xl shadow-sm shadow-emerald-200">
                    R
                </div>
                <div v-if="sidebarOpen" class="flex flex-col overflow-hidden">
                    <span class="font-bold text-slate-800 text-sm leading-tight whitespace-nowrap">Ninh Bình Stay</span>
                    <span class="text-emerald-500 text-xs font-semibold whitespace-nowrap">Chủ Trọ Dashboard</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-6 scrollbar-thin scrollbar-thumb-slate-100">
                <div v-for="group in navGroups" :key="group.label" class="space-y-1">
                    <p v-if="group.label && sidebarOpen" class="px-3 text-[10px] font-bold text-slate-400 tracking-wider uppercase">
                        {{ group.label }}
                    </p>
                    <div v-else-if="group.label && !sidebarOpen" class="h-px bg-slate-100 my-4"></div>
                    
                    <div class="space-y-1">
                        <component 
                            :is="item.path === '#' ? 'div' : Link"
                            v-for="item in group.items"
                            :key="item.label"
                            :href="item.path !== '#' ? item.path : undefined"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group relative',
                                item.path === '#' ? 'cursor-not-allowed opacity-75' : 'cursor-pointer',
                                isActive(item.path) 
                                    ? 'bg-emerald-50 text-emerald-600 font-semibold shadow-sm' 
                                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'
                            ]"
                            :title="!sidebarOpen ? item.label : ''"
                        >
                            <i :class="['bi', item.icon, 'text-lg', isActive(item.path) ? 'text-emerald-500' : 'text-slate-400 group-hover:text-slate-600']"></i>
                            <span v-if="sidebarOpen" class="text-sm truncate">{{ item.label }}</span>
                            
                            <!-- Pro badge -->
                            <span 
                                v-if="item.isPro && sidebarOpen" 
                                class="ml-auto px-1.5 py-0.5 text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-200 rounded uppercase"
                            >
                                PRO
                            </span>

                            <!-- Tooltip when collapsed -->
                            <div v-if="!sidebarOpen" class="absolute left-16 bg-slate-800 text-white text-xs px-2.5 py-1.5 rounded shadow-md opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">
                                {{ item.label }} <span v-if="item.isPro" class="text-[9px] text-amber-400 ml-1">(PRO)</span>
                            </div>
                        </component>
                    </div>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-4 border-t border-slate-100 flex flex-col gap-1.5">
                <button 
                    @click="logout" 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-rose-500 hover:bg-rose-50 w-100 transition-all font-medium text-sm"
                >
                    <i class="bi bi-box-arrow-right text-lg"></i>
                    <span v-if="sidebarOpen">Đăng Xuất</span>
                </button>
                <button 
                    @click="sidebarOpen = !sidebarOpen" 
                    class="flex items-center gap-3 px-3 py-2 text-slate-400 hover:bg-slate-50 rounded-xl transition-all text-xs"
                >
                    <i :class="['bi', sidebarOpen ? 'bi-arrow-bar-left' : 'bi-arrow-bar-right', 'text-lg']"></i>
                    <span v-if="sidebarOpen">Thu gọn menu</span>
                </button>
            </div>
        </aside>

        <!-- Main content container -->
        <div class="flex flex-col flex-1 overflow-hidden min-w-0">
            <!-- Header -->
            <header class="bg-white border-b border-slate-100 h-16 flex items-center justify-between px-6 flex-shrink-0 z-10">
                <div class="flex items-center gap-4">
                    <!-- Hamburger menu (mobile only) -->
                    <button class="md:hidden text-slate-500 hover:bg-slate-50 p-2 rounded-lg" @click="drawerOpen = true">
                        <i class="bi bi-list text-2xl"></i>
                    </button>
                    
                    <!-- Search input (fake) -->
                    <div class="hidden md:flex items-center bg-slate-50 border border-slate-100 rounded-xl px-3 py-1.5 w-64 text-slate-400 gap-2">
                        <i class="bi bi-search text-sm"></i>
                        <span class="text-xs">Tìm kiếm...</span>
                        <kbd class="ml-auto bg-white border border-slate-200 rounded px-1.5 py-0.5 text-[9px] font-mono text-slate-400 shadow-sm">⌘K</kbd>
                    </div>
                </div>

                <!-- Right header tools -->
                <div class="flex items-center gap-4">
                    <!-- Property selector Dropdown -->
                    <div class="relative">
                        <button 
                            @click="propertyDropdownOpen = !propertyDropdownOpen"
                            class="flex items-center gap-2 border border-slate-200 hover:bg-slate-50 rounded-xl px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-white transition-all shadow-sm"
                        >
                            <i class="bi bi-building text-emerald-500"></i>
                            <span>{{ selectedProperty.name }}</span>
                            <i class="bi bi-chevron-down text-[10px] text-slate-400 ml-1"></i>
                        </button>
                        
                        <!-- Dropdown menu -->
                        <div 
                            v-if="propertyDropdownOpen" 
                            class="absolute right-0 mt-2 w-56 bg-white border border-slate-100 rounded-xl shadow-lg py-1.5 z-50"
                        >
                            <div class="px-3.5 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                Chọn Cơ Sở
                            </div>
                            <button 
                                v-for="prop in properties" 
                                :key="prop.id"
                                @click="selectProperty(prop)"
                                class="flex items-center w-full px-4 py-2 text-left text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors"
                            >
                                <i class="bi bi-geo-alt text-emerald-500 mr-2"></i>
                                {{ prop.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Language and guides -->
                    <div class="hidden sm:flex items-center gap-3">
                        <span class="text-xs font-semibold text-slate-500 hover:text-slate-800 cursor-pointer">Hướng dẫn</span>
                        <div class="w-6 h-4 bg-red-600 rounded overflow-hidden flex items-center justify-center shadow-sm relative">
                            <!-- Vietnam Flag Star (simplified) -->
                            <div class="absolute w-2 h-2 bg-yellow-400 rotate-45"></div>
                            <div class="absolute w-2 h-2 bg-yellow-400 rotate-12"></div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <button class="relative w-9 h-9 flex items-center justify-center rounded-xl bg-slate-50 border border-slate-100 text-slate-500 hover:bg-slate-100 transition-colors">
                        <i class="bi bi-bell text-base"></i>
                        <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-rose-500 rounded-full border border-white"></span>
                    </button>

                    <!-- User Profile info -->
                    <div class="flex items-center gap-3 pl-3 border-l border-slate-100">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold shadow-sm shadow-emerald-200">
                            {{ user?.name ? user.name.charAt(0).toUpperCase() : 'L' }}
                        </div>
                        <div class="hidden lg:flex flex-col">
                            <span class="text-xs font-bold text-slate-800 leading-none">{{ user?.name || 'Chủ trọ' }}</span>
                            <span class="text-[10px] font-semibold text-emerald-500 mt-1">Chủ Trọ</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main view screen -->
            <main class="flex-1 overflow-y-auto p-6 md:p-8 bg-slate-50">
                <slot />
            </main>
        </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <Teleport to="body">
        <div v-if="drawerOpen" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex" @click.self="closeDrawer">
            <div class="w-72 h-full bg-white flex flex-col shadow-2xl animate-slide-in">
                <!-- Drawer Head -->
                <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 h-16 flex-shrink-0">
                    <div class="w-10 h-10 bg-emerald-500 text-white rounded-xl flex items-center justify-center font-bold text-2xl">
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
                        <p v-if="group.label" class="px-3 text-[10px] font-bold text-slate-400 tracking-wider uppercase">
                            {{ group.label }}
                        </p>
                        <div class="space-y-0.5">
                            <component 
                                :is="item.path === '#' ? 'div' : Link"
                                v-for="item in group.items"
                                :key="item.label"
                                :href="item.path !== '#' ? item.path : undefined"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200',
                                    item.path === '#' ? 'cursor-not-allowed opacity-75' : 'cursor-pointer',
                                    isActive(item.path) 
                                        ? 'bg-emerald-50 text-emerald-600 font-semibold shadow-sm' 
                                        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'
                                ]"
                                @click="closeDrawer"
                            >
                                <i :class="['bi', item.icon, 'text-lg', isActive(item.path) ? 'text-emerald-500' : 'text-slate-400']"></i>
                                <span class="text-sm">{{ item.label }}</span>
                                <span v-if="item.isPro" class="ml-auto px-1.5 py-0.5 text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-200 rounded uppercase">PRO</span>
                            </component>
                        </div>
                    </div>
                </nav>

                <!-- Drawer Footer -->
                <div class="p-4 border-t border-slate-100">
                    <button @click="logout" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-rose-500 hover:bg-rose-50 w-full transition-all font-medium text-sm">
                        <i class="bi bi-box-arrow-right text-lg"></i>
                        <span>Đăng Xuất</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Mobile Bottom Tab Bar -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white border-t border-slate-100 flex items-center justify-around z-30 pb-safe shadow-lg">
        <template v-for="tab in bottomTabs" :key="tab.label">
            <button v-if="tab.action" class="flex flex-col items-center gap-1 text-slate-400 hover:text-emerald-500 bg-none border-none p-2" @click="tab.action()">
                <i :class="['bi', tab.icon, 'text-xl']"></i>
                <span class="text-[9px] font-bold uppercase tracking-wider">{{ tab.label }}</span>
            </button>
            <Link v-else :href="tab.path" class="flex flex-col items-center gap-1 p-2" :class="isActive(tab.path) ? 'text-emerald-500' : 'text-slate-400 hover:text-slate-800'">
                <i :class="['bi', tab.icon, 'text-xl']"></i>
                <span class="text-[9px] font-bold uppercase tracking-wider">{{ tab.label }}</span>
            </Link>
        </template>
    </nav>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom);
}
@keyframes slideIn {
    from { transform: translateX(-100%); }
    to { transform: translateX(0); }
}
.animate-slide-in {
    animation: slideIn 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

